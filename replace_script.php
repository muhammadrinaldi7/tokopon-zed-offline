<?php
$content = file_get_contents('d:\APP\tokopon-zed\app\Livewire\Admin\Reporting\SalesReport.php');
$newOpsi3 = file_get_contents('d:\APP\tokopon-zed\temp_opsi3.txt');

$pattern = '/\s*public function exportCsvOpsi3\(\)[\s\S]*?(?=\s*public function render\(\))/';
$content = preg_replace($pattern, "\n\n" . $newOpsi3 . "\n\n", $content);

file_put_contents('d:\APP\tokopon-zed\app\Livewire\Admin\Reporting\SalesReport.php', $content);
echo "Replaced exportCsvOpsi3 successfully.\n";
