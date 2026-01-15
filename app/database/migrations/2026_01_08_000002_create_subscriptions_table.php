<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('restrict');

            // Status
            $table->string('status')->default('active');
            // active, past_due, canceled, unpaid, trialing, paused

            // Provider info
            $table->string('provider')->default('stripe'); // stripe, paypal
            $table->string('provider_subscription_id')->nullable();
            $table->string('provider_customer_id')->nullable();

            // Dates
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Billing
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('payment_method')->nullable();

            // Meta
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['license_id', 'status']);
            $table->index('provider_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
