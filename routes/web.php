<?php

use App\Ai\Agents\DatabaseAnalyzerAgent;
use App\Http\Controllers\AiReportController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\WarrantyReportPdfController;
use App\Livewire\Admin\Accurate\AccurateInvoiceExport;
use App\Livewire\Admin\Employe\EmployeManage;
use App\Livewire\Admin\Vendor\VendorManage;
// use App\Livewire\Admin\Pos\CekStock;
use App\Livewire\Admin\Reporting\Dashboard;
use App\Livewire\Admin\Users\UserOperational;
use App\Livewire\Pages\SellPhone;
use App\Livewire\Pages\SellPhoneHistory;
use App\Livewire\Pages\TradeIn;
use App\Livewire\Pages\UserProfile;
use App\Livewire\Zoffline\Qc\ActivationList;
use App\Livewire\Zoffline\Reporting\WarrantyReport;
use App\Livewire\Zoffline\Reporting\MonitoringKasir;
use App\Livewire\Zoffline\Warehouse\CekStock;
use App\Livewire\Zoffline\Warehouse\CheckSerialNumber;
use App\Livewire\Zoffline\Warehouse\SerialNumberHistory;
use App\Livewire\Zoffline\Warranty\WarrantyClaim;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Exceptions\ProviderOverloadedException;

