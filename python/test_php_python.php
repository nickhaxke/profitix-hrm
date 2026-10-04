<?php

use Dotenv\Dotenv;

$base = dirname(__DIR__);
require $base.'/vendor/autoload.php';
$d = Dotenv::createImmutable($base);
$d->load();
echo 'PYTHON_PATH='.$_ENV['PYTHON_PATH'].PHP_EOL;
echo 'PYTHON_SCRIPT_PATH='.$_ENV['PYTHON_SCRIPT_PATH'].PHP_EOL;

// Now test execution with these exact values
$python = $_ENV['PYTHON_PATH'];
$script = $_ENV['PYTHON_SCRIPT_PATH'];
$cmd = escapeshellarg($python).' '.escapeshellarg($script).' --device-id 1 --list-users --json';
echo 'CMD='.$cmd.PHP_EOL;

$desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$p = proc_open($cmd, $desc, $pipes);
fclose($pipes[0]);
$out = stream_get_contents($pipes[1]);
fclose($pipes[1]);
$err = stream_get_contents($pipes[2]);
fclose($pipes[2]);
$exit = proc_close($p);
echo "EXIT=$exit\n";
echo "STDOUT=$out\n";
echo "STDERR=$err\n";
