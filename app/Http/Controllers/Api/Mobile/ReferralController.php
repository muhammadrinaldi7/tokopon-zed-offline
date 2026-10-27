<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CommissionWithdrawal;
use App\Models\ReferralCommission;
use App\Services\ReferralService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Validasi kode referral secara real-time untuk mobile form.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $code = $request->query('code') ?: $request->input('code');
        $currentUser = $request->user();

        $result = $this->referralService->validateCode($code, $currentUser);

        return response()->json([
            'success' => $result['valid'],
            'message' => $result['message'],
            'data' => $result['valid'] ? [
                'code' => $result['code'],
                'referrer_name' => $result['referrer_name'],
            ] : null,
        ], $result['valid'] ? 200 : 422);
    }

    /**
     * Ringkasan dompet komisi, statistik teman yang diajak, dan info referral.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $summary = $this->referralService->getWalletSummary($user);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Riwayat komisi & riwayat penarikan dana user.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $commissions = ReferralCommission::with(['referee:id,name', 'order:id,order_number,total_amount,order_status'])
            ->where('referrer_id', $user->id)
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'order_number' => $c->order?->order_number,
                    'friend_name' => $c->referee?->name ?? 'Teman Tokopon',
                    'order_amount' => (float) $c->order_amount,
                    'commission_amount' => (float) $c->commission_amount,
                    'commission_rate' => (float) $c->commission_rate,
                    'commission_type' => $c->commission_type,
                    'status' => $c->status,
                    'hold_until' => $c->hold_until?->toIso8601String(),
                    'is_hold_released' => $c->hold_until ? $c->hold_until->isPast() : false,
                    'created_at' => $c->created_at?->toIso8601String(),
                ];
            });

        $withdrawals = CommissionWithdrawal::with('bankAccount')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'amount' => (float) $w->amount,
                    'net_amount' => (float) $w->net_amount,
                    'status' => $w->status,
                    'bank_name' => $w->bankAccount?->bank_name,
                    'account_number' => $w->bankAccount?->account_number,
                    'account_name' => $w->bankAccount?->account_name,
                    'proof_url' => $w->proof_attachment_url,
                    'notes' => $w->notes,
                    'created_at' => $w->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'commissions' => $commissions,
                'withdrawals' => $withdrawals,
            ],
        ]);
    }

    /**
     * Pengajuan penarikan dana komisi ke rekening bank.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'bank_account_id' => 'required|integer|exists:user_bank_accounts,id',
            'amount' => 'required|numeric|min:1000',
        ], [
            'bank_account_id.required' => 'Pilih rekening bank tujuan.',
            'amount.required' => 'Nominal penarikan wajib diisi.',
            'amount.min' => 'Nominal penarikan tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $withdrawal = $this->referralService->requestWithdrawal(
                $user,
                (int) $request->input('bank_account_id'),
                (float) $request->input('amount')
            );

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan penarikan saldo berhasil dikirim. Admin akan segera memproses transfer.',
                'data' => [
                    'id' => $withdrawal->id,
                    'amount' => (float) $withdrawal->amount,
                    'status' => $withdrawal->status,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}