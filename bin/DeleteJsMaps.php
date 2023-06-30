<?php

$dir = dirname(__DIR__);

$baseDir = dirname($dir);
$composerInstall = $baseDir . "/vendor/composer";
while (!is_dir($composerInstall)) {
    $baseDir = dirname($dir);
    $composerInstall = $baseDir . "/vendor/composer";
}

echo "Base dir $baseDir\n";

$destDir = $baseDir . "/vendor/silverstripe/admin/thirdparty/bootstrap/js/dist";
$maps = glob("$destDir/*.js");
echo "Processing files in $destDir\n";

foreach ($maps as $map) {
    $contents = file_get_contents($map);
    $base = basename($map);
    $contents = str_replace("//# sourceMappingURL=$base.map", "", $contents);
    file_put_contents($map, $contents);
    echo "Removing map from $map\n";
}

// Remove map from popper
$popper =  $baseDir . "/vendor/silverstripe/admin/thirdparty/popper/popper.min.js";
if (is_file($popper)) {
    $contents = file_get_contents($popper);
    $contents = str_replace("//# sourceMappingURL=popper.min.js.map", "", $contents);
    file_put_contents($popper, $contents);
    echo "Removing map from $popper\n";
}

echo "All done\n";
