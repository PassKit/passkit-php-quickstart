<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$directories = ['src', 'tests', 'shared', 'membership', 'coupons', 'event-tickets', 'flights'];
$files = [$root . '/getting-started.php'];
foreach ($directories as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

$failed = false;
foreach ($files as $file) {
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file);
    exec($command, $output, $code);
    if ($code !== 0) {
        fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
        $failed = true;
    }
    $output = [];
}
if ($failed) {
    exit(1);
}
echo 'Syntax checked ' . count($files) . " PHP files.\n";
