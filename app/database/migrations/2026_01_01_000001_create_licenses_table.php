<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_code', 50)->unique();
            $table->string('buyer')->nullable();
            $table->string('buyer_email')->nullable();
            $table->string('license_type')->default('Regular License');
            $table->date('purchase_date')->nullable();
            $table->date('support_until')->nullable();
            $table->string('item_id')->nullable();

            // Domain binding
            $table->string('domain')->nullable();
            $table->string('domain_hash', 64)->nullable()->index();

            // Status: active, suspended, revoked
            $table->enum('status', ['active', 'suspended', 'revoked'])->default('active');
            $table->text('revoke_reason')->nullable();
            $table->timestamp('revoked_at')->nullable();

            // Tracking
            $table->unsignedInteger('verification_count')->default(0);
            $table->timestamp('last_verified_at')->nullable();
            $table->string('last_ip')->nullable();
            $table->string('last_version')->nullable();
            $table->unsignedInteger('pdf_count')->default(0);
            $table->timestamp('last_pdf_at')->nullable();

            // Envato data cache
            $table->json('envato_data')->nullable();
            $table->timestamp('envato_checked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
