<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->index('updated_at', 'conversations_updated_at_idx');
            $table->index('user_id', 'conversations_user_id_idx');
            $table->index('guest_phone', 'conversations_guest_phone_idx');
            $table->index('status', 'conversations_status_idx');
            $table->index('closing_status', 'conversations_closing_status_idx');
            $table->index('assigned_to_user_id', 'conversations_assigned_to_user_id_idx');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index('conversation_id', 'messages_conversation_id_idx');
            $table->index(['conversation_id', 'read_at'], 'messages_conv_read_idx');
            $table->index(['conversation_id', 'id'], 'messages_conv_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_updated_at_idx');
            $table->dropIndex('conversations_user_id_idx');
            $table->dropIndex('conversations_guest_phone_idx');
            $table->dropIndex('conversations_status_idx');
            $table->dropIndex('conversations_closing_status_idx');
            $table->dropIndex('conversations_assigned_to_user_id_idx');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_id_idx');
            $table->dropIndex('messages_conv_read_idx');
            $table->dropIndex('messages_conv_id_idx');
        });
    }
};
