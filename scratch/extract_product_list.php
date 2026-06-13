<?php
$file = 'd:/APP/tokopon-zed/resources/views/livewire/zoffline/pos/pos.blade.php';
$lines = file($file);

$startLine = 6 - 1; // 0-indexed
$endLine = 134 - 1; // 0-indexed

$productLines = array_slice($lines, $startLine, $endLine - $startLine + 1);
file_put_contents('d:/APP/tokopon-zed/resources/views/livewire/zoffline/pos/partials/product-list.blade.php', implode("", $productLines));

$newLines = array_merge(
    array_slice($lines, 0, $startLine),
    ["        @include('livewire.zoffline.pos.partials.product-list')\n"],
    array_slice($lines, $endLine + 1)
);

file_put_contents($file, implode("", $newLines));
echo "Product list extracted successfully.\n";
