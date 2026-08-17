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

        $nextLevel = $approval->current_level + 1;
        $rule = \App\Models\ApprovalRule::with('role')->where('module', $approval->request_type)->where('level', $nextLevel)->first();
        $targetRole = $rule && $rule->role ? strtolower($rule->role->name) : 'manager';

        // Persiapkan data yang akan dikirim
        $kasirName = $approval->requestedBy->name ?? 'Kasir';
        $tipe = str_replace('_', ' ', $approval->request_type) . " (Level {$nextLevel})";

        $orderInfo = '-';
        if ($approval->approvable_type === \App\Models\Order::class && $approval->approvable) {
            $orderInfo = $approval->approvable->order_number;
        }

        try {
            // PAYLOAD BARU: Kita hanya mengirim approval_id, bukan link
            $response = Http::timeout(5)->post($n8nWebhookUrl, [
                'target_role' => $targetRole,
                'judul'       => "Pengajuan {$tipe} untuk {$orderInfo}",
                'kasir'       => $kasirName,
                'waktu'       => $approval->created_at->format('d M Y H:i'),
                'keterangan'  => $approval->reason ?? '-',
                'approval_id' => $approval->id,
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Gagal kirim Webhook Telegram: ' . $e->getMessage());
            return false;
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
        $id = $request->input('approval_id');
        $action = $request->input('action'); // 'approve' atau 'reject'

        $approval = ApprovalRequest::find($id);

        if (!$approval) {
            return response()->json(['success' => false, 'message' => 'Data pengajuan tidak ditemukan.']);
        }

        if ($approval->status !== 'PENDING') {
            return response()->json(['success' => false, 'message' => "Pengajuan ini sudah berstatus: " . $approval->status]);
        }

        $approverId = 1; // Asumsi ID manajer
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
            $pesan = "❌ Pengajuan telah DITOLAK.";
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
