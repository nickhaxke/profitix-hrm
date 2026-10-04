<?php

/**
 * Profitix HRM — Python Execution Diagnostic
 * Run this from the browser: http://localhost/st/profitix-hrm/public/python_diag.php
 * DELETE THIS FILE AFTER DEBUGGING
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== PROFITIX HRM — PYTHON EXECUTION DIAGNOSTIC ===\n";
echo 'Timestamp: '.date('Y-m-d H:i:s')."\n\n";

// 1. PHP Environment
echo "--- 1. PHP ENVIRONMENT ---\n";
echo 'PHP Version: '.phpversion()."\n";
echo 'SAPI: '.php_sapi_name()."\n";
echo 'OS: '.PHP_OS."\n";
echo 'User running PHP: '.get_current_user()."\n";

// Try whoami
$whoami = shell_exec('whoami 2>&1');
echo 'whoami: '.trim($whoami)."\n";

// Environment PATH
echo 'PATH: '.(getenv('PATH') ?: 'NOT SET')."\n\n";

// 2. Disabled functions check
echo "--- 2. PHP FUNCTION AVAILABILITY ---\n";
$functions = ['exec', 'shell_exec', 'proc_open', 'passthru', 'system', 'popen'];
$disabled = explode(',', ini_get('disable_functions'));
$disabled = array_map('trim', $disabled);
foreach ($functions as $fn) {
    $available = ! in_array($fn, $disabled) && function_exists($fn);
    echo "  $fn: ".($available ? '✅ AVAILABLE' : '❌ DISABLED/MISSING')."\n";
}
echo "\n";

// 3. Test basic command execution
echo "--- 3. BASIC COMMAND EXECUTION TEST ---\n";
$test_cmds = [
    'echo HELLO_FROM_PHP',
    'whoami',
    'where python',
    'where py',
    'where python3',
];
foreach ($test_cmds as $cmd) {
    echo "  CMD: $cmd\n";
    $output = null;
    $retval = null;
    exec($cmd.' 2>&1', $output, $retval);
    echo "  EXIT: $retval\n";
    echo '  OUT:  '.implode(' | ', $output)."\n\n";
    $output = [];
}

// 4. Test Python executables
echo "--- 4. PYTHON EXECUTABLE TESTS ---\n";
$python_candidates = [
    'python',
    'py',
    'python3',
    'C:\\Python313\\python.exe',
    'C:\\Python312\\python.exe',
    'C:\\Python311\\python.exe',
    'C:\\Python310\\python.exe',
    'C:\\Users\\USER\\AppData\\Local\\Programs\\Python\\Python313\\python.exe',
    'C:\\Users\\USER\\AppData\\Local\\Programs\\Python\\Python312\\python.exe',
    'C:\\Users\\USER\\AppData\\Local\\Microsoft\\WindowsApps\\python.exe',
    'C:\\Users\\USER\\AppData\\Local\\Microsoft\\WindowsApps\\PythonSoftwareFoundation.Python.3.13_qbz5n2kfra8p0\\python.exe',
];

$working_python = null;
foreach ($python_candidates as $py) {
    echo "  Testing: $py\n";

    // Check if file exists (for absolute paths)
    if (strpos($py, '\\') !== false || strpos($py, '/') !== false) {
        echo '    File exists: '.(file_exists($py) ? 'YES' : 'NO')."\n";
        if (file_exists($py)) {
            echo '    File size: '.filesize($py)." bytes\n";
            echo '    Is executable: '.(is_executable($py) ? 'YES' : 'NO')."\n";
        }
    }

    // Try executing
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $cmd_str = '"'.$py.'" --version';
    $process = proc_open($cmd_str, $descriptorspec, $pipes, null, null);

    if (is_resource($process)) {
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit_code = proc_close($process);

        echo "    Exit code: $exit_code\n";
        echo '    STDOUT: '.trim($stdout)."\n";
        echo '    STDERR: '.trim($stderr)."\n";

        if ($exit_code === 0 && (stripos($stdout.$stderr, 'Python') !== false)) {
            echo "    ✅ THIS PYTHON WORKS!\n";
            if (! $working_python) {
                $working_python = $py;
            }
        } else {
            echo "    ❌ FAILED\n";
        }
    } else {
        echo "    ❌ proc_open failed entirely\n";
    }
    echo "\n";
}

// 4b. Test via cmd.exe wrapper
echo "--- 4b. TEST VIA CMD.EXE WRAPPER ---\n";
$cmd_candidates = [
    'cmd /c python --version',
    'cmd /c py --version',
    'cmd /c "C:\\Users\\USER\\AppData\\Local\\Microsoft\\WindowsApps\\PythonSoftwareFoundation.Python.3.13_qbz5n2kfra8p0\\python.exe" --version',
];
foreach ($cmd_candidates as $cmd) {
    echo "  CMD: $cmd\n";
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($cmd, $descriptorspec, $pipes, null, null);
    if (is_resource($process)) {
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit_code = proc_close($process);
        echo "    Exit: $exit_code | OUT: ".trim($stdout).' | ERR: '.trim($stderr)."\n";
        if ($exit_code === 0) {
            echo "    ✅ WORKS!\n";
            if (! $working_python) {
                $working_python = $cmd;
            }
        }
    } else {
        echo "    ❌ proc_open failed\n";
    }
    echo "\n";
}

// 4c. Test batch wrapper
echo "--- 4c. TEST BATCH WRAPPER ---\n";
$bat_path = __DIR__.'/../python/run_python.bat';
$bat_path_real = realpath($bat_path);
echo "  Batch file path: $bat_path_real\n";
echo '  Batch file exists: '.(file_exists($bat_path_real) ? 'YES' : 'NO')."\n";
if (file_exists($bat_path_real)) {
    echo "  Batch contents:\n";
    echo '    '.str_replace("\n", "\n    ", file_get_contents($bat_path_real))."\n";

    $cmd = '"'.$bat_path_real.'" --version';
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($cmd, $descriptorspec, $pipes, null, null);
    if (is_resource($process)) {
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit_code = proc_close($process);
        echo "  Exit: $exit_code | OUT: ".trim($stdout).' | ERR: '.trim($stderr)."\n";
        if ($exit_code === 0) {
            echo "  ✅ BATCH WRAPPER WORKS!\n";
        }
    }
}
echo "\n";

// 5. Verify script path
echo "--- 5. SCRIPT PATH VERIFICATION ---\n";
$script_path = realpath(__DIR__.'/../python/device_sync.py');
echo "  Script path: $script_path\n";
echo '  Exists: '.(file_exists($script_path) ? 'YES' : 'NO')."\n";
if (file_exists($script_path)) {
    echo '  Size: '.filesize($script_path)." bytes\n";
    echo '  Readable: '.(is_readable($script_path) ? 'YES' : 'NO')."\n";
}
echo "\n";

// 6. Test minimal Python script
echo "--- 6. MINIMAL PYTHON SCRIPT TEST ---\n";
$test_script = __DIR__.'/../python/test_exec.py';
file_put_contents($test_script, "import sys\nimport json\nprint(json.dumps({'success': True, 'python': sys.executable, 'version': sys.version}))\n");
echo "  Created test script: $test_script\n";

if ($working_python) {
    echo "  Using working Python: $working_python\n";
    $cmd = '"'.$working_python.'" "'.$test_script.'"';
    // If working_python is a cmd string, adjust
    if (strpos($working_python, 'cmd') === 0) {
        $parts = explode('--version', $working_python);
        $cmd = trim($parts[0]).' "'.$test_script.'"';
    }
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($cmd, $descriptorspec, $pipes, null, null);
    if (is_resource($process)) {
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit_code = proc_close($process);
        echo "  Exit: $exit_code\n";
        echo '  STDOUT: '.trim($stdout)."\n";
        echo '  STDERR: '.trim($stderr)."\n";
    }
} else {
    echo "  ⚠ No working Python found yet — testing all candidates with script\n";
    foreach (['python', 'py', 'cmd /c python'] as $py) {
        $cmd = $py.' "'.$test_script.'"';
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptorspec, $pipes, null, null);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $exit_code = proc_close($process);
            echo "  [$py] Exit: $exit_code | OUT: ".trim($stdout).' | ERR: '.trim($stderr)."\n";
        }
    }
}
echo "\n";

// 7. Summary
echo "=== SUMMARY ===\n";
if ($working_python) {
    echo "✅ WORKING PYTHON FOUND: $working_python\n";
    echo "\nRecommended .env setting:\n";
    echo 'PYTHON_PATH="'.str_replace('\\', '\\\\', $working_python)."\"\n";
} else {
    echo "❌ NO WORKING PYTHON FOUND FROM PHP/APACHE CONTEXT\n";
    echo "\nFALLBACK: Build a local Python HTTP API service instead of exec().\n";
    echo "This means PHP cannot execute Python at all under this Apache config.\n";
}

// Cleanup
@unlink($test_script);
