<?php

namespace App\Livewire\Zoffline\SellPhone;

use App\Models\Branch;
use App\Models\SellPhone;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\SellPhoneReceiptMail;

#[Layout('layouts.z', ['title' => 'Riwayat Jual HP'])]
class History extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterPayment = '';
    public string $filterStartDate = '';
    public string $filterEndDate = '';
    public string $filterBranchId = '';

    public bool $showReceiptModal = false;
    public ?SellPhone $selectedSell = null;

    public bool $showProofModal = false;
    public string $proofImageUrl = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterPayment()
    {
        $this->resetPage();
    }

    public function updatingFilterStartDate()
    {
        $this->resetPage();
    }

    public function updatingFilterEndDate()
    {
        $this->resetPage();
    }

    public function updatingFilterBranchId()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterPayment', 'filterStartDate', 'filterEndDate', 'filterBranchId']);
        $this->resetPage();
    }

    public function setPaymentFilter(string $status)
    {
        $this->filterPayment = $status;
        $this->resetPage();
    }

    public function showReceipt(SellPhone $sellPhone)
    {
        $this->selectedSell = $sellPhone->load(['handledBy', 'user.profile', 'user.bankAccounts', 'businessUnit']);
        $this->showReceiptModal = true;
    }

    public function closeReceipt()
    {
        $this->showReceiptModal = false;
        $this->selectedSell = null;
    }

    public function previewProof(string $url)
    {
        $this->proofImageUrl = $url;
        $this->showProofModal = true;
    }

    public function closeProofModal()
    {
        $this->showProofModal = false;
        $this->proofImageUrl = '';
    }

    private function generateReceiptPdf(SellPhone $sellPhone)
    {
        $pdf = Pdf::loadView('pdf.sell-phone-receipt', ['sellPhone' => $sellPhone]);

        // 80mm thermal printer width
        $customPaper = array(0, 0, 226.77, 1000);
        $pdf->setPaper($customPaper, 'portrait');

        return $pdf;
    }

    public function sendReceiptToQontak()
    {
        if (!$this->selectedSell) return;

        $sellPhoneId = $this->selectedSell->id;
        $sellPhone = SellPhone::with('user.profile')->find($sellPhoneId);
        $phone = $sellPhone->user->profile->phone_number ?? null;

        $userAktif = Auth::user();
        if (!$userAktif->hasRole('admin') && $sellPhone->is_wa_sent) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Struk WhatsApp hanya dapat dikirim sekali oleh Kasir/FL.', type: 'warning');
            return;
        }

        if (!$phone) {
            $this->dispatch('toast', title: 'Gagal', message: 'Nomor HP customer tidak ditemukan.', type: 'warning');
            return;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }

        $fullUrl = config('services.qontak.api_url');
        if (empty($fullUrl)) {
            $this->dispatch('toast', title: 'Gagal', message: 'URL Qontak tidak ditemukan di konfigurasi (.env).', type: 'error');
            return;
        }
        if (!preg_match("~^(?:f|ht)tps?://~i", $fullUrl)) {
            $fullUrl = "https://" . $fullUrl;
        }

        $method = 'POST';
        $parsedUrl = parse_url($fullUrl);
        $endpoint = $parsedUrl['path'] ?? '';
        $clientId = config('services.qontak.client_id');
        $clientSecret = config('services.qontak.client_secret');

        try {
            $pdf = $this->generateReceiptPdf($sellPhone);
            $filename = 'Struk_SellPhone_' . $sellPhone->id . '.pdf';
            $folderPath = 'receipts_sellphone';
            $path = $folderPath . '/' . $filename;

            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());
            $pdfPublicUrl = asset('storage/' . $path);
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal menyimpan file PDF struk ke server.', type: 'error');
            return;
        }

        $dateString = gmdate('D, d M Y H:i:s') . ' GMT';
        $requestLine = "{$method} {$endpoint} HTTP/1.1";
        $stringToSign = "date: {$dateString}\n{$requestLine}";
        $digest = hash_hmac('sha256', $stringToSign, $clientSecret, true);
        $signature = base64_encode($digest);
        $hmacHeader = "hmac username=\"{$clientId}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\"";
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        $payload = [
            'to_name' => $sellPhone->user->name ?? 'Customer',
            'to_number' => $phone,
            'channel_integration_id' =>  config('services.qontak.integration_id'),
            'message_template_id' => config('services.qontak.template_id'),
            'language' => ['code' => 'id'],
            'parameters' => [
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        ['key' => 'url', 'value' => $pdfPublicUrl],
                        ['key' => 'filename', 'value' => $filename]
                    ]
                ],
                'body' => [
                    ['key' => '1', 'value' => 'nama', 'value_text' => $sellPhone->user->name ?? 'Customer'],
                    ['key' => '2', 'value' => 'no_invoice', 'value_text' => 'SPL-' . $sellPhone->id],
                    ['key' => '3', 'value' => 'total_tagihan', 'value_text' => 'Rp ' . number_format($sellPhone->appraised_value, 0, ',', '.')]
                ]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization'     => $hmacHeader,
                'Date'              => $dateString,
                'X-Idempotency-Key' => $idempotencyKey,
                'Content-Type'      => 'application/json',
                'Accept'            => 'application/json',
            ])->post($fullUrl, $payload);

            if ($response->successful()) {
                $sellPhone->update(['is_wa_sent' => true]);
                $this->selectedSell->refresh();
                $this->dispatch('toast', title: 'Berhasil', message: 'Struk WA berhasil dikirim!', type: 'success');
            } else {
                $this->dispatch('toast', title: 'Gagal API', message: 'Mekari: Code ' . $response->status(), type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('toast', title: 'Gagal', message: 'Crash: ' . $e->getMessage(), type: 'error');
        }
    }

    public function sendReceiptToEmail()
    {
        if (!$this->selectedSell) return;

        $sellPhoneId = $this->selectedSell->id;
        $sellPhone = SellPhone::with('user')->find($sellPhoneId);
        $email = $sellPhone->user->email ?? null;

        $userAktif = Auth::user();
        if (!$userAktif->hasRole('admin') && $sellPhone->is_email_sent) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Struk email hanya dapat dikirim sekali oleh Kasir/FL.', type: 'warning');
            return;
        }

        if (!$email || str_contains($email, '@pos.tokopun.com') || str_contains($email, '@tokopon.com')) {
            $this->dispatch('toast', title: 'Gagal Kirim', message: 'Email customer tidak valid atau kosong.', type: 'warning');
            return;
        }

        try {
            $pdf = $this->generateReceiptPdf($sellPhone);
            $pdfContent = $pdf->output();
            $filename = 'Struk_SellPhone_' . $sellPhone->id . '.pdf';

            Mail::mailer('pos_sales')
                ->to($email)
                ->send(new SellPhoneReceiptMail($sellPhone, $pdfContent, $filename));

            $sellPhone->update(['is_email_sent' => true]);
            $this->selectedSell->refresh();
            $this->dispatch('toast', title: 'Berhasil', message: 'Struk digital telah dikirim ke ' . $email, type: 'success');
        } catch (\Exception $e) {
            Log::error('POS Email Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Koneksi SMTP bermasalah: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $user = Auth::user();
        $buId = $user ? $user->getActiveBusinessUnitId() : 1;
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('superadmin') || $user->can('manage-trade-in'));

        $availableBranches = Branch::where('business_unit_id', $buId)->orderBy('name')->get();

        $baseQuery = SellPhone::with(['media', 'handledBy', 'branch', 'user.profile', 'user.bankAccounts', 'businessUnit'])
            ->where('business_unit_id', $buId);

        // Branch filtering
        if (!$isAdmin && $user && $user->branch_id) {
            $baseQuery->where('branch_id', $user->branch_id);
        } elseif (!empty($this->filterBranchId)) {
            $baseQuery->where('branch_id', $this->filterBranchId);
        }

        // Summary calculations
        $totalCount = (clone $baseQuery)->count();
        $completedQuery = (clone $baseQuery)->where('status', 'COMPLETED');
        $completedCount = $completedQuery->count();
        $completedTotal = (float) $completedQuery->sum('appraised_value');

        $payingQuery = (clone $baseQuery)->where('status', 'PAYING');
        $payingCount = $payingQuery->count();
        $payingTotal = (float) $payingQuery->sum('appraised_value');

        $inProgressCount = (clone $baseQuery)->whereIn('status', [
            'PENDING', 'OFFERED', 'WAITING_FOR_DEVICE', 'INSPECTING', 'REVISED_OFFER', 'PENDING_APPROVAL'
        ])->count();

        $cancelledCount = (clone $baseQuery)->whereIn('status', ['CANCELLED', 'REJECTED'])->count();

        $summary = [
            'total_count' => $totalCount,
            'completed_count' => $completedCount,
            'completed_total' => $completedTotal,
            'paying_count' => $payingCount,
            'paying_total' => $payingTotal,
            'in_progress_count' => $inProgressCount,
            'cancelled_count' => $cancelledCount,
        ];

        // Apply filters on the query
        $query = clone $baseQuery;

        if (!empty(trim($this->search))) {
            $term = '%' . trim($this->search) . '%';
            $cleanId = str_ireplace(['SPL-', 'SPL', '#'], '', trim($this->search));

            $query->where(function ($q) use ($term, $cleanId) {
                $q->where('invoice_number', 'like', $term)
                    ->orWhere('phone_brand', 'like', $term)
                    ->orWhere('phone_model', 'like', $term)
                    ->orWhere('imei', 'like', $term)
                    ->orWhere('bank_account_name', 'like', $term)
                    ->orWhere('bank_account_number', 'like', $term)
                    ->orWhere('bank_name', 'like', $term)
                    ->orWhereHas('user', function ($uq) use ($term) {
                        $uq->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('identity', 'like', $term)
                            ->orWhereHas('profile', function ($pq) use ($term) {
                                $pq->where('full_name', 'like', $term)
                                    ->orWhere('phone_number', 'like', $term);
                            });
                    })
                    ->orWhereHas('handledBy', function ($hq) use ($term) {
                        $hq->where('name', 'like', $term);
                    });

                if (is_numeric($cleanId)) {
                    $q->orWhere('id', (int) $cleanId);
                }
            });
        }

        // Apply Payment Filter
        if (!empty($this->filterPayment)) {
            if ($this->filterPayment === 'PAID') {
                $query->where('status', 'COMPLETED');
            } elseif ($this->filterPayment === 'PAYING') {
                $query->where('status', 'PAYING');
            } elseif ($this->filterPayment === 'IN_PROGRESS') {
                $query->whereIn('status', [
                    'PENDING', 'OFFERED', 'WAITING_FOR_DEVICE', 'INSPECTING', 'REVISED_OFFER', 'PENDING_APPROVAL'
                ]);
            } elseif ($this->filterPayment === 'CANCELLED') {
                $query->whereIn('status', ['CANCELLED', 'REJECTED']);
            } else {
                $query->where('status', $this->filterPayment);
            }
        }

        // Apply Date Filters
        if (!empty($this->filterStartDate)) {
            $query->whereDate('created_at', '>=', $this->filterStartDate);
        }
        if (!empty($this->filterEndDate)) {
            $query->whereDate('created_at', '<=', $this->filterEndDate);
        }

        $sells = $query->latest()->paginate(10);

        return view('livewire.zoffline.sell-phone.history', compact('sells', 'summary', 'availableBranches', 'isAdmin'));
    }
}
