<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_nodes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 120);
            $table->string('region_code', 32);
            $table->foreignId('daemon_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('capacity_ram_gb')->default(64);
            $table->unsignedInteger('used_ram_gb')->default(0);
            $table->unsignedSmallInteger('max_servers')->default(8);
            $table->unsignedSmallInteger('active_servers')->default(0);
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['region_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_nodes');
    }
};
