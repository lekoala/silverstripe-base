<?php

$basePath = dirname(__DIR__);
$cdnFolder = "javascript/vendor/cdn";

$map = [
    'flatpickr' => 'https://cdn.jsdelivr.net/npm/flatpickr@4.6/dist',
    'jquery.inputmask' => 'https://cdn.jsdelivr.net/npm/inputmask@5.0.7/dist',
    'moment.js' => 'https://momentjs.com/downloads',
    // 'select2' => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist',
    'select2' => 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist',
];

$glob = glob($basePath . '/' . $cdnFolder . "/*");
foreach ($glob as $file) {
    if (!is_dir($file)) {
        continue;
    }
    $basename = basename($file);
    if (!isset($map[$basename])) {
        echo "skip $basename\n";
        continue;
    }
    $cdnBase = $map[$basename];

    echo "process $basename\n";

    $it = new RecursiveDirectoryIterator($file);
    /** @var SplFileInfo $el */
    foreach (new RecursiveIteratorIterator($it) as $el) {
        if (!$el->isFile()) {
            continue;
        }
        $path = str_replace($file, "", $el->getPathname());
        $path = str_replace("\\", "/", $path);

        $target = $cdnBase . $path;
        $content = file_get_contents($target);
        file_put_contents($el, $content);
        echo "updated $path\n";
    }
}
