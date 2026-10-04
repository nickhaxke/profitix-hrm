"""
Profitix HRM — ZKTeco Device Sync Service
==========================================
Pulls attendance logs from ZKTeco biometric devices using the pyzk library.
Inserts records into the profitix_hrm MySQL database.

Requirements: pip install pyzk mysql-connector-python

Usage: python device_sync.py
       python device_sync.py --device-id 1
       python device_sync.py --all
"""

import sys
import json
import argparse
import logging
from datetime import datetime

try:
    from zk import ZK
except ImportError:
    print("ERROR: pyzk not installed. Run: pip install pyzk")
    sys.exit(1)

try:
    import mysql.connector
except ImportError:
    print("ERROR: mysql-connector-python not installed. Run: pip install mysql-connector-python")
    sys.exit(1)

is_json = '--json' in sys.argv

# Configure logging
handlers = [logging.FileHandler('device_sync.log', encoding='utf-8')]
if not is_json:
    handlers.append(logging.StreamHandler(sys.stderr))

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=handlers
)
logger = logging.getLogger(__name__)

# Database config — reads from .env or uses defaults
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'database': 'profitix_hrm',
    'user': 'root',
    'password': 'root',
}


def load_env():
    """Load database config from Laravel .env file if present."""
    import os
    env_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), '.env')
    if os.path.exists(env_path):
        with open(env_path) as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#') and '=' in line:
                    key, val = line.split('=', 1)
                    key, val = key.strip(), val.strip().strip('"').strip("'")
                    if key == 'DB_HOST': DB_CONFIG['host'] = val
                    elif key == 'DB_PORT': DB_CONFIG['port'] = int(val)
                    elif key == 'DB_DATABASE': DB_CONFIG['database'] = val
                    elif key == 'DB_USERNAME': DB_CONFIG['user'] = val
                    elif key == 'DB_PASSWORD': DB_CONFIG['password'] = val


def get_db():
    """Get MySQL connection."""
    return mysql.connector.connect(**DB_CONFIG)


def get_devices(db, device_id=None):
    """Fetch active devices from database."""
    cursor = db.cursor(dictionary=True)
    if device_id:
        cursor.execute("SELECT * FROM devices WHERE id = %s AND is_active = 1", (device_id,))
    else:
        cursor.execute("SELECT * FROM devices WHERE is_active = 1")
    return cursor.fetchall()


def sync_device(db, device):
    """Connect to a single ZKTeco device and pull attendance logs."""
    device_id = device['id']
    ip = device['ip_address']
    port = device['port'] or 4370

    logger.info(f"Syncing device: {device['name']} ({ip}:{port})")

    # Create sync log entry
    cursor = db.cursor()
    cursor.execute(
        "INSERT INTO device_sync_logs (device_id, sync_type, status, records_count, started_at, created_at, updated_at) VALUES (%s, 'attendance', 'running', 0, NOW(), NOW(), NOW())",
        (device_id,)
    )
    db.commit()
    sync_log_id = cursor.lastrowid

    zk = ZK(ip, port=port, timeout=10)
    conn = None
    records_count = 0

    try:
        conn = zk.connect()
        conn.disable_device()

        # Pull attendance records
        attendances = conn.get_attendance()
        if not attendances:
            logger.info(f"  No attendance records on device.")
            attendances = []

        for att in attendances:
            user_id = str(att.user_id)
            punch_time = att.timestamp
            punch_status = att.status  # 0=Check In, 1=Check Out, etc.

            # Map ZK punch status to our punch_type
            punch_type = 'in' if punch_status in (0, 4) else ('out' if punch_status in (1, 5) else 'unknown')

            # Find employee by biometric_id
            cursor.execute("SELECT id FROM employees WHERE biometric_id = %s LIMIT 1", (user_id,))
            emp = cursor.fetchone()

            if not emp:
                logger.warning(f"  Unknown biometric_id: {user_id} — skipping")
                continue

            employee_id = emp[0]

            # Check for duplicate
            cursor.execute(
                "SELECT id FROM attendance_logs WHERE employee_id = %s AND punch_time = %s AND device_id = %s LIMIT 1",
                (employee_id, punch_time, device_id)
            )
            if cursor.fetchone():
                continue  # Skip duplicate

            # Insert new log
            cursor.execute(
                "INSERT INTO attendance_logs (employee_id, device_id, punch_time, punch_type, source, is_processed, created_at, updated_at) VALUES (%s, %s, %s, %s, 'device', 0, NOW(), NOW())",
                (employee_id, device_id, punch_time, punch_type)
            )
            records_count += 1

        conn.enable_device()
        db.commit()

        # Update device status
        cursor.execute("UPDATE devices SET is_online = 1, last_sync = NOW(), last_ping = NOW(), updated_at = NOW() WHERE id = %s", (device_id,))

        # Update sync log
        cursor.execute(
            "UPDATE device_sync_logs SET status = 'success', records_count = %s, completed_at = NOW(), updated_at = NOW() WHERE id = %s",
            (records_count, sync_log_id)
        )
        db.commit()

        logger.info(f"  ✅ Synced {records_count} new records from {device['name']}")

    except Exception as e:
        logger.error(f"  ❌ Error syncing {device['name']}: {e}")
        cursor.execute("UPDATE devices SET is_online = 0, updated_at = NOW() WHERE id = %s", (device_id,))
        cursor.execute(
            "UPDATE device_sync_logs SET status = 'failed', error_message = %s, completed_at = NOW(), updated_at = NOW() WHERE id = %s",
            (str(e), sync_log_id)
        )
        db.commit()

    finally:
        if conn:
            try:
                conn.disconnect()
            except:
                pass

    return records_count


