<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('region_code', 16);
            $table->string('plan_slug', 32);
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('sold')->default(0);
            $table->timestamps();

            $table->unique(['region_code', 'plan_slug']);
        });

        Schema::create('hosting_purchases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('plan_slug', 32);
            $table->string('region_code', 16);
            $table->string('billing_cycle', 16);
            $table->unsignedInteger('amount_usd');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('server_name')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['plan_slug', 'region_code', 'status']);
            $table->index('customer_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_purchases');
        Schema::dropIfExists('hosting_stocks');
    }
};