// ─── POS Landing Page (requires auth + admin role) ──────────────
// Route::get('/tesrenaldi', \App\Livewire\Admin\Pos\PointOfSale::class)->middleware(['auth', 'admin'])->name('/');
Route::middleware(['auth'])->group(function () {
    // AI Assistant Routes
    Route::get('/ai/export-report', [AiReportController::class, 'download'])->name('ai.export.report');
    Route::prefix('admin/ai-assistant')->name('admin.ai-assistant.')->group(function () {
        Route::get('/', \App\Livewire\Admin\AiAssistant\Chat::class)->name('index');
        Route::get('/settings', \App\Livewire\Admin\AiAssistant\Settings::class)->name('settings');
    });

    Route::get('/', \App\Livewire\Zoffline\Home::class)->name('zoffline');
    Route::get('/zoffline/monitoring-kasir', MonitoringKasir::class)->name('zoffline.monitoring-kasir')->middleware('can:monitoring-kasir');
    Route::get('/zoffline/pos', \App\Livewire\Zoffline\Pos\Pos::class)->name('zoffline.pos')->middleware('can:view-pos');
    Route::get('/zoffline/pos/open-shift', \App\Livewire\Zoffline\Pos\OpenShift::class)->name('zoffline.pos.open-shift')->middleware('can:view-pos');
    Route::get('/zoffline/pos/closing-kasir', \App\Livewire\Zoffline\Pos\ClosingKasir::class)->name('zoffline.pos.closing-kasir')->middleware('can:view-pos');
    Route::get('/zoffline/riwayat', \App\Livewire\Zoffline\Reporting\RiwayatPenjualan::class)->name('zoffline.pos.riwayat')->middleware('can:view-pos');
    Route::get('/zoffline/riwayat-kasir', \App\Livewire\Admin\Pos\RiwayatKasir::class)->name('zoffline.riwayat-kasir')->middleware('can:view-riwayat-kasir');
    Route::get('/zoffline/trade-in', \App\Livewire\Zoffline\TradeIn\TradeIn::class)->name('zoffline.trade-in')->middleware('can:trade-in');
    Route::get('/zoffline/sell-phone', \App\Livewire\Zoffline\SellPhone\SellPhone::class)->name('zoffline.sell-phone')->middleware('can:sell-phone');
    Route::get('/zoffline/sell-phone-history', \App\Livewire\Zoffline\SellPhone\History::class)->name('zoffline.sell-phone-history')->middleware('can:sell-phone-history');
    Route::get('/zoffline/warranty-activation', \App\Livewire\Zoffline\Qc\WarrantyActivation::class)->name('zoffline.warranty-activation')->middleware('can:warranty-activation');
    Route::get('/zoffline/warranty-claim', WarrantyClaim::class)->name('zoffline.warranty-claim')->middleware('can:warranty-activation');
    Route::get('/zoffline/cek-stock', CekStock::class)->name('zoffline.cek-stock')->middleware('can:view-stock');
    Route::get('/zoffline/reporting', \App\Livewire\Zoffline\Reporting\Reporting::class)->name('zoffline.reporting')->middleware('can:view-reporting');
    Route::get('/zoffline/reporting/cek-harga', \App\Livewire\Zoffline\Reporting\CheckPrice::class)->name('zoffline.reporting.cek-harga')->middleware('can:view-reporting');
    Route::get('/zoffline/reporting/pembelian', \App\Livewire\Zoffline\Reporting\LaporanPembelian::class)->name('zoffline.reporting.pembelian')->middleware('can:view-reporting');
    Route::get('/zoffline/reporting/monitoring-kasir-report', \App\Livewire\Zoffline\Reporting\MonitoringKasirReport::class)->name('reporting.monitoring-kasir-report')->middleware('can:report-monitoring-kasir');
    Route::get('/zoffline/check-serial-number', CheckSerialNumber::class)->name('zoffline.check-serial-number')->middleware('can:view-warehouse-stocks');
    Route::get('/zoffline/check-serial-number/history', SerialNumberHistory::class)->name('zoffline.warehouse.sn-history')->middleware('can:view-warehouse-stocks');
    Route::get('/zoffline/qc/list-activation', ActivationList::class)->name('zoffline.qc.list-activation');

    Route::get('/zoffline/qc-returns', \App\Livewire\Zoffline\Qc\ReturnIndex::class)->name('zoffline.qc-returns');
    Route::get('/zoffline/qc-returns/{claim}/inspect', \App\Livewire\Zoffline\Qc\ReturnInspect::class)->name('zoffline.qc-returns.inspect');
    Route::get('/zoffline/device-passport', \App\Livewire\Zoffline\DevicePassport::class)->name('zoffline.device-passport');

    // Zoffline Inbound PO
    Route::middleware('can:manage-inbound')->group(function () {
        Route::get('/zoffline/inbound', \App\Livewire\Zoffline\Inbound\Index::class)->name('zoffline.inbound.index');
        Route::get('/zoffline/inbound/{po}/scan', \App\Livewire\Zoffline\Inbound\Scan::class)->name('zoffline.inbound.scan');
    });

    // Zoffline Approvals & Settings
    Route::get('/zoffline/approvals', \App\Livewire\Admin\Approvals\Index::class)->name('zoffline.approvals.index');
    Route::get('/zoffline/approval-rules', \App\Livewire\Admin\Settings\ApprovalRule\Index::class)->name('zoffline.approval-rules.index')->middleware('can:manage-settings');

    // Reporting
    Route::prefix('reporting')->name('reporting.')->middleware('can:view-reporting')->group(function () {
        Route::get('/sales', \App\Livewire\Zoffline\Reporting\SalesReport::class)->name('sales');
        Route::get('/promo', \App\Livewire\Zoffline\Reporting\PromoReport::class)->name('promo');
        Route::get('/products', \App\Livewire\Zoffline\Reporting\ProductReport::class)->name('products');
        Route::get('/stock', \App\Livewire\Zoffline\Reporting\StockReport::class)->name('stock');
        Route::get('/laporan-stok', \App\Livewire\Zoffline\Reporting\LaporanStok::class)->name('laporan-stok');
        Route::get('/staff', \App\Livewire\Zoffline\Reporting\StaffReport::class)->name('staff');
        Route::get('/laba-rugi', \App\Livewire\Zoffline\Reporting\IncomeStatement::class)->name('income-statement');
        Route::get('/profit', \App\Livewire\Zoffline\Reporting\ProfitReport::class)->name('profit');
        Route::get('/closing-kasir', \App\Livewire\Zoffline\Reporting\ClosingKasirReport::class)->name('closing-kasir');
        Route::get('/pembatalan', \App\Livewire\Zoffline\Reporting\CancellationReport::class)->name('pembatalan');
        Route::get('/sales-order', \App\Livewire\Zoffline\Reporting\SalesOrderReport::class)->name('sales-order');
        Route::get('/warranty', WarrantyReport::class)->name('warranty');
        Route::get('/warranty/pdf', [WarrantyReportPdfController::class, 'export'])->name('warranty.pdf');
        Route::get('/return-report', \App\Livewire\Zoffline\Reporting\ReturnReport::class)->name('return-report');
        Route::get('/dashboard', \App\Livewire\Zoffline\Reporting\Dashboard::class)->name('dashboard-bm');
        Route::get('/laporan-pembayaran', \App\Livewire\Zoffline\Reporting\InvoiceReport::class)->name('pembayaran');
        Route::get('/order-issues', \App\Livewire\Admin\Orders\OrderIssuesIndex::class)->name('order-issues');
        Route::get('/sell-phone-issues', \App\Livewire\Admin\SellPhone\SellPhoneIssuesIndex::class)->name('sell-phone-issues');
    });
});

