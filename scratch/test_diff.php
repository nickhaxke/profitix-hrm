<?php

require __DIR__.'/../vendor/autoload.php';
$t1 = Carbon\Carbon::parse('2026-04-01 07:54:10');
$t2 = Carbon\Carbon::parse('2026-04-01 16:49:41');
echo 'diffInSeconds t2 to t1: '.$t2->diffInSeconds($t1)."\n";
echo 'diffInSeconds t1 to t2: '.$t1->diffInSeconds($t2)."\n";
