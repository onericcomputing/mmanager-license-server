<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->nullable()->constrained()->onDelete('set null');
            $table->string('purchase_code', 50)->index();
            $table->string('domain')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('action'); // verify, pdf, revoke, activate, suspend, reactivate
            $table->enum('status', ['success', 'failed', 'blocked'])->default('success');
            $table->string('failure_reason')->nullable();
            $table->string('version')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_logs');
    }
};