// ─── Trade In & Sell Phone Client Pages (accessible by authenticated users, e.g. FL or customer) ───
Route::middleware(['auth'])->group(function () {
    Route::get('/sell-phone', SellPhone::class)->name('sell-phone');
    Route::get('/sell-phone-history', SellPhoneHistory::class)->name('sell-phone-history');
    Route::get('/sell-phone/{sellPhone}/detail', \App\Livewire\Pages\SellPhoneDetail::class)->name('sell-phone.show');

    Route::get('/trade-in/{product:slug?}', TradeIn::class)->name('trade-in');
    Route::get('/trade-in-history', \App\Livewire\Pages\TradeInHistory::class)->name('trade-in-history');
    Route::get('/trade-in/{product}/submit', \App\Livewire\Pages\SubmitTradeIn::class)->name('trade-in.submit');
    Route::get('/trade-in/{tradeIn}/detail', \App\Livewire\Pages\TradeInDetail::class)->name('trade-ins.show');
    // Route::get('/orders', \App\Livewire\Pages\OrderHistory::class)->name('orders.index');
    // Route::get('/orders/{order}', \App\Livewire\Pages\OrderDetail::class)->name('orders.show');
    // Route::get('/orders/{order}/confirmation', \App\Livewire\Pages\OrderConfirmation::class)->name('orders.confirmation');

    Route::get('/profile', UserProfile::class)->name('profile');
});

