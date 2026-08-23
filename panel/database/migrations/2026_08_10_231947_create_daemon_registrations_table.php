<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daemon_registrations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash');
            $table->string('token_prefix', 16);
            $table->text('hub_public_key');
            $table->text('hub_private_key');
            $table->string('fingerprint')->nullable()->unique();
            $table->string('status', 32)->default('pending');
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daemon_registrations');
    }
};
