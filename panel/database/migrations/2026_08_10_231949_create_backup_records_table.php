<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('game_server_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('status', 32)->default('pending');
            $table->unsignedBigInteger('bytes')->default(0);
            $table->string('local_path')->nullable();
            $table->string('cloud_key')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_records');
    }
};