// ─── Admin Routes (requires auth + admin role) ──────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Route::get('/pos', \App\Livewire\Admin\Pos\PointOfSale::class)->name('pos')->middleware('can:view-pos');
    Route::livewire('/dashboard', 'pages::admin.dashboard')->name('dashboard')->middleware('can:view_dashboard');
    // QC & Inspeksi
    Route::get('/purchase-invoice-export', AccurateInvoiceExport::class)->name('purchase.invoice.export');

    // Administrator
    Route::livewire('/users', 'pages::admin.user-management')->name('users')->middleware('can:manage-users');
    Route::livewire('/roles', 'pages::admin.role-permission')->name('roles')->middleware('can:manage-users');
    Route::get('/user/operational', UserOperational::class)->name('user.operational')->middleware('can:manage-users');
    Route::get('/user/employes', EmployeManage::class)->name('user.employes')->middleware('can:manage-users');
    Route::get('/user/vendors', VendorManage::class)->name('user.vendors')->middleware('can:manage-users');

    // Katalog Pusat
    Route::get('/products', \App\Livewire\Admin\Products\ProductManagement::class)->name('products')->middleware('can:manage-new-catalog');
    Route::get('/second-products', \App\Livewire\Admin\Products\SecondProductManagement::class)->name('second-products')->middleware('can:manage-second-catalog');
    Route::get('/categories', \App\Livewire\Admin\Products\CategoryManagement::class)->name('categories')->middleware('can:manage-categories');
    Route::get('/brands', \App\Livewire\Admin\Products\BrandManagement::class)->name('brands')->middleware('can:manage-brands');
    Route::get('/products/{product}/variants', \App\Livewire\Admin\Products\VariantManagement::class)->name('products.variants')->middleware('can:manage-new-catalog');
    Route::get('/second-products/{product}/variants', \App\Livewire\Admin\Products\SecondVariantManagement::class)->name('second-products.variants')->middleware('can:manage-second-catalog');
    Route::get('/accurate-products', \App\Livewire\Admin\Accurate\ProductAccurateManagement::class)->name('accurate-products')->middleware('can:manage-accurate-products');
    Route::get('/accurate-customers', \App\Livewire\Admin\Accurate\CustomerAccurateManagement::class)->name('accurate-customers')->middleware('can:manage-accurate-customers');
    Route::get('/accurate-sync-sn', \App\Livewire\Admin\Accurate\SerialNumberSync::class)->name('accurate-sync-sn')->middleware('can:manage-accurate-products');
    Route::get('/warehouse-stocks', \App\Livewire\Admin\Warehouse\StockManagement::class)->name('warehouse-stocks')->middleware('can:view-warehouse-stocks');



    // Pesanan
    Route::get('/orders', \App\Livewire\Admin\Orders\OrderManagement::class)->name('orders.management')->middleware('can:manage-orders');
    Route::get('/orders/issues', \App\Livewire\Admin\Orders\OrderIssuesIndex::class)->name('orders.issues')->middleware('can:manage-orders');
    Route::get('/orders/import-draft', \App\Livewire\Admin\Orders\ImportDraft::class)->name('orders.import-draft')->middleware('can:manage-orders');
    Route::get('/orders/reset-to-draft', \App\Livewire\Admin\Orders\ResetToDraft::class)->name('orders.reset-to-draft')->middleware('can:manage-orders');

    // Customer Deposits
    Route::get('/customer-deposits', \App\Livewire\Admin\Finance\CustomerDeposit\Index::class)->name('customer-deposits.index')->middleware('can:manage-orders');

    // Finance Dashboard
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/warranty-return', \App\Livewire\Admin\Finance\WarrantyReturn::class)->name('warranty-return');
        Route::get('/warranty-return/{claim}', \App\Livewire\Admin\Finance\WarrantyReturnShow::class)->name('warranty-return.show');
    });

    // Sales Order (Mini Accurate)
    Route::prefix('sales-orders')->name('sales-orders.')->middleware('can:manage-orders')->group(function () {
        Route::get('/', \App\Livewire\Admin\Orders\SalesOrder\Index::class)->name('index');
        Route::get('/create', \App\Livewire\Admin\Orders\SalesOrder\Create::class)->name('create');
        Route::get('/{order}', \App\Livewire\Admin\Orders\SalesOrder\Show::class)->name('show');
    });

    // Settings
    Route::get('/settings/system-reset', \App\Livewire\Admin\Settings\SystemReset::class)->name('settings.system-reset')->middleware('can:manage-settings');
    Route::get('/settings/system-reset/export', [\App\Http\Controllers\SystemResetExportController::class, 'exportSo'])->name('settings.system-reset.export-so')->middleware('can:manage-settings');
    Route::get('/settings/telegram', \App\Livewire\Admin\Settings\TelegramConnection::class)->name('settings.telegram')->middleware('can:manage-settings');
    Route::get('/settings/business-units', \App\Livewire\Admin\Settings\BusinessUnitIndex::class)->name('settings.business-units')->middleware('can:manage-settings');
    Route::get('/settings/payment-methods', \App\Livewire\Admin\Settings\PaymentMethodIndex::class)->name('settings.payment-methods')->middleware('can:manage-settings');
    Route::get('/settings/shipping', \App\Livewire\Admin\Settings\ShippingSettings::class)->name('settings.shipping')->middleware('can:manage-settings');
    Route::get('/settings/catalog', \App\Livewire\Admin\Settings\CatalogSettings::class)->name('settings.catalog')->middleware('can:manage-settings');
    Route::get('/settings/warehouse', \App\Livewire\Admin\Settings\Warehouse\Index::class)->name('settings.warehouse')->middleware('can:manage-settings');
    Route::livewire('/settings/pos', 'pages::admin.settings.pos-settings')->name('settings.pos')->middleware('can:manage-settings');
    Route::get('/settings/approval-rules', \App\Livewire\Admin\Settings\ApprovalRule\Index::class)->name('settings.approval-rules')->middleware('can:manage-settings');
    Route::get('/inventory/stock-adjustment', \App\Livewire\Admin\Inventory\StockAdjustment\Index::class)->name('adjustment.index')->middleware('can:manage-settings');

    // Approvals
    Route::get('/approvals', \App\Livewire\Admin\Approvals\Index::class)->name('approvals.index');


    Route::prefix('promos')->name('promos.')->middleware('can:manage-promos')->group(function () {
        Route::get('/', App\Livewire\Admin\Promo\Index::class)->name('index');
        Route::get('/create', App\Livewire\Admin\Promo\Form::class)->name('create');
        Route::get('/{promo}/edit', App\Livewire\Admin\Promo\Form::class)->name('edit');
    });

    Route::prefix('manual-discount')->name('manual-discount.')->middleware('can:manage-promos')->group(function () {
        Route::get('/', App\Livewire\Admin\ManualDiscount\Index::class)->name('index');
        Route::get('/create', App\Livewire\Admin\ManualDiscount\Form::class)->name('create');
        Route::get('/{id}/edit', App\Livewire\Admin\ManualDiscount\Form::class)->name('edit');
    });

    Route::get('/trade-ins', App\Livewire\Admin\TradeIn\Index::class)->name('trade-ins.index')->middleware('can:manage-trade-in');
    Route::get('/trade-ins/{tradeIn}', App\Livewire\Admin\TradeIn\Show::class)->name('trade-ins.show')->middleware('can:manage-trade-in');

    Route::get('/sell-phones', App\Livewire\Admin\SellPhone\Index::class)->name('sell-phones.index')->middleware('can:manage-trade-in');
    Route::get('/sell-phones/issues', App\Livewire\Admin\SellPhone\SellPhoneIssuesIndex::class)->name('sell-phones.issues')->middleware('can:manage-trade-in');
    Route::get('/sell-phones/{sellPhone}', App\Livewire\Admin\SellPhone\Show::class)->name('sell-phones.show')->middleware('can:manage-trade-in');

    Route::prefix('buyback')->name('buyback.')->middleware('can:manage-buyback')->group(function () {
        Route::get('/devices', App\Livewire\Admin\Buyback\DeviceIndex::class)->name('index');
        Route::get('/mapped-devices', App\Livewire\Admin\Buyback\MappedDeviceIndex::class)->name('mapped-devices');
        Route::get('/mapped-devices/{tier_id}', App\Livewire\Admin\Buyback\MappedDeviceShow::class)->name('mapped-devices.show');
        Route::get('/devices/create', App\Livewire\Admin\Buyback\DeviceForm::class)->name('create');
        Route::get('/tiers', App\Livewire\Admin\Buyback\TierIndex::class)->name('tiers');
    });

    Route::prefix('warranty')->name('warranty.')->group(function () {
        Route::get('/policies', \App\Livewire\Admin\Warranty\PolicyManagement::class)->name('policies');
        Route::get('/claims', \App\Livewire\Admin\Warranty\ClaimManagement::class)->name('claims');
        Route::get('/pending-activations', \App\Livewire\Admin\Warranty\PendingActivationManagement::class)->name('pending-activations');
    });

    Route::prefix('qc')->name('qc.')->group(function () {
        Route::get('/templates', App\Livewire\Admin\Qc\TemplateIndex::class)->name('templates')->middleware('can:manage-qc-templates');
        Route::get('/inbound', App\Livewire\Admin\Qc\VendorInboundQc::class)->name('inbound')->middleware('can:manage-qc-inspections');
        Route::get('/device', App\Livewire\Admin\Qc\DeviceSearch::class)->name('device-search')->middleware('can:manage-qc-inspections');
        Route::get('/device/{imei}', App\Livewire\Admin\Qc\DevicePassport::class)->name('device-passport')->middleware('can:manage-qc-inspections');
        Route::get('/inspect/{secondProductVariant}', App\Livewire\Admin\Qc\InspectionForm::class)->name('inspect')->middleware('can:manage-qc-inspections');
    });
});

