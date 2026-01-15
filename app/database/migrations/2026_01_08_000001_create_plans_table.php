<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('interval')->default('month'); // month, year
            $table->integer('interval_count')->default(1);
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('EUR');

            // Stripe
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_product_id')->nullable();

            // PayPal
            $table->string('paypal_plan_id')->nullable();

            // Features/limits
            $table->integer('pdf_limit')->nullable(); // null = unlimited
            $table->integer('api_limit')->nullable();
            $table->json('features')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
