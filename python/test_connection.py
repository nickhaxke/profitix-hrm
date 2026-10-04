"""
Quick test: Try ALL possible connection methods to the ZKTeco device.
"""
import sys
import json
import socket

sys.path.insert(0, '.')

try:
    from zk import ZK
except ImportError:
    print("ERROR: pyzk not installed")
    sys.exit(1)

IP = '192.168.1.15'
results = []

# ---- Test 1: Basic TCP ping on relevant ports ----
print("=" * 50)
print("TEST 1: TCP Port Scan")
print("=" * 50)
for port in [4370, 80, 443, 4371, 8000]:
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.settimeout(2)
        r = s.connect_ex((IP, port))
        status = "OPEN" if r == 0 else "CLOSED"
        print(f"  TCP Port {port}: {status}")
        s.close()
    except Exception as e:
        print(f"  TCP Port {port}: ERROR - {e}")

# ---- Test 2: UDP port scan ----
print("\n" + "=" * 50)
print("TEST 2: UDP Port Check (4370)")
print("=" * 50)
try:
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    s.settimeout(3)
    # Send a ZK discovery packet
    s.sendto(b'\x50\x50\x82\x7d\x08\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00', (IP, 4370))
    try:
        data, addr = s.recvfrom(1024)
        print(f"  UDP 4370: GOT RESPONSE! ({len(data)} bytes)")
    except socket.timeout:
        print(f"  UDP 4370: No response (timeout)")
    s.close()
except Exception as e:
    print(f"  UDP 4370: ERROR - {e}")

# ---- Test 3: Try pyzk with different configs ----
configs = [
    {"port": 4370, "force_udp": False, "ommit_ping": False, "desc": "TCP port 4370 (default)"},
    {"port": 4370, "force_udp": True, "ommit_ping": False, "desc": "UDP port 4370"},
    {"port": 4370, "force_udp": False, "ommit_ping": True, "desc": "TCP port 4370 (skip ping)"},
    {"port": 4370, "force_udp": True, "ommit_ping": True, "desc": "UDP port 4370 (skip ping)"},
    {"port": 80, "force_udp": False, "ommit_ping": False, "desc": "TCP port 80"},
    {"port": 80, "force_udp": False, "ommit_ping": True, "desc": "TCP port 80 (skip ping)"},
    {"port": 80, "force_udp": True, "ommit_ping": False, "desc": "UDP port 80"},
    {"port": 80, "force_udp": True, "ommit_ping": True, "desc": "UDP port 80 (skip ping)"},
    {"port": 443, "force_udp": False, "ommit_ping": True, "desc": "TCP port 443 (skip ping)"},
]

print("\n" + "=" * 50)
print("TEST 3: pyzk Connection Attempts")
print("=" * 50)

for cfg in configs:
    desc = cfg['desc']
    try:
        zk = ZK(IP, port=cfg['port'], timeout=5, password=123321, force_udp=cfg['force_udp'], ommit_ping=cfg['ommit_ping'])
        conn = zk.connect()
        if conn:
            fw = conn.get_firmware_version()
            sn = conn.get_serialnumber()
            print(f"  [SUCCESS] {desc}: CONNECTED! FW={fw}, SN={sn}")
            results.append({"config": cfg, "success": True})
            conn.disconnect()
        else:
            print(f"  [FAIL] {desc}: connect() returned None")
            results.append({"config": cfg, "success": False})
    except Exception as e:
        err = str(e)
        if len(err) > 80:
            err = err[:80] + "..."
        print(f"  [FAIL] {desc}: {err}")
        results.append({"config": cfg, "success": False, "error": str(e)})

# Summary
print("\n" + "=" * 50)
print("SUMMARY")
print("=" * 50)
working = [r for r in results if r.get('success')]
if working:
    print(f"  [SUCCESS] Found {len(working)} working connection method(s)!")
    for w in working:
        print(f"     -> {w['config']['desc']}")
else:
    print("  [FAIL] No pyzk connection method worked.")
    print("  Device may need port 4370 enabled in its web admin settings,")
    print("  or the system needs to use ADMS/HTTP protocol instead.")
