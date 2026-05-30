<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AccurateWebhookLog;

class ProcessAccurateWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $logId;

    /**
     * Create a new job instance.
     */
    public function __construct($logId)
    {
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = AccurateWebhookLog::find($this->logId);
        if (!$log || $log->status !== 'pending') {
            return;
        }

        $log->update(['status' => 'processing']);

        try {
            $handlerClass = $this->resolveHandler($log->event_type);
            
            if ($handlerClass) {
                $handler = new $handlerClass();
                $handler->handle($log);
            } else {
                \Illuminate\Support\Facades\Log::info("No specific handler for Accurate Webhook event: {$log->event_type}");
            }

            $log->update(['status' => 'success']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Accurate Webhook Job Failed: " . $e->getMessage());
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);
            
            // Re-throw so Laravel's queue manager knows it failed
            throw $e;
        }
    }

    private function resolveHandler(string $eventType): ?string
    {
        return match ($eventType) {
            'ITEM',
            'ITEM_SAVE' => \App\Webhooks\Accurate\ItemSaveHandler::class,
            
            'INVENTORY_ADJUSTMENT',
            'INVENTORY_TRANSFER',
            'PURCHASE_INVOICE',
            'RECEIVE_ITEM',
            'ITEM_QUANTITY',
            'STOCK_MUTATION',
            'ITEM_ADJUSTMENT' => \App\Webhooks\Accurate\StockChangeHandler::class,
            
            'SALES_INVOICE',
            'SALES_RECEIPT' => \App\Webhooks\Accurate\SalesInvoiceHandler::class,

            default => null,
        };
    }
}
