<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    /**
     * Tampilan utama Rekening Pembayaran Bank (Inertia React)
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $filterTab = $request->input('tab', 'all'); // all, mobile_only, non_mobile
        $filterBu = $request->input('bu', null);

        $query = PaymentMethod::with('businessUnit:id,name,code');

        if (!empty($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('bank_name', 'like', "%{$s}%")
                    ->orWhere('account_number', 'like', "%{$s}%")
                    ->orWhere('account_owner', 'like', "%{$s}%");
            });
        }

        if ($filterTab === 'mobile_only') {
            $query->where('is_visible_mobile', true);
        } elseif ($filterTab === 'non_mobile') {
            $query->where('is_visible_mobile', false);
        }

        if (!empty($filterBu)) {
            $query->where('business_unit_id', $filterBu);
        }

        $paymentMethods = $query->orderBy('business_unit_id')
            ->orderBy('bank_name')
            ->get()
            ->map(function ($pm) {
                return [
                    'id' => $pm->id,
                    'name' => $pm->name,
                    'bank_name' => $pm->bank_name ?: $pm->name,
                    'account_number' => $pm->account_number,
                    'account_owner' => $pm->account_owner,
                    'accurate_bank_no' => $pm->accurate_bank_no,
                    'business_unit_id' => $pm->business_unit_id,
                    'business_unit_name' => $pm->businessUnit?->name ?? 'Semua Store',
                    'is_active' => (bool) $pm->is_active,
                    'is_visible_mobile' => (bool) $pm->is_visible_mobile,
                ];
            });

        // Metrik Ringkasan
        $stats = [
            'total_methods' => $paymentMethods->count(),
            'mobile_active' => $paymentMethods->where('is_visible_mobile', true)->where('is_active', true)->count(),
            'total_active' => $paymentMethods->where('is_active', true)->count(),
        ];

        return Inertia::render('Payments/Index', [
            'paymentMethods' => $paymentMethods,
            'stats' => $stats,
            'popularBanks' => [
                'BCA' => 'Bank Central Asia (BCA)',
                'MANDIRI' => 'Bank Mandiri',
                'BRI' => 'Bank Rakyat Indonesia (BRI)',
                'BNI' => 'Bank Negara Indonesia (BNI)',
                'BSI' => 'Bank Syariah Indonesia (BSI)',
                'CIMB' => 'CIMB Niaga',
                'PERMATA' => 'Bank Permata',
                'JAGO' => 'Bank Jago',
                'QRIS' => 'QRIS Toko (Statis/Dinamis)',
            ],
            'filters' => [
                'search' => $search,
                'tab' => $filterTab,
                'bu' => $filterBu,
            ],
        ]);
    }

    /**
     * Tambah rekening pembayaran baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_owner' => 'required|string|max:255',
            'business_unit_id' => 'required|exists:business_units,id',
            'accurate_bank_no' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_visible_mobile' => 'boolean',
        ]);

        $validated['accurate_customer_no'] = 'UMUM';
        $validated['mdr_percentage'] = 0;
        $validated['category'] = 'NON-TUNAI';

        PaymentMethod::create($validated);

        return redirect()->back()->with('success', "Rekening {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Perbarui rekening pembayaran yang ada
     */
    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_owner' => 'required|string|max:255',
            'business_unit_id' => 'required|exists:business_units,id',
            'accurate_bank_no' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_visible_mobile' => 'boolean',
        ]);

        $paymentMethod->update($validated);

        return redirect()->back()->with('success', "Rekening {$paymentMethod->name} berhasil diperbarui.");
    }

    /**
     * Toggle status aktif rekening
     */
    public function toggleActive(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->update(['is_active' => !$paymentMethod->is_active]);

        $statusStr = $paymentMethod->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'is_active' => $paymentMethod->is_active,
            'message' => "Rekening {$paymentMethod->name} berhasil {$statusStr}.",
        ]);
    }

    /**
     * Toggle visibilitas di aplikasi mobile
     */
    public function toggleMobile(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->update(['is_visible_mobile' => !$paymentMethod->is_visible_mobile]);

        $statusStr = $paymentMethod->is_visible_mobile ? 'ditampilkan di mobile' : 'disembunyikan dari mobile';

        return response()->json([
            'success' => true,
            'is_visible_mobile' => $paymentMethod->is_visible_mobile,
            'message' => "Rekening {$paymentMethod->name} berhasil {$statusStr}.",
        ]);
    }

    /**
     * Hapus rekening
     */
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $name = $paymentMethod->name;
        $paymentMethod->delete();

        return redirect()->back()->with('success', "Rekening {$name} berhasil dihapus.");
    }
}
