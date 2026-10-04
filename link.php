<?php

echo '<h3>Auto-Fix Tool (Inasuluhisha Error 500 na Logo)</h3>';

// Tafuta mzizi halisi wa Laravel (kama lipo faili la artisan)
$laravelRoot = file_exists(__DIR__.'/artisan') ? __DIR__ : __DIR__.'/..';
echo 'Mzizi wa mfumo upo hapa: '.$laravelRoot.'<br><br>';

$mainStorage = $laravelRoot.'/storage';

// Kama storage kuu ni symlink kimakosa, lifute
if (is_link($mainStorage)) {
    echo "KOSA LIMEONEKANA: Folda kuu la 'storage' limekuwa symlink. Nalifuta...<br>";
    unlink($mainStorage);
}

// Tengeneza upya mfumo sahihi wa storage
$dirs = [
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/testing',
    'storage/framework/views',
    'storage/logs',
];

foreach ($dirs as $dir) {
    $path = $laravelRoot.'/'.$dir;
    if (! file_exists($path)) {
        if (mkdir($path, 0775, true)) {
            echo '✅ Imetengeneza folda: '.$dir.'<br>';
            file_put_contents($path.'/.gitignore', "*\n!.gitignore\n");
        } else {
            echo '❌ Imeshindwa kutengeneza: '.$dir.'<br>';
        }
    } else {
        echo '✅ Lipo: '.$dir.'<br>';
    }
}

// Sasa tengeneza link ya picha (Logo)
$publicStorageLink = $laravelRoot.'/public/storage';
if (is_link($publicStorageLink)) {
    unlink($publicStorageLink); // Futa la zamani kama lipo
}

$target = $laravelRoot.'/storage/app/public';
if (! file_exists($publicStorageLink)) {
    if (symlink($target, $publicStorageLink)) {
        echo '<br><b>🎉 Link ya Logo imetengenezwa kikamilifu!</b><br>';
    } else {
        echo '<br><b>⚠️ Link ya Logo imegoma kutengenezwa (Angalia CPanel permissions).</b><br>';
    }
} else {
    echo "<br><b>⚠️ Folda la 'public/storage' lipo na siyo link. Unapaswa kulifuta kwanza.</b><br>";
}

echo "<br><h3 style='color:green;'>TAYARI! Rudi kwenye page yako ya Login u-refresh.</h3>";
