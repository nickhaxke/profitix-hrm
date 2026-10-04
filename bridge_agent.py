import sys
import os
import time
import requests
import json
from datetime import datetime
from zk import ZK, const

# --- SMART CONFIGURATION LOADER ---
SETTINGS_FILE = "settings.json"

def load_settings():
    if not os.path.exists(SETTINGS_FILE):
        log_message("!!! SETTINGS NOT FOUND !!!", "SETUP")
        print("\n--- PROFITIX BRIDGE SETUP ---")
        cloud_url = input("Enter Cloud Sync URL (e.g., https://hrm.example.com/bridge/sync): ").strip()
        token = input("Enter Bridge Token (e.g., ProfitixBridge2026): ").strip()
        device_ip = input("Enter Biometric Device IP (e.g., 192.168.1.201): ").strip()
        device_id = input("Enter Device ID from CPanel (default 1): ").strip() or "1"
        chunk_size = input("Enter Sync Chunk Size (default 2000): ").strip() or "2000"
        
        settings = {
            "cloud_url": cloud_url,
            "bridge_token": token,
            "devices": [
                {
                    "id": int(device_id),
                    "ip": device_ip,
                    "port": 4370,
                    "name": "MAIN_DEVICE"
                }
            ],
            "chunk_size": 500,
            "interval": 30
        }
        
        with open(SETTINGS_FILE, "w") as f:
            json.dump(settings, f, indent=4)
        log_message("Settings saved to settings.json", "OK")
        return settings
        
    try:
        with open(SETTINGS_FILE, "r") as f:
            settings = json.load(f)
            # Ensure consistency for key names
            if 'sync_interval' in settings:
                settings['interval'] = settings.pop('sync_interval')
            return settings
    except Exception as e:
        log_message(f"Error loading settings.json: {e}", "ERROR")
        sys.exit(1)

def log_message(msg, status="INFO"):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    prefix = f"[{timestamp}] [{status}]"
    print(f"{prefix} {msg}")

def execute_hardware_command(conn, command_str, device_id=None):
    if not conn or not command_str: return
    
    action = command_str
    params = {}
    
    # Try to parse as JSON for complex commands
    try:
        if command_str.startswith('{'):
            data = json.loads(command_str)
            action = data.get('action')
            params = data
    except:
        pass

    try:
        if action == 'reboot':
            log_message("Executing REBOOT command...", "CMD")
            conn.restart()
        elif action == 'sync-time':
            log_message("Syncing device time...", "CMD")
            conn.set_time(datetime.now())
        elif action == 'test':
            log_message("Testing hardware voice...", "CMD")
            conn.test_voice()
        elif action == 'push_user':
            user_id = str(params.get('user_id'))
            name = params.get('name', 'Cloud User')
            log_message(f"Pushing User: {name} ({user_id})...", "CMD")
            conn.set_user(uid=int(user_id), name=name, privilege=const.USER_DEFAULT, user_id=user_id)
        elif action == 'delete_user':
            uid = params.get('uid')
            user_id = str(params.get('user_id'))
            log_message(f"Deleting User: {user_id}...", "CMD")
            conn.delete_user(uid=int(uid), user_id=user_id)
        elif action == 'enroll_user':
            uid = params.get('uid')
            log_message(f"Triggering Enrollment for UID: {uid}...", "CMD")
            conn.enroll_user(uid=int(uid))
        elif action == 'fetch_users':
            log_message("Fetching user list and templates from device...", "CMD")
            users = conn.get_users()
            templates = conn.get_templates()
            
            # Count templates per user uid
            template_counts = {}
            if templates:
                for t in templates:
                    template_counts[t.uid] = template_counts.get(t.uid, 0) + 1
            
            payload = []
            for u in users:
                payload.append({
                    'uid': u.uid,
                    'user_id': u.user_id,
                    'name': u.name.strip() if u.name else '',
                    'privilege': u.privilege,
                    'fingerprint_count': template_counts.get(u.uid, 0)
                })
            # Send to Cloud
            settings = load_settings()
            headers = {
                'X-Bridge-Token': settings['bridge_token'],
                'Content-Type': 'application/json'
            }
            requests.post(
                settings['cloud_url'].replace('/sync', '/sync-users'),
                json={'device_id': params.get('device_id', device_id), 'users': payload},
                headers=headers
            )
            log_message(f"Uploaded {len(payload)} users to cloud.", "OK")
        elif action == 'full_sync':
            log_message("Full log synchronization acknowledged.", "OK")
        else:
            log_message(f"Unknown Action: {action}", "WARN")
            
    except Exception as e:
        log_message(f"Command Execution Failed ({action}): {e}", "ERROR")

def sync_device(device, cloud_url, token, chunk_size):
    device_pwd = device.get('password', 0)
    zk = ZK(device['ip'], port=device['port'], timeout=10, password=device_pwd, force_udp=False, ommit_ping=True)
    conn = None
    payload_logs = []
    device_status = "offline"

    # 1. TRY CONNECT TO HARDWARE
    try:
        log_message(f"Checking {device['name']} ({device['ip']})...", "SCAN")
        conn = zk.connect()
        device_status = "online"
        
        attendance = conn.get_attendance()
        if attendance:
            # Send ALL logs found on the device
            for record in attendance:
                payload_logs.append({
                    'user_id': record.user_id,
                    'timestamp': record.timestamp.strftime("%Y-%m-%d %H:%M:%S"),
                    'status': record.status,
                    'punch': record.punch
                })
    except Exception as e:
        log_message(f"Device Connectivity Error: {str(e)}", "OFFLINE")
        device_status = "offline"

    # 2. ALWAYS SYNC TO CLOUD
    try:
        headers = {
            'X-Bridge-Token': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
        
        response = requests.post(
            cloud_url, 
            json={'device_id': device['id'], 'logs': payload_logs, 'device_status': device_status}, 
            headers=headers,
            timeout=25
        )

        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                log_message(f"Cloud: OK | Bridge: ONLINE | Device: {device_status.upper()}", "OK")
                pending_cmd = data.get('command')
                if pending_cmd and conn:
                    log_message(f"Action Required: {pending_cmd}", "CLOUD")
                    execute_hardware_command(conn, pending_cmd, device['id'])
            else:
                log_message(f"Cloud Error: {data.get('details', 'Unknown')}", "ERROR")
        else:
            log_message(f"Cloud HTTP Error {response.status_code}", "ERROR")

    except Exception as e:
        log_message(f"Cloud Connectivity Issue: {str(e)}", "OFFLINE")
    finally:
        if conn:
            conn.disconnect()

if __name__ == "__main__":
    print("===========================================")
    print("   PROFITIX BIOMETRIC CLOUD BRIDGE v2.5    ")
    print("   (Advanced Debugging & Sync Enabled)     ")
    print("===========================================")
    
    settings = load_settings()
    log_message(f"Gateway: {settings['cloud_url']}")
    
    while True:
        for device in settings['devices']:
            sync_device(device, settings['cloud_url'], settings['bridge_token'], settings.get('chunk_size', 2000))
        
        time.sleep(settings.get('interval', 30))
