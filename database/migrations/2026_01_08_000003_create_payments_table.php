<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('license_id')->constrained()->onDelete('cascade');

            // Provider info
            $table->string('provider')->default('stripe');
            $table->string('provider_payment_id')->unique();
            $table->string('provider_invoice_id')->nullable();

            // Amount
            $table->decimal('amount', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('EUR');

            // Status
            $table->string('status')->default('succeeded');
            // succeeded, pending, failed, refunded

            // Details
            $table->string('payment_method')->nullable();
            $table->string('description')->nullable();
            $table->string('receipt_url')->nullable();
            $table->string('invoice_pdf')->nullable();

            // Refund info
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();
            $table->string('refund_reason')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['license_id', 'status']);
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
