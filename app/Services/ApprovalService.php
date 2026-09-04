<?php

namespace App\Services;

use App\Approvals\Contracts\ApprovalHandlerInterface;
use App\Approvals\Handlers\CustomCashbackHandler;
use App\Approvals\Handlers\OrderCancellationHandler;
use App\Approvals\Handlers\SellPhoneApprovalHandler;
use App\Approvals\Handlers\WarrantyExtensionHandler;
use App\Approvals\Handlers\WarrantyReplacementHandler;
use App\Http\Controllers\ApprovalController;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRule;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ApprovalService
{
    /**
     * Peta handler untuk setiap jenis request approval.
     */
    protected array $handlers = [
        'ORDER_CANCELLATION'   => OrderCancellationHandler::class,
        'SELL_PHONE_APPROVAL'  => SellPhoneApprovalHandler::class,
        'WARRANTY_EXTENSION'   => WarrantyExtensionHandler::class,
        'WARRANTY_REPLACEMENT' => WarrantyReplacementHandler::class,
        'CUSTOM_CASHBACK'      => CustomCashbackHandler::class,
    ];

    /**
     * Mengambil daftar aturan approval yang cocok secara hierarkis (Spesifik BU -> Global Fallback).
     */
    public function resolveRules(string $module, ?int $businessUnitId = null, ?float $amount = null, ?int $branchId = null): Collection
    {
        // 1. Coba cari aturan spesifik untuk Business Unit terkait
        if ($businessUnitId) {
            $buRules = ApprovalRule::with('role')
                ->where('module', $module)
                ->where('business_unit_id', $businessUnitId)
                ->when($amount !== null, function ($q) use ($amount) {
                    $q->where('min_amount', '<=', $amount)
                      ->where(function ($sq) use ($amount) {
                          $sq->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
                      });
                })
                ->when($branchId !== null, function ($q) use ($branchId) {
                    $q->where(function ($sq) use ($branchId) {
                        $sq->whereNull('branch_id')->orWhere('branch_id', $branchId);
                    });
                })
                ->orderBy('level', 'asc')
                ->get();

            if ($buRules->isNotEmpty()) {
                return $buRules;
            }
        }

        // 2. Fallback: Ambil aturan Global Default (business_unit_id IS NULL)
        return ApprovalRule::with('role')
            ->where('module', $module)
            ->whereNull('business_unit_id')
            ->when($amount !== null, function ($q) use ($amount) {
                $q->where('min_amount', '<=', $amount)
                  ->where(function ($sq) use ($amount) {
                      $sq->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
                  });
            })
            ->when($branchId !== null, function ($q) use ($branchId) {
                $q->where(function ($sq) use ($branchId) {
                    $sq->whereNull('branch_id')->orWhere('branch_id', $branchId);
                });
            })
            ->orderBy('level', 'asc')
            ->get();
    }

    /**
     * Menghitung level tertinggi yang dibutuhkan untuk modul & konteks transaksi.
     */
    public function getRequiredLevel(string $module, ?int $businessUnitId = null, ?float $amount = null, ?int $branchId = null): int
    {
        $rules = $this->resolveRules($module, $businessUnitId, $amount, $branchId);
        return (int) ($rules->max('level') ?? 0);
    }

    /**
     * Mengambil spesifik rule untuk level tertentu dengan prioritas BU -> Global.
     */
    public function getRuleForLevel(string $module, int $level, ?int $businessUnitId = null): ?ApprovalRule
    {
        if ($businessUnitId) {
            $rule = ApprovalRule::with('role')
                ->where('module', $module)
                ->where('level', $level)
                ->where('business_unit_id', $businessUnitId)
                ->first();

            if ($rule) {
                return $rule;
            }
        }

        return ApprovalRule::with('role')
            ->where('module', $module)
            ->where('level', $level)
            ->whereNull('business_unit_id')
            ->first();
    }

    /**
     * Membuat request approval baru secara terpusat dan aman.
     */
    public function createRequest(array $data): ApprovalRequest
    {
        $approvable = $data['approvable'] ?? null;
        $requestType = $data['request_type'];
        $requestedBy = $data['requested_by'] ?? Auth::id();
        if ($requestedBy instanceof User) {
            $requestedBy = $requestedBy->id;
        }

        // Resolusi Business Unit
        $businessUnitId = $data['business_unit_id'] ?? null;
        if (!$businessUnitId && $approvable && isset($approvable->business_unit_id)) {
            $businessUnitId = $approvable->business_unit_id;
        }
        if (!$businessUnitId) {
            $user = User::find($requestedBy);
            $businessUnitId = $user ? $user->getActiveBusinessUnitId() : 1;
        }

        // Resolusi Branch
        $branchId = $data['branch_id'] ?? null;
        if (!$branchId && $approvable && isset($approvable->branch_id)) {
            $branchId = $approvable->branch_id;
        }
        if (!$branchId) {
            $user = User::find($requestedBy);
            $branchId = $user?->branch_id;
        }

        $totalAmount = isset($data['total_amount']) ? (float) $data['total_amount'] : null;

        // Hitung level yang dibutuhkan
        $requiredLevel = $data['required_level'] ?? $this->getRequiredLevel($requestType, $businessUnitId, $totalAmount, $branchId);
        if ($requiredLevel <= 0) {
            $requiredLevel = 1; // Minimal 1 level jika ada approval
        }

        $approvableType = $approvable instanceof Model ? get_class($approvable) : ($data['approvable_type'] ?? User::class);
        $approvableId = $approvable instanceof Model ? $approvable->getKey() : ($data['approvable_id'] ?? $requestedBy);

        $request = ApprovalRequest::create([
            'approvable_type'  => $approvableType,
            'approvable_id'    => $approvableId,
            'request_type'     => $requestType,
            'requested_by'     => $requestedBy,
            'business_unit_id' => $businessUnitId,
            'branch_id'        => $branchId,
            'total_amount'     => $totalAmount,
            'reason'           => $data['reason'] ?? null,
            'payload'          => $data['payload'] ?? null,
            'status'           => 'PENDING',
            'required_level'   => $requiredLevel,
            'current_level'    => 0,
        ]);

        // Pemicu notifikasi Telegram n8n
        ApprovalController::sendTelegramNotification($request);

        return $request;
    }

    /**
     * Mengambil instance handler yang sesuai untuk request_type.
     */
    public function getHandler(string $requestType): ApprovalHandlerInterface
    {
        if (!isset($this->handlers[$requestType])) {
            throw new Exception("Handler untuk tipe approval '{$requestType}' belum terdaftar.");
        }

        return app($this->handlers[$requestType]);
    }
}
