<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Order;
use App\Models\User;
use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Services\MessageDispatchService;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.z', ['title' => 'Riwayat Penjualan POS'])]
class RiwayatPenjualan extends Component
{
    use WithPagination;

    public $search = '';

    // Filters
    public $filterStartDate = '';
    public $filterEndDate = '';
    public $filterStatus = '';
    public $filterPaymentMethod = '';
    public $filterKasir = '';
    public $filterBranchId = '';
    public $filterBuId = '';

    public $showReceiptModal = false;
    public $completedOrder = null;

    // Cancellation properties
    public $showCancelModal = false;
    public $cancelOrderId = null;
    public $cancelReason = '';

    // Direct cancellation properties (tanpa approval)
    public $showDirectCancelModal = false;
    public $directCancelOrderId = null;
    public $directCancelReason = '';

    public function mount()
    {
        $user = Auth::user();
        $canViewAllBranches = (bool) ($user && ($user->can('view-all-branches') || $user->hasRole('superadmin')));
        if (!$canViewAllBranches && $user && $user->branch_id) {
            $this->filterBranchId = (string) $user->branch_id;
        }
    }

    public function reprintOrder($orderId)
    {
        $this->completedOrder = Order::with(['items.variant', 'user', 'payments.paymentMethod', 'handledBy', 'salesBy'])->find($orderId);

        if ($this->completedOrder) {
            $this->showReceiptModal = true;
        }
    }

