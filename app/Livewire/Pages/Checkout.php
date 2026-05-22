<?php

namespace App\Livewire\Pages;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserAddress;
use App\Models\OrderPayment;
use App\Services\CartService;
use App\Services\XenditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Checkout extends Component
{
    // Address form
    public $recipientName = '';
    public $phoneNumber = '';
    public $fullAddress = '';
    public $postalCode = '';
    public $notes = '';

    // Shipping related
    public $couriersList = [];
    public $selectedCourier = '';
    public $shippingCost = 0;

    // Saved addresses
    public $savedAddresses = [];
    public $selectedAddressId = null;
    public $showAddressForm = false;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Load saved addresses
        $this->savedAddresses = $user->addresses()->orderByDesc('is_primary')->get()->toArray();

        // Pre-select primary address
        $primary = collect($this->savedAddresses)->firstWhere('is_primary', true);
        if ($primary) {
            $this->selectAddress($primary['id']);
        } elseif (count($this->savedAddresses) > 0) {
            $this->selectAddress($this->savedAddresses[0]['id']);
        } else {
            $this->showAddressForm = true;
            $this->recipientName = $user->name;
        }
    }

    public function selectAddress(int $addressId): void
    {
        $address = collect($this->savedAddresses)->firstWhere('id', $addressId);
        if ($address) {
            $this->selectedAddressId = $addressId;
            $this->recipientName = $address['recipient_name'];
            $this->phoneNumber = $address['phone_number'];
            $this->fullAddress = $address['full_address'];
            $this->postalCode = $address['postal_code'] ?? '';
            $this->showAddressForm = false;
            
            if (!empty($this->postalCode)) {
                $this->loadShippingRates();
            }
        }
    }

    public function useNewAddress(): void
    {
        $this->selectedAddressId = null;
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $this->recipientName = $user->name;
        $this->phoneNumber = '';
        $this->fullAddress = '';
        $this->postalCode = '';
        $this->showAddressForm = true;
        $this->couriersList = [];
        $this->selectedCourier = '';
        $this->shippingCost = 0;
    }

    public function updatedPostalCode($value)
    {
        if (strlen($value) >= 5) {
            $this->loadShippingRates();
        } else {
            $this->couriersList = [];
            $this->selectedCourier = '';
            $this->shippingCost = 0;
        }
    }

    public function updatedSelectedCourier($value)
    {
        foreach ($this->couriersList as $courier) {
            $code = ($courier['company'] ?? '') . '|' . ($courier['type'] ?? '');
            if ($code === $value) {
                $this->shippingCost = $courier['price'] ?? 0;
                break;
            }
        }
    }

    public function loadShippingRates()
    {
        $biteshipService = app(\App\Services\BiteshipService::class);
        $cartService = app(\App\Services\CartService::class);
        $cart = $cartService->getCart();
        
        $items = [];
        if ($cart) {
            foreach ($cart->items->load(['productVariant.product']) as $item) {
                $items[] = [
                    'name' => $item->productVariant->product->name ?? 'Produk',
                    'price' => $item->productVariant->price ?? 1000,
                    'qty' => $item->qty
                ];
            }
        }

        $rates = $biteshipService->getRates($this->postalCode, null, $items);
        if ($rates && isset($rates['pricing'])) {
            // Sort by price ascending
            $pricing = collect($rates['pricing'])->sortBy('price')->values()->toArray();
            $this->couriersList = $pricing;
            
            if (count($this->couriersList) > 0 && empty($this->selectedCourier)) {
                $first = $this->couriersList[0];
                $this->selectedCourier = ($first['company'] ?? '') . '|' . ($first['type'] ?? '');
                $this->shippingCost = $first['price'] ?? 0;
            }
        } else {
            $this->couriersList = [];
            $this->selectedCourier = '';
            $this->shippingCost = 0;
        }
    }

    public function placeOrder(XenditService $xenditService): void
    {
        $this->validate([
            'recipientName' => 'required|string|max:255',
            'phoneNumber' => 'required|string|max:20',
            'fullAddress' => 'required|string|max:1000',
            'postalCode' => 'nullable|string|max:10',
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getCart();

        if (!$cart || $cart->items->isEmpty()) {
            $this->dispatch('toast', title: 'Gagal', message: 'Keranjang belanja kosong.', type: 'warning');
            return;
        }

        $items = $cart->items->load(['productVariant.product']);

        // Validate stock availability
        foreach ($items as $item) {
            $variant = $item->productVariant;
            if ($variant->stock < $item->qty) {
                $this->dispatch(
                    'toast',
                    title: 'Stok Tidak Cukup',
                    message: "Stok {$variant->product->name} hanya tersisa {$variant->stock}.",
                    type: 'warning'
                );
                return;
            }
        }

        try {
            DB::beginTransaction();

            // Calculate totals
            $totalAmount = $items->sum(fn($item) => $item->qty * $item->productVariant->price);
            $grandTotal = $totalAmount + $this->shippingCost;

            // Generate order number
            $orderNumber = 'TKP-' . now()->format('Ymd') . '-' . str_pad(
                Order::whereDate('created_at', today())->count() + 1,
                3,
                '0',
                STR_PAD_LEFT
            );

            // Snapshot address
            $addressSnapshot = [
                'recipient_name' => $this->recipientName,
                'phone_number' => $this->phoneNumber,
                'full_address' => $this->fullAddress,
                'postal_code' => $this->postalCode,
            ];

            // Save address if new
            if (!$this->selectedAddressId && $this->fullAddress) {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                UserAddress::create([
                    'user_id' => $user->id,
                    'label_address' => 'Alamat Baru',
                    'recipient_name' => $this->recipientName,
                    'phone_number' => $this->phoneNumber,
                    'full_address' => $this->fullAddress,
                    'postal_code' => $this->postalCode,
                    'is_primary' => $user->addresses()->count() === 0,
                ]);
            }

            // Create order
            $order = Order::create([
                'user_id' => Auth::id(),
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'shipping_cost' => $this->shippingCost,
                'discount_amount' => 0,
                'grand_total' => $grandTotal,
                'order_status' => 'PENDING',
                'shipping_address_snapshot' => $addressSnapshot,
            ]);

            // Save Shipping Courier if Biteship used
            if ($this->selectedCourier) {
                $parts = explode('|', $this->selectedCourier);
                DB::table('order_shippings')->insert([
                    'order_id' => $order->id,
                    'courier_company' => $parts[0] ?? '',
                    'courier_type' => $parts[1] ?? '',
                    'tracking_number' => null,
                    'shipping_cost' => $this->shippingCost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Create order items & reduce stock
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item->productVariant->id,
                    'product_variant_type' => \App\Models\ProductVariant::class,
                    'qty' => $item->qty,
                    'price_at_checkout' => $item->productVariant->price,
                    'subtotal' => $item->qty * $item->productVariant->price,
                ]);

                // Reduce stock
                $item->productVariant->decrement('stock', $item->qty);

                // Update parent product stock
                $product = $item->productVariant->product;
                $product->update([
                    'total_stock' => $product->variants()->sum('stock'),
                ]);
            }

            // Clear cart
            $cartService->clear();

            // Integrasi Xendit Invoice
            $invoiceUrl = '';
            if ($xenditService->isConfigured()) {
                // Generate Invoice
                $invoice = $xenditService->createInvoice($order);
                $invoiceUrl = $invoice['invoice_url'] ?? '';

                if ($invoiceUrl) {
                    // Record Payment Request
                    OrderPayment::create([
                        'order_id' => $order->id,
                        'xendit_external_id' => $invoice['external_id'],
                        'xendit_invoice_url' => $invoiceUrl,
                        'amount' => $order->grand_total,
                        'status' => 'PENDING',
                        'payment_payload' => $invoice,
                    ]);
                }
            }

            DB::commit();

            // Redirect ke Xendit Invoice jika berhasil digenerate, jika tidak kembali ke confirmation fallback
            if (!empty($invoiceUrl)) {
                $this->redirect($invoiceUrl);
            } else {
                $this->redirect(route('orders.confirmation', $order), navigate: true);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->dispatch('toast', title: 'Error', message: 'Terjadi kesalahan: ' . $e->getMessage(), type: 'danger');
        }
    }

    #[Layout('layouts.app', ['title' => 'Checkout - TokoPun'])]
    public function render()
    {
        $cart = app(CartService::class)->getCart();
        $items = $cart ? $cart->items->load(['productVariant.product.media', 'productVariant.product.brand']) : collect();

        $totalPrice = $items->sum(fn($item) => $item->qty * $item->productVariant->price);
        $totalItems = $items->sum('qty');

        return view('livewire.pages.checkout', [
            'items' => $items,
            'totalPrice' => $totalPrice,
            'totalItems' => $totalItems,
        ]);
    }
}
