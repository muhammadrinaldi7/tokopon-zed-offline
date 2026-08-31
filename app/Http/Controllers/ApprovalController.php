<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ApprovalRequest;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{
    /**
     * Helper untuk memicu pengiriman notifikasi ke Telegram via n8n webhook
     * Sekarang menggunakan mekanisme Inline Keyboard Callback Data, bukan link sakti
     */
    public static function sendTelegramNotification(ApprovalRequest $approval)
    {
        $n8nWebhookUrl = 'https://n8n.zedgroup.tech/webhook/approval-telegram-zedpos';
        $businessUnitId = $approval->requestedBy->business_unit_id ?? null;

        if ($businessUnitId) {
            $bu = \App\Models\BusinessUnit::find($businessUnitId);
            if ($bu && $bu->telegram_approval_webhook) {
                $n8nWebhookUrl = $bu->telegram_approval_webhook;
            }
        }

        $nextLevel = $approval->current_level + 1;
        $rule = \App\Models\ApprovalRule::with('role')->where('module', $approval->request_type)->where('level', $nextLevel)->first();
        $targetRole = $rule && $rule->role ? strtolower($rule->role->name) : 'manager';

        $cabangId = $approval->requestedBy->branch_id ?? null;

        $globalRoles = ['admin', 'direktur', 'superadmin'];
        // Role tingkat cabang (difilter berdasarkan BU dan Cabang)
        $localRoles = ['manager', 'bm', 'supervisor', 'manager_operasional'];

        $query = \App\Models\User::role($targetRole)
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '');

        // Jika bukan role global (seperti admin/direktur/superadmin), filter berdasarkan Business Unit
        // Ini berlaku untuk manager, bm, supervisor, manageroperasional, dll.
        if (!in_array($targetRole, $globalRoles) && $businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        // Jika role tingkat cabang, filter lagi secara spesifik berdasarkan cabang
        if (in_array($targetRole, $localRoles) && $cabangId) {
            $query->where('branch_id', $cabangId);
        }

        $targetUsers = $query->get();

        if ($targetUsers->isEmpty()) {
            Log::info("Telegram Webhook: Tidak ada user dengan role {$targetRole} dan telegram_chat_id yang valid untuk cabang {$cabangId}");
            return false;
        }

        $kasirName = $approval->requestedBy->name ?? 'Kasir';
        $tipe = str_replace('_', ' ', $approval->request_type) . " (Level {$nextLevel})";

        $orderInfo = '-';
        if ($approval->approvable_type === \App\Models\Order::class && $approval->approvable) {
            $orderInfo = $approval->approvable->order_number;
        } elseif ($approval->approvable_type === \App\Models\SellPhone::class && $approval->approvable) {
            $orderInfo = $approval->approvable->phone_brand . ' ' . $approval->approvable->phone_model;
        }

        $keterangan = $approval->reason ?? '-';

        if ($approval->request_type === 'CUSTOM_CASHBACK' && is_array($approval->payload)) {
            $orderInfo = $approval->payload['product_name'] ?? '-';
            $amount = $approval->payload['amount'] ?? 0;
            $price = $approval->payload['item_price'] ?? 0;
            $keterangan .= "\n\nHarga Jual: Rp " . number_format($price, 0, ',', '.') . "\nNominal Cashback: Rp " . number_format($amount, 0, ',', '.');
        }

        $successCount = 0;

        foreach ($targetUsers as $user) {
            try {
                $response = Http::timeout(5)->post($n8nWebhookUrl, [
                    'chat_id_penerima' => $user->telegram_chat_id,
                    'judul'            => "Pengajuan {$tipe} untuk {$orderInfo}",
                    'kasir'            => $kasirName,
                    'branch'           => $approval->requestedBy->branch->name,
                    'waktu'            => $approval->created_at->format('d M Y H:i'),
                    'keterangan'       => $keterangan,
                    'action'           => 'PENDING',
                    'approval_id'      => $approval->id,
                    'req_level'        => $nextLevel,
                ]);

                if ($response->successful()) {
                    $successCount++;
                }
            } catch (\Exception $e) {
                Log::error("Gagal kirim Webhook Telegram ke user {$user->id}: " . $e->getMessage());
            }
        }

        // --- KIRIM NOTIFIKASI KE GRUP JUGA ---
        $alasan = $keterangan;
        $cabang = $approval->requestedBy->branch->name ?? '-';
        $waktuFormat = $approval->created_at->format('d M Y H:i');

        $teksGrup = "🔔 *PENGAJUAN BARU*\n\n"
            . "Pengajuan: {$tipe} untuk {$orderInfo}\n"
            . "Kasir: {$kasirName}\n"
            . "Waktu: {$waktuFormat}\n"
            . "Cabang: {$cabang}\n"
            . "Keterangan: \"{$alasan}\"\n\n"
            . "⏳ _Menunggu persetujuan dari divisi terkait._";

        self::sendGroupNotification($teksGrup, $businessUnitId);
        // -------------------------------------

        return $successCount > 0;
    }
    /**
     * Helper khusus untuk mengirim pesan log ke Grup Telegram
     */
    public static function sendGroupNotification($pesan_teks, $businessUnitId = null)
    {
        // Ganti dengan URL Webhook Workflow 3 Anda yang baru
        $n8nGroupWebhook = 'https://n8n.zedgroup.tech/webhook/approval-group-log';

        if ($businessUnitId) {
            $bu = \App\Models\BusinessUnit::find($businessUnitId);
            if ($bu && $bu->telegram_log_webhook) {
                $n8nGroupWebhook = $bu->telegram_log_webhook;
            }
        }

        try {
            Http::timeout(5)->post($n8nGroupWebhook, [
                'pesan_grup' => $pesan_teks
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gagal kirim log grup: " . $e->getMessage());
        }
    }
    /**
     * Proses persetujuan via API (Ditembak oleh n8n secara otomatis di belakang layar ketika tombol callback ditekan)
     */
    public function apiProcess(Request $request)
    {
        // 1. VALIDASI KEAMANAN API (Pengganti Signed Route)
        $secretKey = env('N8N_API_SECRET', 'ZedposRahasia123');
        if ($request->header('X-API-KEY') !== $secretKey) {
            return response()->json(['success' => false, 'message' => 'Unauthorized Access'], 401);
        }

        // Tangkap data dari body JSON yang dikirim n8n
        Log::info('Webhook Telegram Masuk:', $request->all());

        $id = $request->input('approval_id');
        $action = $request->input('action'); // 'approve' atau 'reject'
        $reqLevel = (int) $request->input('req_level');
        $chatId = $request->input('telegram_chat_id');

        // 2. MENDETEKSI SIAPA PENGEKLIK
        if (!$chatId) {
            return response()->json(['success' => false, 'message' => 'Telegram Chat ID tidak ditemukan.']);
        }

        $user = \App\Models\User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Akun dengan Chat ID tersebut tidak terdaftar di sistem.']);
        }

        $approval = ApprovalRequest::find($id);

        if (!$approval) {
            return response()->json(['success' => false, 'message' => 'Data pengajuan tidak ditemukan.']);
        }

        if ($approval->status !== 'PENDING') {
            return response()->json(['success' => false, 'message' => "Pengajuan ini sudah berstatus: " . $approval->status]);
        }

        // 3. MENGUNCI LEVEL (LEVEL LOCK VALIDATION)
        $expectedLevel = $approval->current_level + 1;
        if ($reqLevel !== $expectedLevel) {
            return response()->json(['success' => false, 'message' => 'Tombol ini sudah kadaluarsa (Pengajuan sudah diproses ke tahap selanjutnya/sebelumnya).']);
        }

        // 4. VALIDASI JABATAN (ROLE AUTHORIZATION)
        $rule = \App\Models\ApprovalRule::with('role')->where('module', $approval->request_type)->where('level', $expectedLevel)->first();
        $targetRole = $rule && $rule->role ? strtolower($rule->role->name) : 'manager';

        if (!$user->hasRole($targetRole)) {
            return response()->json(['success' => false, 'message' => "Anda tidak memiliki hak akses ({$targetRole}) untuk menyetujui level ini."]);
        }

        $approverId = $user->id;
        $pesan = "";

        // 3. Eksekusi Perubahan Status
        if ($action === 'approve') {

            $approval->histories()->create([
                'acted_by' => $approverId,
                'action'   => 'APPROVED',
                'level'    => $approval->current_level + 1,
                'notes'    => 'Approved via Telegram API Callback'
            ]);

            $approval->current_level += 1;

            if ($approval->current_level >= $approval->required_level) {
                $approval->status = 'APPROVED';
                $approval->save();

                try {
                    $approval->executeAction(['extension_days' => 7]);
                    $pesan = "✅ Pengajuan telah DISETUJUI sepenuhnya.";

                    $kasirName = $approval->requestedBy->name ?? 'Kasir';
                    $tipe = str_replace('_', ' ', $approval->request_type) . " (Level {$approval->required_level})";
                    $orderInfo = '-';
                    if ($approval->approvable_type === \App\Models\Order::class && $approval->approvable) {
                        $orderInfo = $approval->approvable->order_number;
                    } elseif ($approval->approvable_type === \App\Models\SellPhone::class && $approval->approvable) {
                        $orderInfo = $approval->approvable->phone_brand . ' ' . $approval->approvable->phone_model;
                    }
                    $cabang = $approval->requestedBy->branch->name ?? '-';
                    $waktu = $approval->created_at->format('d M Y H:i');
                    $alasan = $approval->reason ?? '-';

                    $teksGrup = "✅ *APPROVAL SUKSES*\n\n"
                        . "Pengajuan: {$tipe} untuk {$orderInfo}\n"
                        . "Kasir: {$kasirName}\n"
                        . "Waktu: {$waktu}\n"
                        . "Cabang: {$cabang}\n"
                        . "Keterangan: \"{$alasan}\"\n\n"
                        . "Telah disetujui sepenuhnya oleh *{$user->name}*.";

                    $buId = $approval->requestedBy->business_unit_id ?? null;
                    self::sendGroupNotification($teksGrup, $buId);
                } catch (\Exception $e) {
                    $pesan = "⚠️ Disetujui, tapi gagal eksekusi aksi: " . $e->getMessage();
                }
            } else {
                $approval->save();

                // Trigger notifikasi untuk level selanjutnya
                self::sendTelegramNotification($approval);

                $pesan = "✅ Disetujui (Diteruskan ke level selanjutnya).";
            }
        } elseif ($action === 'reject') {

            $approval->histories()->create([
                'acted_by' => $approverId,
                'action'   => 'REJECTED',
                'level'    => $approval->current_level + 1,
                'notes'    => 'Rejected via Telegram API Callback'
            ]);

            $approval->update(['status' => 'REJECTED']);
            try {
                $approval->executeRejectedAction();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to execute rejected action via Telegram for Request ID {$approval->id}: " . $e->getMessage());
            }
            $pesan = "❌ Pengajuan telah DITOLAK.";
            $teksGrup = "❌ *APPROVAL DITOLAK*\nPengajuan {$approval->request_type} (ID: {$approval->id}) telah DITOLAK oleh {$user->name}.";

            $buId = $approval->requestedBy->business_unit_id ?? null;
            self::sendGroupNotification($teksGrup, $buId);
        } else {
            return response()->json(['success' => false, 'message' => 'Action tidak dikenali.']);
        }

        // 4. RETURN BERUPA JSON
        // n8n akan membaca pesan ini dan menampilkannya sebagai popup (Answer Callback Query) di Telegram
        return response()->json([
            'success' => true,
            'message' => $pesan
        ]);
    }
}