def sync_users_from_device(db, device):
    """Connect to a single ZKTeco device and pull user records."""
    device_id = device['id']
    ip = device['ip_address']
    port = device['port'] or 4370

    logger.info(f"Syncing users from device: {device['name']} ({ip}:{port})")

    zk = ZK(ip, port=port, timeout=10)
    conn = None
    records_count = 0

    try:
        conn = zk.connect()
        conn.disable_device()

        # Pull users
        users = conn.get_users()
        if not users:
            logger.info(f"  No users on device.")
            users = []

        cursor = db.cursor()

        for u in users:
            bio_id = str(u.user_id)
            name = u.name.strip() if u.name else f"Device User {bio_id}"

            # Check if employee exists by biometric_id
            cursor.execute("SELECT id FROM employees WHERE biometric_id = %s LIMIT 1", (bio_id,))
            emp = cursor.fetchone()

            if not emp:
                # Insert new employee stub
                # generate a generic employee_code
                cursor.execute("SELECT MAX(id) FROM employees")
                max_id = cursor.fetchone()[0] or 0
                emp_code = f"EMP{max_id + 1:04d}"
                
                cursor.execute(
                    "INSERT INTO employees (employee_code, first_name, last_name, biometric_id, status, created_at, updated_at) VALUES (%s, %s, %s, %s, 'active', NOW(), NOW())",
                    (emp_code, name, '', bio_id)
                )
                records_count += 1
            else:
                # Optionally update name if needed, but we'll skip to not overwrite HR data
                pass

        conn.enable_device()
        db.commit()

        # Update device status
        cursor.execute("UPDATE devices SET is_online = 1, last_ping = NOW(), updated_at = NOW() WHERE id = %s", (device_id,))
        db.commit()

        logger.info(f"  ✅ Synced {records_count} new users from {device['name']}")

    except Exception as e:
        logger.error(f"  ❌ Error syncing users from {device['name']}: {e}")
        cursor.execute("UPDATE devices SET is_online = 0, updated_at = NOW() WHERE id = %s", (device_id,))
        db.commit()

    finally:
        if conn:
            try:
                conn.disconnect()
            except:
                pass

    return records_count


def list_users_from_device(db, device):
    """Fetch users and their template counts without inserting into DB."""
    device_id = device['id']
    ip = device['ip_address']
    port = device['port'] or 4370

    zk = ZK(ip, port=port, timeout=10)
    conn = None
    device_users = []

    try:
        conn = zk.connect()
        conn.disable_device()

        users = conn.get_users()
        templates = conn.get_templates()

        # Count templates per user uid
        template_counts = {}
        if templates:
            for t in templates:
                template_counts[t.uid] = template_counts.get(t.uid, 0) + 1

        for u in users:
            device_users.append({
                'uid': u.uid,
                'user_id': str(u.user_id),
                'name': u.name.strip() if u.name else '',
                'privilege': u.privilege,
                'card': getattr(u, 'card', ''),
                'fingerprint_count': template_counts.get(u.uid, 0)
            })

        conn.enable_device()
        
        # Mark as online
        cursor = db.cursor()
        cursor.execute("UPDATE devices SET is_online = 1, last_ping = NOW(), updated_at = NOW() WHERE id = %s", (device_id,))
        db.commit()

    except Exception as e:
        logger.error(f"  ❌ Error listing users from {device['name']}: {e}")
        cursor = db.cursor()
        cursor.execute("UPDATE devices SET is_online = 0, updated_at = NOW() WHERE id = %s", (device_id,))
        db.commit()
        raise e

    finally:
        if conn:
            try:
                conn.disconnect()
            except:
                pass

    return device_users


