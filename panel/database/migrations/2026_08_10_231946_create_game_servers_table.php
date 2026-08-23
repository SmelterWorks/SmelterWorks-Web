<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_servers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 32)->default('managed');
            $table->string('status', 32)->default('pending');
            $table->string('region_code', 32)->nullable();
            $table->unsignedInteger('ram_gb')->nullable();
            $table->unsignedInteger('storage_gb')->nullable();
            $table->string('connect_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_servers');
    }
};
