<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_commands', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('daemon_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 64);
            $table->string('status', 32)->default('pending');
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['daemon_registration_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commands');
    }
};
