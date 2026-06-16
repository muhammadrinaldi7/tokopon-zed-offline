<?php
$filePath = 'd:/APP/tokopon-zed/resources/views/livewire/zoffline/sell-phone/sell-phone.blade.php';
$replacementPath = 'd:/APP/tokopon-zed/resources/views/livewire/zoffline/sell-phone/replacement.blade.php';

$content = file_get_contents($filePath);
$replacement = file_get_contents($replacementPath);

$step2Start = strpos($content, '{{-- STEP 2: QC Kelayakan Fisik --}}');
$step4Start = strpos($content, '{{-- STEP 4: Summary & Submit --}}');

if ($step2Start === false || $step4Start === false) {
    echo "Could not find markers.\n";
    exit(1);
}

$newContent = substr($content, 0, $step2Start) . $replacement . "\n        " . substr($content, $step4Start);

file_put_contents($filePath, $newContent);

echo "Success\n";
