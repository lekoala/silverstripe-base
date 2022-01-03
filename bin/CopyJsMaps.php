<?php

$dir = dirname(__DIR__);

$baseDir = dirname($dir);
$composerInstall = $baseDir . "/vendor/composer";
while (!is_dir($composerInstall)) {
    $baseDir = dirname($dir);
    $composerInstall = $baseDir . "/vendor/composer";
}

echo "Base dir $baseDir\n";

$srcDir = $dir . "/resources/Admin";
$destDir = $baseDir . "/vendor/silverstripe/admin/thirdparty/bootstrap/js/dist";
$maps = glob("$srcDir/*.js.map");
$c = count($maps);
echo "Processing $c files from $srcDir\n";

foreach ($maps as $map) {
    $dest = $destDir . "/" . basename($map);
    echo "Processing $map\n";
    if (!is_file($dest)) {
        copy($map, $dest);
        echo "Copied to $dest\n";
    }
}

// Remove map from popper
$popper =  $baseDir . "/vendor/silverstripe/admin/thirdparty/popper/popper.min.js";
if (is_file($popper)) {
    $contents = file_get_contents($popper);
    $contents = str_replace("//# sourceMappingURL=popper.min.js.map", "", $contents);
    file_put_contents($popper, $contents);
    echo "Removing map from popper\n";
}

echo "All done\n";
