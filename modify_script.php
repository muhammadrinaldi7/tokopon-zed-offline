<?php
$content = file_get_contents('d:\APP\tokopon-zed\app\Livewire\Admin\Reporting\SalesReport.php');

// Add property
$content = str_replace("public \$branchFilter = '';", "public \$branchFilter = '';\n    public \$csvSeparator = ';';", $content);

// Update Opsi 1 and 2 separators
$content = str_replace("use (\$orders)", "use (\$orders, \$separator)", $content);

$content = preg_replace("/\\\$csvFileName = '(laporan_.*?)';\s+return response\(\)->streamDownload/", "\$csvFileName = '\$1';\n        \$separator = \$this->csvSeparator;\n        return response()->streamDownload", $content);

$content = preg_replace("/fputcsv\(\\\$file, (\\\$headers)\);/", "fputcsv(\$file, \$1, \$separator);", $content);
$content = preg_replace("/fputcsv\(\\\$file, (\\\$rowData)\);/", "fputcsv(\$file, \$1, \$separator);", $content);
$content = preg_replace("/fputcsv\(\\\$file, (\\\$row)\);/", "fputcsv(\$file, \$1, \$separator);", $content);
$content = preg_replace("/\], ';'\);/", "], \$separator);", $content);

// Add TOTAL TAGIHAN (Rp)
$content = str_replace(
    "'SUBTOTAL ITEM (Rp)',\n                'METODE 1',",
    "'SUBTOTAL ITEM (Rp)',\n                'TOTAL TAGIHAN (Rp)',\n                'METODE 1',",
    $content
);

$content = str_replace(
    "\$item->discount_amount ?? 0,\n                            \$item->subtotal,\n                        ];",
    "\$item->discount_amount ?? 0,\n                            \$item->subtotal,\n                            \$totalOrderItemsSubtotal,\n                        ];",
    $content
);

$content = str_replace(
    "                        '-',\n                        '-',\n                        '-',\n                        '-',\n                        '-',\n                        '-',\n                        '0',\n                        '0',\n                        '0',\n                        '0'\n                    ];",
    "                        '-',\n                        '-',\n                        '-',\n                        '-',\n                        '-',\n                        '-',\n                        '0',\n                        '0',\n                        '0',\n                        '0',\n                        '0'\n                    ];",
    $content
);

file_put_contents('d:\APP\tokopon-zed\app\Livewire\Admin\Reporting\SalesReport.php', $content);
echo "Modifications applied successfully.\n";
