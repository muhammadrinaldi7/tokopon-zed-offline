<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Mengambil daftar rekening bank perusahaan untuk transfer manual di Mobile App.
     */
    public function index(Request $request): JsonResponse
    {
        $businessUnitId = $request->query('business_unit_id');

        $query = PaymentMethod::query()
            ->where('is_active', true)
            ->where('is_visible_mobile', true)
            ->with('businessUnit:id,name,code');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        $methods = $query->orderBy('name')->get();

        $data = $methods->map(function ($pm) {
            return [
                'id' => $pm->id,
                'name' => $pm->name,
                'bank_name' => $pm->bank_name ?: $pm->name,
                'account_number' => $pm->account_number,
                'account_owner' => $pm->account_owner,
                'business_unit' => $pm->businessUnit ? [
                    'id' => $pm->businessUnit->id,
                    'name' => $pm->businessUnit->name,
                ] : null,
                'instructions' => [
                    "Transfer tepat hingga 3 digit terakhir sesuai total tagihan.",
                    "Simpan bukti transfer dan unggah melalui aplikasi setelah transaksi dibuat.",
                    "Pembayaran akan diverifikasi oleh tim kami dalam jam operasional.",
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar rekening pembayaran berhasil dimuat.',
            'data' => $data,
        ]);
    }
}
