<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\PromoCalculatorService;
use Illuminate\Support\Facades\DB;

class RecalculatePromoDiscountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promo:recalculate-discounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculates discount_applied for past orders in order_promos table based on the latest formula (fixing overwriting bugs)';

    /**
     * Execute the console command.
     */
    public function handle(PromoCalculatorService $promoService)
    {
        $this->info('Starting recalculation of promo discounts...');
        
        // Find all orders that have promos
        $orders = Order::whereHas('promos')->with(['items.variant', 'promos'])->get();
        $this->info('Found ' . $orders->count() . ' orders with promos.');

        $updatedCount = 0;

        foreach ($orders as $order) {
            // Reconstruct $cart format needed by PromoCalculatorService
            $cart = [];
            foreach ($order->items as $item) {
                $variant = $item->variant;
                $sku = '';
                if ($variant) {
                    $sku = $variant->item_no ?? ($variant->sku ?? '');
                }

                $cart[] = [
                    'id' => uniqid(),
                    'variant_id' => $item->product_variant_id,
                    'variant_type' => $item->product_variant_type,
                    'sku' => $sku,
                    'name' => $item->product_name ?? ($variant->name ?? 'Unknown'),
                    'qty' => $item->qty,
                    'price' => $item->price_at_checkout,
                ];
            }

            $selectedPromoIds = $order->promos->pluck('id')->toArray();

            // Run the corrected apply logic
            $promoService->applyPromosToCart($cart, $selectedPromoIds);

            // Calculate totals from promo_discounts map
            $promoTotals = [];
            foreach ($cart as $item) {
                foreach ($item['promo_discounts'] ?? [] as $pid => $disc) {
                    $promoTotals[$pid] = ($promoTotals[$pid] ?? 0) + $disc;
                }
            }

            // Update pivot table order_promos
            foreach ($selectedPromoIds as $promoId) {
                $discountApplied = $promoTotals[$promoId] ?? 0;
                
                DB::table('order_promos')
                    ->where('order_id', $order->id)
                    ->where('promo_id', $promoId)
                    ->update(['discount_applied' => $discountApplied]);
            }

            // Populate new pivot table order_item_promos
            foreach ($order->items as $idx => $orderItem) {
                // Detach all old pivot for this order item to avoid duplicates 
                $orderItem->promos()->detach();

                $cartItem = $cart[$idx]; // Indexes match
                $vendorNameFallback = clone $orderItem;
                $vendorNameFallback = $vendorNameFallback->vendor_name;

                $cleanSns = array_values(array_filter(array_map('trim', explode(',', $orderItem->serial_number))));

                foreach ($cartItem['promo_discounts'] ?? [] as $promoId => $discAmount) {
                    if ($discAmount > 0) {
                        if (!empty($cleanSns)) {
                            // Jika ada SN, bagi diskon sebanyak jumlah SN
                            $discountPerSn = round($discAmount / max(1, count($cleanSns)));

                            foreach ($cleanSns as $sn) {
                                // Cari nama vendor asli dari tabel ProductSerialNumber
                                $snModel = \App\Models\ProductSerialNumber::with('vendor')->where('serial_number', $sn)->first();
                                $actualVendorName = $snModel?->vendor?->vendor_name ?? $vendorNameFallback;

                                $orderItem->promos()->attach($promoId, [
                                    'discount_amount' => $discountPerSn,
                                    'serial_number' => $sn,
                                    'vendor_name' => $actualVendorName,
                                ]);
                            }
                        } else {
                            // Jika tidak ada SN, simpan 1 row seperti biasa
                            $orderItem->promos()->attach($promoId, [
                                'discount_amount' => $discAmount,
                                'serial_number' => '',
                                'vendor_name' => $vendorNameFallback,
                            ]);
                        }
                    }
                }
            }
            
            $updatedCount++;
            $this->output->write('.');
        }

        $this->info("\nDone! Recalculated promos for $updatedCount orders.");
    }
}
