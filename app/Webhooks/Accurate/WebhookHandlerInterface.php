<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;

interface WebhookHandlerInterface
{
    public function handle(AccurateWebhookLog $log): void;
}
