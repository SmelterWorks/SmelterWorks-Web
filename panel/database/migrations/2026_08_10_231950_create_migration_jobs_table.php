<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_jobs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_server_id')->constrained('game_servers')->cascadeOnDelete();
            $table->foreignId('destination_server_id')->nullable()->constrained('game_servers')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->unsignedBigInteger('bytes')->default(0);
            $table->string('package_hash', 64)->nullable();
            $table->string('staging_key')->nullable();
            $table->timestamp('staging_expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_jobs');
    }
};
