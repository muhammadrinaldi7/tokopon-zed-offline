<?php

namespace Tests\Unit;

use App\Jobs\ProcessAccurateWebhookJob;
use App\Webhooks\Accurate\ItemDeleteHandler;
use App\Webhooks\Accurate\ItemSaveHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AccurateWebhookJobHandlerResolutionTest extends TestCase
{
    public function test_it_resolves_item_delete_handler_correctly()
    {
        $job = new ProcessAccurateWebhookJob(1);
        $reflection = new ReflectionClass($job);
        $method = $reflection->getMethod('resolveHandler');
        $method->setAccessible(true);

        $this->assertEquals(ItemDeleteHandler::class, $method->invoke($job, 'ITEM_DELETE'));
        $this->assertEquals(ItemSaveHandler::class, $method->invoke($job, 'ITEM'));
        $this->assertEquals(ItemSaveHandler::class, $method->invoke($job, 'ITEM_SAVE'));
    }
}
