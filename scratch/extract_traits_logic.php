<?php

$file = 'd:/APP/tokopon-zed/app/Livewire/Zoffline/Pos/Pos_Backup.php';
$content = file_get_contents($file);

// Function to extract a block of code based on start and end markers
function extractBlock(&$content, $startMarker, $endMarker) {
    $start = strpos($content, $startMarker);
    if ($start === false) return "";
    $end = strpos($content, $endMarker, $start);
    if ($end === false) return "";
    
    $block = substr($content, $start, $end - $start);
    
    // Remove the block from the original content
    $content = substr_replace($content, "", $start, $end - $start);
    
    return $block;
}

$traitsDir = 'd:/APP/tokopon-zed/app/Livewire/Zoffline/Pos/Traits';
if (!is_dir($traitsDir)) mkdir($traitsDir, 0777, true);

// 1. Extract WithCart
$withCartCode = "<?php\n\nnamespace App\Livewire\Zoffline\Pos\Traits;\n\n";
$withCartCode .= "use App\Models\Product;\n";
$withCartCode .= "use App\Models\ProductVariant;\n";
$withCartCode .= "use App\Models\SecondProduct;\n";
$withCartCode .= "use App\Models\SecondProductVariant;\n";
$withCartCode .= "use App\Services\AccurateService;\n";
$withCartCode .= "use Illuminate\Support\Facades\Auth;\n\n";
$withCartCode .= "trait WithCart\n{\n";

// Extract properties
$withCartCode .= extractBlock($content, "    // ─── Search & Filter", "    // ─── Customer");
$withCartCode .= extractBlock($content, "    // ─── Variant Selection", "    // ─── History Sales Properties");
$withCartCode .= extractBlock($content, "    // ─── QC Serah Terima", "    // ─── Draft Sales Properties");
// Extract methods
$withCartCode .= extractBlock($content, "    // ─── Cart Actions", "    // ─── Customer Actions");

$withCartCode .= "\n}\n";
file_put_contents("$traitsDir/WithCart.php", $withCartCode);


// 2. Extract WithCustomerAndSales
$withCustomerCode = "<?php\n\nnamespace App\Livewire\Zoffline\Pos\Traits;\n\n";
$withCustomerCode .= "trait WithCustomerAndSales\n{\n";

$withCustomerCode .= extractBlock($content, "    // ─── Customer", "    // ─── Payment");
$withCustomerCode .= extractBlock($content, "    // ─── Customer Actions", "    // ─── Checkout");

$withCustomerCode .= "\n}\n";
file_put_contents("$traitsDir/WithCustomerAndSales.php", $withCustomerCode);


// 3. Extract WithPaymentAndPromo
$withPaymentCode = "<?php\n\nnamespace App\Livewire\Zoffline\Pos\Traits;\n\n";
$withPaymentCode .= "use App\Models\Promo;\n";
$withPaymentCode .= "use Livewire\Attributes\Computed;\n\n";
$withPaymentCode .= "trait WithPaymentAndPromo\n{\n";

$withPaymentCode .= extractBlock($content, "    // ─── Payment", "    // ─── Modals");
$withPaymentCode .= extractBlock($content, "    // ─── Cart Subtotals", "    // ─── Cart Actions");

$withPaymentCode .= "\n}\n";
file_put_contents("$traitsDir/WithPaymentAndPromo.php", $withPaymentCode);


// 4. Extract WithCheckoutAndReceipt
$withCheckoutCode = "<?php\n\nnamespace App\Livewire\Zoffline\Pos\Traits;\n\n";
$withCheckoutCode .= "use App\Models\Order;\n";
$withCheckoutCode .= "use App\Models\OrderItem;\n";
$withCheckoutCode .= "use App\Models\OrderPayment;\n";
$withCheckoutCode .= "use App\Models\User;\n";
$withCheckoutCode .= "use App\Services\AccurateService;\n";
$withCheckoutCode .= "use Illuminate\Support\Facades\Auth;\n";
$withCheckoutCode .= "use Illuminate\Support\Facades\Log;\n";
$withCheckoutCode .= "use Illuminate\Support\Facades\Http;\n";
$withCheckoutCode .= "use Barryvdh\DomPDF\Facade\Pdf;\n\n";
$withCheckoutCode .= "trait WithCheckoutAndReceipt\n{\n";

$withCheckoutCode .= extractBlock($content, "    // ─── Checkout", "\n}"); // Everything till the end of the class

$withCheckoutCode .= "\n}\n";
file_put_contents("$traitsDir/WithCheckoutAndReceipt.php", $withCheckoutCode);

// Inject use statements into Pos.php
// We still need the original namespaces for Pos.php, so let's put it back together carefully.
$posCode = file_get_contents('d:/APP/tokopon-zed/app/Livewire/Zoffline/Pos/Pos_Backup.php');

// We will find the class declaration
$classStart = strpos($posCode, "class Pos extends Component");
$insideClass = strpos($posCode, "{", $classStart) + 1;

// The rest of the original methods that were not extracted are:
// - History Sales Properties & Methods
// - Draft Sales Properties & Methods
// - loadHistory(), loadDraft(), mount(), render(), etc.
// Since we deleted blocks from $content, $content now ONLY has what's left.

$newPosCode = substr($content, 0, $insideClass) . "\n";
$newPosCode .= "    use Traits\WithCart;\n";
$newPosCode .= "    use Traits\WithCustomerAndSales;\n";
$newPosCode .= "    use Traits\WithPaymentAndPromo;\n";
$newPosCode .= "    use Traits\WithCheckoutAndReceipt;\n";
$newPosCode .= substr($content, $insideClass);

file_put_contents('d:/APP/tokopon-zed/app/Livewire/Zoffline/Pos/Pos.php', $newPosCode);

echo "Traits extracted and Pos.php updated successfully!\n";