    public function closeReceipt()
    {
        $this->showReceiptModal = false;
        $this->completedOrder = null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStartDate()
    {
        $this->resetPage();
    }
    public function updatedFilterEndDate()
    {
        $this->resetPage();
    }
    public function updatedFilterStatus()
    {
        $this->resetPage();
    }
    public function updatedFilterPaymentMethod()
    {
        $this->resetPage();
    }
    public function updatedFilterKasir()
    {
        $this->resetPage();
    }
    public function updatedFilterBranchId()
    {
        $this->filterKasir = '';
        $this->resetPage();
    }
    public function updatedFilterBuId()
    {
        $user = Auth::user();
        $canViewAllBranches = (bool) ($user && ($user->can('view-all-branches') || $user->hasRole('superadmin')));
        if (!$canViewAllBranches && $user && $user->branch_id) {
            $this->filterBranchId = (string) $user->branch_id;
        } else {
            $this->filterBranchId = '';
        }
        $this->filterKasir = '';
        $this->resetPage();
    }

    public function setStatusFilter($status)
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterStartDate', 'filterEndDate', 'filterStatus', 'filterPaymentMethod', 'filterKasir', 'filterBuId', 'filterBranchId']);
        $user = Auth::user();
        $canViewAllBranches = (bool) ($user && ($user->can('view-all-branches') || $user->hasRole('superadmin')));
        if (!$canViewAllBranches && $user && $user->branch_id) {
            $this->filterBranchId = (string) $user->branch_id;
        }
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $canViewAllBu = (bool) ($user && ($user->can('view-all-bu') || $user->hasRole('superadmin')));
        $canViewAllBranches = (bool) ($user && ($user->can('view-all-branches') || $user->hasRole('superadmin')));

        $businessUnits = $canViewAllBu ? BusinessUnit::where('is_active', true)->get() : collect();

        // Determine active BU: if permitted, use $this->filterBuId (empty string means Semua BU)
        if ($canViewAllBu) {
            $activeBuId = $this->filterBuId !== '' ? (int)$this->filterBuId : null;
        } else {
            $activeBuId = $user ? $user->getActiveBusinessUnitId() : 1;
        }

        // Branch determination: if permitted, user can choose branch or view all; otherwise locked to user's branch
        if ($canViewAllBranches) {
            $activeBranchId = $this->filterBranchId !== '' ? $this->filterBranchId : null;
            $branchQuery = Branch::with('businessUnit')->orderBy('business_unit_id')->orderBy('name');
            if ($activeBuId) {
                $branchQuery->where('business_unit_id', $activeBuId);
            }
            $branches = $branchQuery->get();
        } else {
            $activeBranchId = $user->branch_id ?? null;
            $branches = collect();
        }

        // Cashiers query according to active branch or active BU
        if ($activeBranchId) {
            $cashierIdsInBranch = Order::where('branch_id', $activeBranchId)
                ->whereNotNull('handled_by')
                ->distinct()
                ->pluck('handled_by')
                ->toArray();

            $cashiers = User::where(function ($query) use ($activeBranchId, $cashierIdsInBranch) {
                $query->where(function ($q) use ($activeBranchId) {
                    $q->where('branch_id', $activeBranchId)
                        ->whereHas('roles', function ($rq) {
                            $rq->where('name', 'like', '%kasir%');
                        });
                })->orWhereIn('id', $cashierIdsInBranch);
            })
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $orderCashierQuery = Order::whereNotNull('handled_by');
            if ($activeBuId) {
                $orderCashierQuery->where('business_unit_id', $activeBuId);
            }
            $cashierIds = $orderCashierQuery->distinct()->pluck('handled_by')->toArray();

            $cashiers = User::where(function ($q) use ($cashierIds, $activeBuId) {
                $q->whereHas('roles', function ($rq) {
                    $rq->where('name', 'like', '%kasir%');
                })->orWhereIn('id', $cashierIds);
            })
                ->when($activeBuId, function ($q) use ($activeBuId) {
                    $q->where('business_unit_id', $activeBuId);
                })
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        // Base query for counts and orders
        $baseQuery = Order::whereIn('order_channel', ['POS', 'SO'])
            ->where('order_status', '!=', 'DRAFT')
            ->when($activeBuId, function ($q) use ($activeBuId) {
                $q->where('business_unit_id', $activeBuId);
            })
            ->when($activeBranchId, function ($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->when($this->filterKasir, function ($query) {
                $query->where('handled_by', $this->filterKasir);
            })
            ->when($this->filterStartDate, function ($query) {
                $query->whereDate('created_at', '>=', $this->filterStartDate);
            })
            ->when($this->filterEndDate, function ($query) {
                $query->whereDate('created_at', '<=', $this->filterEndDate);
            });

        // Quick status counts (for summary tabs/cards)
        $statusCounts = [
            'ALL' => (clone $baseQuery)->count(),
            'COMPLETED' => (clone $baseQuery)->where('order_status', 'COMPLETED')
                ->where(function ($q) {
                    $q->where(function ($aq) {
                        $aq->whereNotNull('accurate_invoice_no')->where('accurate_invoice_no', '!=', '');
                    })->orWhere(function ($aq) {
                        $aq->whereNotNull('accurate_receipt_no')->where('accurate_receipt_no', '!=', '');
                    });
                })->count(),
            'PIUTANG' => (clone $baseQuery)->whereIn('order_status', ['PIUTANG', 'piutang'])->count(),
            'PENDING' => (clone $baseQuery)->where(function ($q) {
                $q->whereIn('order_status', ['pending', 'PENDING', 'down_payment'])
                    ->orWhere(function ($sub) {
                        $sub->whereNotIn('order_status', ['CANCELLED', 'DELETED', 'PIUTANG', 'piutang', 'DRAFT'])
                            ->where(function ($aq) {
                                $aq->whereNull('accurate_invoice_no')->orWhere('accurate_invoice_no', '');
                            })
                            ->where(function ($aq) {
                                $aq->whereNull('accurate_receipt_no')->orWhere('accurate_receipt_no', '');
                            });
                    })
                    ->orWhereHas('approvalRequests', function ($aq) {
                        $aq->whereIn('request_type', ['ORDER_CANCELLATION', 'cancellation'])->where('status', 'PENDING');
                    });
            })->count(),
            'CANCELLED' => (clone $baseQuery)->where('order_status', 'CANCELLED')->count(),
        ];

        // Orders query with search and filters applied
        $orders = (clone $baseQuery)
            ->with(['user', 'items', 'payments', 'salesBy', 'handledBy', 'businessUnit', 'branch', 'approvalRequests' => function ($q) {
                $q->whereIn('request_type', ['ORDER_CANCELLATION', 'cancellation']);
            }])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($uq) {
                            $uq->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('identity', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('items', function ($iq) {
                            $iq->where('serial_number', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterStatus, function ($query) {
                if ($this->filterStatus === 'PIUTANG') {
                    $query->whereIn('order_status', ['PIUTANG', 'piutang']);
                } elseif ($this->filterStatus === 'PENDING') {
                    $query->where(function ($q) {
                        $q->whereIn('order_status', ['pending', 'PENDING', 'down_payment'])
                            ->orWhere(function ($sub) {
                                $sub->whereNotIn('order_status', ['CANCELLED', 'DELETED', 'PIUTANG', 'piutang', 'DRAFT'])
                                    ->where(function ($aq) {
                                        $aq->whereNull('accurate_invoice_no')->orWhere('accurate_invoice_no', '');
                                    })
                                    ->where(function ($aq) {
                                        $aq->whereNull('accurate_receipt_no')->orWhere('accurate_receipt_no', '');
                                    });
                            })
                            ->orWhereHas('approvalRequests', function ($aq) {
                                $aq->whereIn('request_type', ['ORDER_CANCELLATION', 'cancellation'])->where('status', 'PENDING');
                            });
                    });
                } elseif ($this->filterStatus === 'COMPLETED') {
                    $query->where('order_status', 'COMPLETED')
                        ->where(function ($q) {
                            $q->where(function ($aq) {
                                $aq->whereNotNull('accurate_invoice_no')->where('accurate_invoice_no', '!=', '');
                            })->orWhere(function ($aq) {
                                $aq->whereNotNull('accurate_receipt_no')->where('accurate_receipt_no', '!=', '');
                            });
                        });
                } elseif ($this->filterStatus === 'CANCELLED') {
                    $query->where('order_status', 'CANCELLED');
                } else {
                    $query->where('order_status', $this->filterStatus);
                }
            })
            ->when($this->filterPaymentMethod, function ($query) {
                $query->whereHas('payments', function ($pq) {
                    $pq->where('payment_method_id', $this->filterPaymentMethod);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $paymentMethodsQuery = \App\Models\PaymentMethod::where('is_active', true);
        if ($activeBuId) {
            $paymentMethodsQuery->where('business_unit_id', $activeBuId);
        }
        $paymentMethods = $paymentMethodsQuery->get();

        $activeBranchName = null;
        if ($activeBranchId) {
            $activeBranchName = Branch::find($activeBranchId)?->name;
        }

        return view('livewire.zoffline.reporting.riwayat-penjualan', [
            'orders' => $orders,
            'paymentMethods' => $paymentMethods,
            'cashiers' => $cashiers,
            'branches' => $branches,
            'businessUnits' => $businessUnits,
            'canViewAllBu' => $canViewAllBu,
            'canViewAllBranches' => $canViewAllBranches,
            'statusCounts' => $statusCounts,
            'activeBranchId' => $activeBranchId,
            'activeBranchName' => $activeBranchName,
            'activeBuId' => $activeBuId,
            'filterStatus' => $this->filterStatus,
            'filterKasir' => $this->filterKasir,
            'filterBranchId' => $this->filterBranchId,
            'filterBuId' => $this->filterBuId,
            'search' => $this->search,
            'filterStartDate' => $this->filterStartDate,
            'filterEndDate' => $this->filterEndDate,
            'filterPaymentMethod' => $this->filterPaymentMethod,
            'showCancelModal' => $this->showCancelModal,
            'cancelReason' => $this->cancelReason,
            'showDirectCancelModal' => $this->showDirectCancelModal,
            'directCancelReason' => $this->directCancelReason,
            'showReceiptModal' => $this->showReceiptModal,
            'completedOrder' => $this->completedOrder,
        ]);
    }

    public function newTransaction()
    {
        return $this->redirect(route('zoffline.pos'), navigate: true);
    }

    public function getEscposBase64()
    {
        if (!$this->completedOrder) {
            $this->dispatch('toast', title: 'Error', message: 'Tidak ada transaksi aktif untuk dicetak.', type: 'error');
            return;
        }

        try {
            $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
            $printer = new \Mike42\Escpos\Printer($connector);
            $printer->initialize();

            $this->generateEscposContent($printer);
            $printer->feed(1);
            $printer->cut();

            $data = $connector->getData();
            $base64 = base64_encode($data);

            $printer->close();

            $orderNumber = $this->completedOrder->order_number ?? 'terbaru';
            $this->dispatch('print-receipt', base64Data: $base64, orderNumber: $orderNumber);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ESCPOS Base64 Generation Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal memproses cetakan: ' . $e->getMessage(), type: 'error');
        }
    }

    public function getEscposBase64QzSilent()
    {
        if (!$this->completedOrder) {
            $this->dispatch('toast', title: 'Error', message: 'Tidak ada transaksi aktif untuk dicetak.', type: 'error');
            return;
        }

        try {
            $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
            $printer = new \Mike42\Escpos\Printer($connector);
            $printer->initialize();

            $this->generateEscposContent($printer);
            $printer->feed(1);
            $printer->cut();

            $data = $connector->getData();
            $base64 = base64_encode($data);

            $printer->close();

            $orderNumber = $this->completedOrder->order_number ?? 'terbaru';
            $this->dispatch('print-receipt-silent', base64Data: $base64, orderNumber: $orderNumber);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ESCPOS Base64 QZ Silent Generation Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal memproses cetakan: ' . $e->getMessage(), type: 'error');
        }
    }

    private function generateEscposContent($printer)
    {
        $maxColumns = 40;
        $separator = str_repeat("-", $maxColumns) . "\n";

        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(
            \Mike42\Escpos\Printer::MODE_FONT_B |
                \Mike42\Escpos\Printer::MODE_DOUBLE_WIDTH |
                \Mike42\Escpos\Printer::MODE_DOUBLE_HEIGHT
        );
        $storeTitle = optional($this->completedOrder->businessUnit)->store_title ?? 'Z-POS STORE';
        $printer->text($storeTitle . "\n");
        $printer->selectPrintMode(\Mike42\Escpos\Printer::MODE_FONT_B);

        $storeName = $this->completedOrder->shipping_address_snapshot['store'] ?? 'Toko';
        $printer->text($storeName . "\n");
        $printer->text($this->completedOrder->created_at->format('d/m/Y H:i') . "\n");
        $printer->text($separator);

        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_LEFT);
        $printer->text($this->formatLine("No. Transaksi", $this->completedOrder->order_number, $maxColumns) . "\n");
        $printer->text($this->formatLine("Kasir", $this->completedOrder->handledBy->name ?? '-', $maxColumns) . "\n");
        $printer->text($this->formatLine("Sales", $this->completedOrder->salesBy->name ?? '-', $maxColumns) . "\n");
        $printer->text($this->formatLine("Customer", $this->completedOrder->user->name ?? '-', $maxColumns) . "\n");
        $printer->text($this->formatLine("Customer No", $this->completedOrder->user->profile->phone_number ?? '-', $maxColumns) . "\n");
        $printer->text($separator);

        foreach ($this->completedOrder->items as $item) {
            $v = $item->variant;

            if ($v instanceof \App\Models\ProductAccurate) {
                $itemName = $v->name ?? '-';
                $ram = '';
                $storage = '';
                $color = '';
            } else {
                $itemName = $v ? $v->product->name ?? ($v->secondProduct->name ?? '-') : '-';
                $ram = $v ? $v->ram ?? '' : '';
                $storage = $v ? $v->storage ?? '' : '';
                $color = $v ? $v->color ?? '' : '';
            }

            // Bersihkan awalan nama
            $itemName = preg_replace('/^(?:DS\s*-\s*HP\s*|DS\s*-\s*|HP\s*-\s*|HP\s*)/i', '', trim($itemName));

            if ($v && !($v instanceof \App\Models\ProductAccurate)) {
                $variantDetails = "";
                if ($ram != null && $ram !== '') $variantDetails .= $ram . "/";
                $variantDetails .= $storage;
                if ($color != null && $color !== '') $variantDetails .= " " . $color;
                if (trim($variantDetails) !== '') $itemName .= " " . trim($variantDetails);
            }

            $printer->text($itemName . "\n");

            $qtyAndPrice = $item->qty . "x Rp " . number_format($item->price_at_checkout, 0, ',', '.');
            $subtotal = "Rp " . number_format($item->subtotal, 0, ',', '.');

            // Mengurangi space di depan menjadi 1 spasi saja agar menghemat karakter yang makin sempit
            $printer->text($this->formatLine(" " . $qtyAndPrice, $subtotal, $maxColumns) . "\n");

            if ($item->serial_number) {
                $printer->text(" SN: " . $item->serial_number . "\n");
            }
        }
        $printer->text($separator);

        // Total Section
        $showDiscount = optional($this->completedOrder->businessUnit)->receipt_show_discount;
        if ($showDiscount) {
            $printer->text($this->formatLine("Subtotal", "Rp " . number_format($this->completedOrder->total_amount, 0, ',', '.'), $maxColumns) . "\n");
            if ($this->completedOrder->discount_amount > 0) {
                $printer->text($this->formatLine("Diskon", "-Rp " . number_format($this->completedOrder->discount_amount, 0, ',', '.'), $maxColumns) . "\n");
            }
            $printer->text($this->formatLine("TOTAL", "Rp " . number_format($this->completedOrder->grand_total, 0, ',', '.'), $maxColumns) . "\n");
        } else {
            $printer->text($this->formatLine("Total", "Rp " . number_format($this->completedOrder->total_amount, 0, ',', '.'), $maxColumns) . "\n");
        }
        $printer->text($separator);
        if ($this->completedOrder->accurate_invoice_no) {
            $printer->text($this->formatLine("No. SI", $this->completedOrder->accurate_invoice_no, $maxColumns) . "\n");
        }
        $printer->text($separator);
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
        $printer->text("\nTerima kasih telah berbelanja!\n");
        $printer->text("Call Center : 0811-5600-6464\n");
        $printer->text("\n\n\n\n\n");
    }

    private function formatLine($left, $right, $width = 58)
    {
        $leftWidth = strlen($left);
        $rightWidth = strlen($right);
        $spaces = $width - $leftWidth - $rightWidth;
        if ($spaces < 1) $spaces = 1;
        return $left . str_repeat(' ', $spaces) . $right;
    }

    private function generateReceiptPdf($order)
    {
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt', compact('order'))
            ->setPaper([0, 0, 226, 600], 'portrait');
    }

    public function sendReceiptToEmail()
    {
        if (!$this->completedOrder) return;

        $orderId = $this->completedOrder->id;
        $order = Order::with('user')->find($orderId);
        $email = $order->user->email ?? null;

        $userAktif = Auth::user();
        if (!$userAktif->hasRole('admin') && $order->is_email_sent) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Struk email hanya dapat dikirim sekali oleh Kasir/FL.', type: 'warning');
            return;
        }

        if (!$email || str_contains($email, '@pos.tokopun.com') || str_contains($email, '@tokopon.com')) {
            $this->dispatch('toast', title: 'Gagal Kirim', message: 'Email customer tidak valid atau kosong.', type: 'warning');
            return;
        }

        try {
            $pdf = $this->generateReceiptPdf($order);
            $pdfContent = $pdf->output();
            $filename = 'Struk_' . $order->order_number . '.pdf';

            $result = app(MessageDispatchService::class)->sendOrderEmail($order, $pdfContent, $filename, $userAktif);

            $this->completedOrder->refresh();

            if ($result['success']) {
                $this->dispatch('toast', title: 'Berhasil', message: $result['message'], type: 'success');
            } else {
                $this->dispatch('toast', title: 'Gagal', message: $result['message'], type: 'error');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('POS Email Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Koneksi SMTP bermasalah: ' . $e->getMessage(), type: 'error');
        }
    }

    public function sendReceiptToQontak()
    {
        if (!$this->completedOrder) return;

        $orderId = $this->completedOrder->id;
        $order = Order::with('user.profile')->find($orderId);
        $phone = $order->user->profile->phone_number ?? null;

        $userAktif = Auth::user();
        if (!$userAktif->hasRole('admin') && $order->is_wa_sent) {
            $this->dispatch('toast', title: 'Akses Ditolak', message: 'Struk WhatsApp hanya dapat dikirim sekali oleh Kasir/FL.', type: 'warning');
            return;
        }

        if (!$phone) {
            $this->dispatch('toast', title: 'Gagal', message: 'Nomor HP customer tidak ditemukan.', type: 'warning');
            return;
        }

        try {
            $pdf = $this->generateReceiptPdf($order);
            $filename = 'Struk_' . $order->order_number . '.pdf';
            $folderPath = 'receipts';
            $path = $folderPath . '/' . $filename;
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());
            $pdfPublicUrl = asset('storage/' . $path);

            $result = app(MessageDispatchService::class)->sendOrderWhatsApp($order, $pdfPublicUrl, $filename, $userAktif);

            $this->completedOrder->refresh();

            if ($result['success']) {
                $this->dispatch('toast', title: 'Berhasil', message: $result['message'], type: 'success');
            } else {
                $this->dispatch('toast', title: 'Gagal API', message: $result['message'], type: 'error');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Qontak Process Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Crash: ' . $e->getMessage(), type: 'error');
        }
    }

    // Cancellation Methods
    public function requestCancellation($orderId)
    {
        $this->cancelOrderId = $orderId;
        $this->cancelReason = '';
        $this->showCancelModal = true;
    }

    public function closeCancelModal()
    {
        $this->showCancelModal = false;
        $this->cancelOrderId = null;
    }

    // Direct Cancellation Methods (tanpa approval)
    public function requestDirectCancellation($orderId)
    {
        $this->directCancelOrderId = $orderId;
        $this->directCancelReason = '';
        $this->showDirectCancelModal = true;
    }

    public function closeDirectCancelModal()
    {
        $this->showDirectCancelModal = false;
        $this->directCancelOrderId = null;
        $this->directCancelReason = '';
    }

    public function directCancellation()
    {
        $this->validate([
            'directCancelReason' => 'required|min:5'
        ], [
            'directCancelReason.required' => 'Alasan pembatalan wajib diisi.',
            'directCancelReason.min' => 'Alasan pembatalan minimal 5 karakter.'
        ]);

        $order = Order::find($this->directCancelOrderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Transaksi tidak ditemukan.', type: 'error');
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Hapus dokumen di Accurate (Sales Receipt, Sales Invoice, DO, SO)
            $accurateService = app(\App\Services\AccurateService::class);
            $accurateService->rollbackOrderDocuments($order);

            // 2. Update order status ke DRAFT dan null-kan kolom accurate
            $order->update([
                'order_status' => 'DRAFT',
                'accurate_invoice_no' => null,
                'accurate_receipt_no' => null,
            ]);

            // 3. Kembalikan saldo deposit yang terpakai
            $usages = \App\Models\CustomerDepositUsage::where('order_id', $order->id)->get();
            foreach ($usages as $usage) {
                $deposit = $usage->customerDeposit;
                if ($deposit) {
                    $deposit->balance += (float) $usage->amount_used;
                    $deposit->status = 'AVAILABLE';
                    $deposit->save();
                }
                $usage->delete();
            }

            // Kembalikan deposit SO (yang berasal dari DP SO ini) ke AVAILABLE
            \App\Models\CustomerDeposit::where('origin_order_id', $order->id)
                ->where('status', 'USED')
                ->update(['status' => 'AVAILABLE']);

            // 4. Hapus semua payment terkait order ini
            $order->payments()->delete();

            \Illuminate\Support\Facades\DB::commit();

            $this->dispatch('toast', title: 'Berhasil', message: 'Dokumen Accurate dihapus & transaksi dikembalikan ke Draft.', type: 'success');
            $this->closeDirectCancelModal();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Direct Cancellation Error: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Terjadi kesalahan: ' . $e->getMessage(), type: 'error');
        }
    }

    public function submitCancellation()
    {
        $this->validate([
            'cancelReason' => 'required|min:5'
        ], [
            'cancelReason.required' => 'Alasan pembatalan wajib diisi.',
            'cancelReason.min' => 'Alasan pembatalan minimal 5 karakter.'
        ]);

        $order = Order::find($this->cancelOrderId);
        if (!$order) {
            $this->dispatch('toast', title: 'Error', message: 'Transaksi tidak ditemukan.', type: 'error');
            return;
        }

        // Check if there is already a pending request
        $existing = $order->approvalRequests()->where('status', 'PENDING')->whereIn('request_type', ['ORDER_CANCELLATION', 'cancellation'])->first();
        if ($existing) {
            $this->dispatch('toast', title: 'Info', message: 'Transaksi ini sudah dalam proses pengajuan pembatalan.', type: 'info');
            $this->closeCancelModal();
            return;
        }

        $request = app(\App\Services\ApprovalService::class)->createRequest([
            'approvable'       => $order,
            'request_type'     => 'ORDER_CANCELLATION',
            'requested_by'     => Auth::id(),
            'business_unit_id' => $order->business_unit_id,
            'branch_id'        => $order->branch_id,
            'total_amount'     => $order->grand_total,
            'reason'           => $this->cancelReason,
        ]);

        $this->dispatch('toast', title: 'Berhasil', message: 'Pengajuan pembatalan berhasil dikirim ke Admin/Pusat.', type: 'success');
        $this->closeCancelModal();
    }
}
