<?php
$posFile = file_get_contents('d:/APP/tokopon-zed/app/Livewire/Zoffline/Pos/Pos.php');

// Define extraction targets
$cartStart = strpos($posFile, '    // ─── Search & Filter ───────────────────────────────────────');
$cartEnd = strpos($posFile, '    public function loadHistory()'); // End of cart logic, history logic begins

$cartLogic = substr($posFile, $cartStart, $cartEnd - $cartStart);

$cartTrait = "<?php\n\nnamespace App\Livewire\Zoffline\Pos\Traits;\n\nuse App\Services\AccurateService;\nuse App\Models\Product;\nuse App\Models\ProductVariant;\nuse App\Models\SecondProduct;\nuse App\Models\SecondProductVariant;\nuse Illuminate\Support\Facades\Auth;\nuse Livewire\Attributes\Computed;\n\ntrait WithCart\n{\n" . $cartLogic . "\n}\n";

file_put_contents('d:/APP/tokopon-zed/app/Livewire/Zoffline/Pos/Traits/WithCart.php', $cartTrait);

echo "WithCart.php created.\n";
