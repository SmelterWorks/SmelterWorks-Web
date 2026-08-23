<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->unique();
            $table->string('ip_subnet', 45)->nullable();
            $table->string('user_agent_family')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('step_up_verified_at')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