// Route::get('/qc/device/{imei}', App\Livewire\Pages\PublicDeviceQc::class)->name('public.device-qc');

// ─── CS Chat Route (requires auth + admin middleware + cs role) ──
Route::livewire('/admin/cs-chat', 'pages::cs-dashboard')
    ->middleware(['auth', 'admin', 'can:access-cs-chat'])
    ->name('admin.cs-chat');

// ─── Logout ─────────────────────────────────────────────────────
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

// ─── Google OAuth Routes ────────────────────────────────────────
Route::get('/auth/google', [\App\Http\Controllers\GoogleCallbackController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleCallbackController::class, 'handleGoogleCallback'])->name('auth.google.callback');


// ─── Erzap Webhook Routes (Dynamic Source Support) ────────────────
Route::post('/web_service/import_produk_json/new.json', [\App\Http\Controllers\Api\ErzapProductController::class, 'store']);
Route::post('/web_service/import_produk_json/new', [\App\Http\Controllers\Api\ErzapProductController::class, 'store']);
Route::post('/web_service/sinkronisasi_stok/new', [\App\Http\Controllers\Api\ErzapProductController::class, 'syncStock']);

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/ai-test', function () {
        $response = \Laravel\Ai\agent(instructions: 'Anda adalah asisten AI untuk Chatbot Tokopon')
            ->prompt('Buatkan pantun 2 baris tentang tokopon');

        return $response;
    });

    Route::get('/ai-analyze-db', function () {
        $agent = new DatabaseAnalyzerAgent();

        try {
            $response = $agent->prompt('Tolong hitung ada berapa jumlah data di dalam tabel users.');
            return $response;
        } catch (ProviderOverloadedException $e) {
            // Tangkap error jika server Gemini kepenuhan
            return response()->json([
                'status' => 'error',
                'message' => 'Maaf, server AI sedang sibuk memproses terlalu banyak permintaan. Silakan coba beberapa detik lagi.'
            ], 503);
        } catch (\Exception $e) {
            // Tangkap error umum lainnya
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    });
});