def push_user_to_device(db, device, user_id, name):
    """Push an employee record from the DB directly to the biometric device."""
    device_id = device['id']
    ip = device['ip_address']
    port = device['port'] or 4370

    zk = ZK(ip, port=port, timeout=10)
    conn = None

    try:
        conn = zk.connect()
        conn.disable_device()

        users = conn.get_users()
        # Find a free uid or check if user_id already exists
        target_uid = None
        uids = []
        for u in users:
            uids.append(u.uid)
            if str(u.user_id) == str(user_id):
                target_uid = u.uid
                break
                
        if not target_uid:
            target_uid = max(uids) + 1 if uids else 1

        safe_name = name[:24]
        
        conn.set_user(uid=target_uid, name=safe_name, privilege=0, password='', group_id='', user_id=str(user_id))
        
        conn.enable_device()
        return {'success': True, 'message': f'User {safe_name} pushed successfully to device.'}

    except Exception as e:
        logger.error(f"❌ Error pushing user {name} to {device['name']}: {e}")
        return {'success': False, 'message': str(e)}

    finally:
        if conn:
            try:
                conn.disconnect()
            except:
                pass


def delete_user_from_device(db, device, uid, user_id):
    """Delete a user from the biometric device."""
    ip = device['ip_address']
    port = device['port'] or 4370
    zk = ZK(ip, port=port, timeout=10)
    conn = None

    try:
        conn = zk.connect()
        conn.disable_device()
        conn.delete_user(uid=int(uid), user_id=str(user_id))
        conn.enable_device()
        return {'success': True, 'message': f'User {user_id} deleted successfully.'}
    except Exception as e:
        return {'success': False, 'message': str(e)}
    finally:
        if conn:
            try:
                conn.disconnect()
            except:
                pass


def enroll_fingerprint(db, device, uid, user_id):
    """Trigger device to enter fingerprint enrollment mode."""
    ip = device['ip_address']
    port = device['port'] or 4370
    zk = ZK(ip, port=port, timeout=10)
    conn = None

    try:
        conn = zk.connect()
        conn.disable_device()
        # enroll_user(uid, temp_id, user_id) temp_id is 0-9 for fingers
        # find next available temp_id for this user
        templates = conn.get_templates()
        user_temps = [t.fid for t in templates if str(t.uid) == str(uid)]
        
        next_fid = 0
        while next_fid in user_temps and next_fid < 10:
            next_fid += 1
            
        if next_fid >= 10:
            return {'success': False, 'message': 'Maximum fingerprints (10) reached for this user.'}
            
        conn.enroll_user(uid=int(uid), temp_id=next_fid, user_id=str(user_id))
        conn.enable_device()
        return {'success': True, 'message': f'Device ready for enrollment. Please place finger on device.'}
    except Exception as e:
        return {'success': False, 'message': f'Enrollment failed: {str(e)}'}
    finally:
        if conn:
            try:
                conn.disconnect()
            except:
                pass


def execute_device_command(db, device, command):
    """Execute a remote command on the device."""
    ip = device['ip_address']
    port = device['port'] or 4370
    zk = ZK(ip, port=port, timeout=15)
    conn = None

    try:
        conn = zk.connect()
        conn.disable_device()

        if command == 'reboot':
            conn.restart()
            message = 'Device is restarting.'
        elif command == 'sync-time':
            conn.set_time(datetime.now())
            message = 'Device time synchronized.'
        elif command == 'clear-logs':
            conn.clear_attendance()
            message = 'Attendance logs cleared.'
        elif command == 'clear-users':
            users = conn.get_users()
            count = 0
            for u in users:
                conn.delete_user(uid=u.uid, user_id=u.user_id)
                count += 1
            message = f'Cleared {count} users from device.'
        elif command == 'test':
            info = {
                'firmware': conn.get_firmware_version(),
                'serial': conn.get_serialnumber(),
                'mac': conn.get_mac(),
                'time': str(conn.get_time()),
                'device_name': conn.get_device_name()
            }
            message = 'Connection successful.'
            return {'success': True, 'message': message, 'data': info}
        else:
            return {'success': False, 'message': f'Unknown command: {command}'}

        if command != 'reboot':
            conn.enable_device()
            
        return {'success': True, 'message': message}
        
    except Exception as e:
        return {'success': False, 'message': str(e)}
    finally:
        if conn:
            try:
                conn.disconnect()
            except:
                pass


