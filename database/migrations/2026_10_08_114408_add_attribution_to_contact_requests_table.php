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
            // First-touch attribution of the visitor's session (see App\Support\LeadAttribution).
            $table->string('landing_page_url', 2048)->nullable()->after('message');
            $table->string('referrer_url', 2048)->nullable()->after('landing_page_url');
            $table->string('utm_source', 150)->nullable()->after('referrer_url');
            $table->string('utm_medium', 150)->nullable()->after('utm_source');
            $table->string('utm_campaign', 150)->nullable()->after('utm_medium');
            $table->string('utm_term', 150)->nullable()->after('utm_campaign');
            $table->string('utm_content', 150)->nullable()->after('utm_term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->dropColumn(['landing_page_url', 'referrer_url', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']);
        });
    }
};
