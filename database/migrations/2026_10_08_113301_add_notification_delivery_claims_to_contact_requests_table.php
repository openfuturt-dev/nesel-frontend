<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contact_requests', function (Blueprint $table) {
            // When a failed notification becomes eligible again (null = now).
            $table->timestamp('notification_next_attempt_at')->nullable()->after('notification_last_error');
            // Short lease held by the process currently sending the notification.
            $table->uuid('notification_claim_token')->nullable()->after('notification_next_attempt_at');
            $table->timestamp('notification_claimed_until')->nullable()->after('notification_claim_token');

            $table->index(['notification_status', 'notification_next_attempt_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->dropIndex(['notification_status', 'notification_next_attempt_at']);
            $table->dropColumn(['notification_next_attempt_at', 'notification_claim_token', 'notification_claimed_until']);
        });
    }
};
