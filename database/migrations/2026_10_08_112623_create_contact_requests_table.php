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
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->uuid('submission_token')->unique();

            $table->string('name', 100);
            $table->string('email');
            $table->string('phone', 30);
            $table->string('city', 40);
            $table->string('offer', 20)->nullable();
            $table->text('message')->nullable();

            // Email notification to the Nesel team.
            $table->string('notification_status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('notification_attempts')->default(0);
            $table->timestamp('notification_last_attempted_at')->nullable();
            $table->string('notification_last_error')->nullable();
            $table->timestamp('notification_sent_at')->nullable();

            // Delivery to the StartEntreprise "Suivi commercial" module (not wired yet).
            $table->string('crm_status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('crm_attempts')->default(0);
            $table->timestamp('crm_last_attempted_at')->nullable();
            $table->string('crm_last_error')->nullable();
            $table->uuid('crm_prospect_id')->nullable();
            $table->timestamp('crm_delivered_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_requests');
    }
};
