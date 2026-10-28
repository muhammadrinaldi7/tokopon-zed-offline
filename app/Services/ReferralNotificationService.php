<?php

namespace App\Services;

use App\Models\CommissionWithdrawal;
use App\Models\MessageLog;
use App\Models\ReferralCommission;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReferralNotificationService
{
    public function __construct(
        protected ExpoPushService $expoPushService,
        protected SettingService $settingService
    ) {}

    /**
     * 1. Notifikasi saat ada teman baru mendaftar / aktivasi dengan kode referral.
     */
    public function notifyFriendJoined(User $referrer, User $newFriend): void
    {
        $referrer->loadMissing('profile');
        $referralCode = $referrer->referral_code ?? 'TKP';
        $title = "🎉 Teman Baru Bergabung!";
        $body = "Teman Anda {$newFriend->name} baru saja mendaftar menggunakan kode referral Anda ({$referralCode}). Dapatkan komisi saat ia berbelanja!";

        // Push Notification ke HP Pengundang
        if (!empty($referrer->push_token)) {
            $this->expoPushService->sendNotification(
                $referrer->push_token,
                $title,
                $body,
                [
                    'url' => '/(tabs)/profile',
                    'type' => 'referral_friend_joined',
                    'friend_id' => $newFriend->id,
                ],
                'rewards-notifications'
            );
        }

        // Catat Message Log
        $this->logMessage($referrer, 'referral_friend_joined', $title, $body, $newFriend);
    }

    /**
     * 2. Notifikasi saat pesanan teman selesai dibuat & komisi dialokasikan.
     */
    public function notifyCommissionEarned(User $referrer, ReferralCommission $commission): void
    {
        $referrer->loadMissing('profile');
        $commission->loadMissing(['order', 'referee']);

        $amountFormatted = number_format($commission->commission_amount, 0, ',', '.');
        $orderNumber = $commission->order?->order_number ?? "#{$commission->order_id}";
        $friendName = $commission->referee?->name ?? 'Teman Anda';

        $title = "💰 Komisi Referral Baru (+Rp {$amountFormatted})";
        $body = "Kabar gembira! Anda mendapatkan komisi Rp {$amountFormatted} dari pesanan {$friendName} ({$orderNumber}). Dana dialokasikan ke saldo hold masa garansi H+7.";

        // Push Notification ke HP Pengundang
        if (!empty($referrer->push_token)) {
            $this->expoPushService->sendNotification(
                $referrer->push_token,
                $title,
                $body,
                [
                    'url' => '/(tabs)/profile',
                    'type' => 'referral_commission_earned',
                    'commission_id' => $commission->id,
                    'order_id' => $commission->order_id,
                ],
                'rewards-notifications'
            );
        }

        // Catat Message Log
        $this->logMessage($referrer, 'referral_commission_earned', $title, $body, $commission);
    }

    /**
     * 3. Notifikasi saat komisi referral lolos masa hold H+7 dan siap ditarik.
     */
    public function notifyCommissionMatured(User $referrer, float $totalAmount): void
    {
        $referrer->loadMissing('profile');
        $amountFormatted = number_format($totalAmount, 0, ',', '.');

        $title = "✨ Saldo Komisi Siap Ditarik!";
        $body = "Komisi sebesar Rp {$amountFormatted} telah melewati masa hold garansi dan kini berstatus APPROVED. Anda dapat menarik dana ke rekening bank sekarang!";

        if (!empty($referrer->push_token)) {
            $this->expoPushService->sendNotification(
                $referrer->push_token,
                $title,
                $body,
                [
                    'url' => '/(tabs)/profile',
                    'type' => 'referral_commission_matured',
                ],
                'rewards-notifications'
            );
        }

        $this->logMessage($referrer, 'referral_commission_matured', $title, $body);
    }

    /**
     * 4. Notifikasi saat pengajuan penarikan dana disetujui & ditransfer finance.
     */
    public function notifyWithdrawalTransferred(CommissionWithdrawal $withdrawal): void
    {
        $withdrawal->loadMissing(['user.profile', 'bankAccount']);
        $user = $withdrawal->user;
        if (!$user) return;

        $amountFormatted = number_format($withdrawal->net_amount, 0, ',', '.');
        $bankName = $withdrawal->bankAccount?->bank_name ?? 'Bank';

        $title = "💸 Penarikan Komisi Berhasil Ditransfer!";
        $body = "Dana komisi sebesar Rp {$amountFormatted} telah berhasil ditransfer ke rekening {$bankName} Anda. Terima kasih atas kerja samanya!";

        if (!empty($user->push_token)) {
            $this->expoPushService->sendNotification(
                $user->push_token,
                $title,
                $body,
                [
                    'url' => '/(tabs)/profile',
                    'type' => 'referral_withdrawal_transferred',
                    'withdrawal_id' => $withdrawal->id,
                ],
                'rewards-notifications'
            );
        }

        $this->logMessage($user, 'referral_withdrawal_transferred', $title, $body, $withdrawal);
    }

    /**
     * 5. Notifikasi saat pengajuan penarikan dana ditolak.
     */
    public function notifyWithdrawalRejected(CommissionWithdrawal $withdrawal, string $reason): void
    {
        $withdrawal->loadMissing(['user.profile', 'bankAccount']);
        $user = $withdrawal->user;
        if (!$user) return;

        $amountFormatted = number_format($withdrawal->amount, 0, ',', '.');

        $title = "⚠️ Pengajuan Penarikan Ditolak";
        $body = "Penarikan komisi Rp {$amountFormatted} belum dapat diproses: {$reason}. Saldo Anda tetap aman dan telah dikembalikan ke dompet.";

        if (!empty($user->push_token)) {
            $this->expoPushService->sendNotification(
                $user->push_token,
                $title,
                $body,
                [
                    'url' => '/(tabs)/profile',
                    'type' => 'referral_withdrawal_rejected',
                    'withdrawal_id' => $withdrawal->id,
                ],
                'rewards-notifications'
            );
        }

        $this->logMessage($user, 'referral_withdrawal_rejected', $title, $body, $withdrawal);
    }

    /**
     * Log pencatatan riwayat pesan notifikasi ke tabel message_logs.
     */
    protected function logMessage(User $user, string $messageType, string $subject, string $content, $source = null): void
    {
        try {
            $phone = MessageDispatchService::formatPhoneNumber($user->profile?->phone_number);

            MessageLog::create([
                'channel' => 'push_and_wa',
                'recipient' => $phone ?: ($user->email ?: (string)$user->id),
                'recipient_name' => $user->name,
                'subject' => $subject,
                'message_type' => $messageType,
                'content' => $content,
                'is_sent' => true,
                'status' => 'sent',
                'source_type' => $source ? get_class($source) : null,
                'source_id' => $source?->id ?? null,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("ReferralNotificationService log failed: " . $e->getMessage());
        }
    }
}