@echo off
set PYTHON_EXE="C:\Users\USER\AppData\Local\Microsoft\WindowsApps\python.exe"

echo ===========================================
echo Profitix Biometric Bridge Builder (Fixed v2)
echo ===========================================
echo.

echo [1/3] Cleaning old build files...
if exist build rd /s /q build
if exist dist rd /s /q dist

echo [2/3] Installing requirements...
%PYTHON_EXE% -m pip install pyzk requests pyinstaller

echo.
echo [3/3] Building EXE (CLEAN BUILD)...
%PYTHON_EXE% -m PyInstaller --onefile --clean --name Profitix_Sync_Bridge bridge_agent.py

echo.
echo [3/3] Cleaning up...
echo Done! 
echo.
echo SMART DELIVERY FOLDER:
echo 1. Go to 'dist' folder.
echo 2. You will see 'Profitix_Sync_Bridge.exe'.
echo 3. Create a 'settings.json' file next to it.
echo 4. Give the EXE and the JSON to the client.
echo.
pause
