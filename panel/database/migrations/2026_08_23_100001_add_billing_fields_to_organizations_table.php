<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('stripe_customer_id')->nullable()->after('slug');
            $table->string('billing_email')->nullable()->after('stripe_customer_id');
        });

        Schema::table('game_servers', function (Blueprint $table): void {
            $table->string('plan_slug', 32)->nullable()->after('type');
            $table->string('stripe_subscription_id')->nullable()->after('plan_slug');
            $table->string('billing_cycle', 16)->nullable()->after('stripe_subscription_id');
            $table->foreignId('host_node_id')->nullable()->after('daemon_registration_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_servers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('host_node_id');
            $table->dropColumn(['plan_slug', 'stripe_subscription_id', 'billing_cycle']);
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn(['stripe_customer_id', 'billing_email']);
        });
    }
};