def main():
    parser = argparse.ArgumentParser(description='Profitix HRM — ZKTeco Device Sync')
    parser.add_argument('--device-id', type=int, help='Sync specific device by ID')
    parser.add_argument('--all', action='store_true', help='Sync all active devices')
    parser.add_argument('--sync-users', action='store_true', help='Sync users instead of attendance logs')
    parser.add_argument('--list-users', action='store_true', help='List users from device (no DB insert)')
    parser.add_argument('--push-user', action='store_true', help='Push a specific user to the device')
    parser.add_argument('--user-id', type=str, help='The biometric user_id to push')
    parser.add_argument('--name', type=str, help='The name of the user to push')
    parser.add_argument('--delete-user', action='store_true', help='Delete a user from the device')
    parser.add_argument('--enroll-user', action='store_true', help='Trigger fingerprint enrollment on device')
    parser.add_argument('--uid', type=int, help='The internal UID of the user on the device')
    parser.add_argument('--command', type=str, help='Execute device command: reboot, sync-time, clear-logs, clear-users, test')
    parser.add_argument('--json', action='store_true', help='Output results as JSON (for PHP bridge)')
    args = parser.parse_args()

    load_env()

    try:
        db = get_db()
    except Exception as e:
        result = {'success': False, 'message': f'Database error: {e}'}
        if args.json:
            print(json.dumps(result))
        else:
            logger.error(result['message'])
        sys.exit(1)

    devices = get_devices(db, args.device_id)

    if not devices:
        result = {'success': False, 'message': 'No devices found'}
        if args.json:
            print(json.dumps(result))
        else:
            logger.warning(result['message'])
        sys.exit(0)

    # Handle device commands
    if args.command:
        if len(devices) > 1:
            result = {'success': False, 'message': 'Can only execute command on one device at a time'}
            if args.json: print(json.dumps(result))
            sys.exit(1)
        result = execute_device_command(db, devices[0], args.command)
        if args.json: print(json.dumps(result))
        db.close()
        sys.exit(0)

    # Handle delete-user
    if args.delete_user:
        if len(devices) > 1:
            result = {'success': False, 'message': 'Can only delete user from one device at a time'}
            if args.json: print(json.dumps(result))
            sys.exit(1)
        if args.uid is None or not args.user_id:
            result = {'success': False, 'message': 'Must provide --uid and --user-id to delete user'}
            if args.json: print(json.dumps(result))
            sys.exit(1)
            
        result = delete_user_from_device(db, devices[0], args.uid, args.user_id)
        if args.json: print(json.dumps(result))
        db.close()
        sys.exit(0)

    # Handle enroll-user
    if args.enroll_user:
        if len(devices) > 1:
            result = {'success': False, 'message': 'Can only enroll user on one device at a time'}
            if args.json: print(json.dumps(result))
            sys.exit(1)
        if args.uid is None or not args.user_id:
            result = {'success': False, 'message': 'Must provide --uid and --user-id to enroll user'}
            if args.json: print(json.dumps(result))
            sys.exit(1)
            
        result = enroll_fingerprint(db, devices[0], args.uid, args.user_id)
        if args.json: print(json.dumps(result))
        db.close()
        sys.exit(0)

    # Handle push-user
    if args.push_user:
        if len(devices) > 1:
            result = {'success': False, 'message': 'Can only push user to one device at a time'}
            if args.json:
                print(json.dumps(result))
            sys.exit(1)
        if not args.user_id or not args.name:
            result = {'success': False, 'message': 'Must provide --user-id and --name to push user'}
            if args.json:
                print(json.dumps(result))
            sys.exit(1)
            
        result = push_user_to_device(db, devices[0], args.user_id, args.name)
        if args.json:
            print(json.dumps(result))
        db.close()
        sys.exit(0)

    # Handle list-users which expects exactly one device
    if args.list_users:
        if len(devices) > 1:
            result = {'success': False, 'message': 'Can only list users from one device at a time'}
            if args.json:
                print(json.dumps(result))
            sys.exit(1)
        
        try:
            device_users = list_users_from_device(db, devices[0])
            result = {
                'success': True,
                'device_id': devices[0]['id'],
                'users': device_users
            }
            if args.json:
                print(json.dumps(result))
        except Exception as e:
            result = {'success': False, 'message': str(e)}
            if args.json:
                print(json.dumps(result))
        
        db.close()
        sys.exit(0)

    total_records = 0
    results = []
    for device in devices:
        if args.sync_users:
            count = sync_users_from_device(db, device)
        else:
            count = sync_device(db, device)
            
        total_records += count
        results.append({'device': device['name'], 'records': count})

    db.close()

    result = {
        'success': True,
        'total_devices': len(devices),
        'total_records': total_records,
        'devices': results,
    }

    if args.json:
        print(json.dumps(result))
    else:
        logger.info(f"Sync complete: {total_records} records from {len(devices)} devices")


if __name__ == '__main__':
    main()
