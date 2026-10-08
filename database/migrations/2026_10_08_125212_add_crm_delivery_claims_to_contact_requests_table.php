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
            // When a request that failed temporarily becomes eligible again (null = now).
            $table->timestamp('crm_next_attempt_at')->nullable()->after('crm_last_error');
            // Short lease held by the process currently sending the request to StartEntreprise.
            $table->uuid('crm_claim_token')->nullable()->after('crm_next_attempt_at');
            $table->timestamp('crm_claimed_until')->nullable()->after('crm_claim_token');
            // Requests created before the synchronization start are only sent once an
            // administrator schedules them explicitly (contact-requests:crm-backfill).
            $table->timestamp('crm_backfill_requested_at')->nullable()->after('crm_claimed_until');

            $table->index(['crm_status', 'crm_next_attempt_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->dropIndex(['crm_status', 'crm_next_attempt_at']);
            $table->dropColumn(['crm_next_attempt_at', 'crm_claim_token', 'crm_claimed_until', 'crm_backfill_requested_at']);
        });
    }
};
