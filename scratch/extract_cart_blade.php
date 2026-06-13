<?php
$file = 'd:/APP/tokopon-zed/resources/views/livewire/zoffline/pos/pos.blade.php';
$lines = file($file);

$startLine = 140 - 1; // 0-indexed
$endLine = 975 - 1; // 0-indexed

$cartLines = array_slice($lines, $startLine, $endLine - $startLine + 1);
file_put_contents('d:/APP/tokopon-zed/resources/views/livewire/zoffline/pos/partials/cart.blade.php', implode("", $cartLines));

$newLines = array_merge(
    array_slice($lines, 0, $startLine),
    ["        @include('livewire.zoffline.pos.partials.cart')\n"],
    array_slice($lines, $endLine + 1)
);

file_put_contents($file, implode("", $newLines));
echo "Cart drawer extracted successfully.\n";
