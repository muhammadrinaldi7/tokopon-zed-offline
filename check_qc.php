<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$imei = 'POP01';
$items = \App\Models\OrderItem::where('serial_number', 'LIKE', '%' . $imei . '%')->with('variant')->get();
if ($items->count() > 0) {
    foreach ($items as $item) {
        echo 'OrderItem Found ID: ' . $item->id . PHP_EOL;
        echo 'Serial Number: ' . $item->serial_number . PHP_EOL;
        $variant = $item->variant;
        if ($variant) {
            echo 'Variant ID: ' . $variant->id . PHP_EOL;
            echo 'Variant Class: ' . get_class($variant) . PHP_EOL;
            $catName = null;
            $brandName = null;
            
            if ($variant instanceof \App\Models\ProductAccurate) {
                $catName = $variant->categoryName;
                $brandName = $variant->brandName;
            } elseif (method_exists($variant, 'accurateData') && $variant->accurateData) {
                $catName = $variant->accurateData->categoryName;
                $brandName = $variant->accurateData->brandName;
            }
            
            if ($catName) {
                echo 'Variant Category: ' . $catName . PHP_EOL;
                echo 'Brand Name: ' . $brandName . PHP_EOL;
                
                $normCat = \App\Models\QcTemplate::normalizeDeviceCategory($catName);
                echo 'Normalized Category: ' . $normCat . PHP_EOL;
                
                $brand = \App\Models\Brand::where('name', 'like', '%' . $brandName . '%')->first();
                $brandId = $brand->id ?? null;
                echo 'Resolved Brand ID: ' . $brandId . PHP_EOL;
                
                $matchedTemplate = \App\Models\QcTemplate::findForBrandAndCategory($brandId, $normCat);
                if ($matchedTemplate) {
                    echo 'Matched Template ID: ' . $matchedTemplate->id . ' - ' . $matchedTemplate->name . PHP_EOL;
                } else {
                    echo 'No template matched.' . PHP_EOL;
                }
            } else {
                echo 'Variant does not have accurateData category' . PHP_EOL;
            }
        }
    }
} else {
    echo 'OrderItem not found for IMEI ' . $imei . PHP_EOL;
}

echo PHP_EOL . '=== Smartwatch Products ===' . PHP_EOL;
$smartwatches = \App\Models\ProductAccurate::where('categoryName', 'like', '%smartwatch%')->orWhere('categoryName', 'like', '%watch%')->get();
foreach ($smartwatches as $sw) {
    echo 'ID: ' . $sw->id . ' | Name: ' . $sw->name . ' | Category: ' . $sw->categoryName . PHP_EOL;
}
