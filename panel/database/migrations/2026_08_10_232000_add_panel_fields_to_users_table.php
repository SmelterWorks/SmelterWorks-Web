<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role', 32)->default('owner')->after('password');
            $table->timestamp('locked_until')->nullable()->after('role');
            $table->unsignedSmallInteger('failed_login_count')->default(0)->after('locked_until');
            $table->timestamp('password_changed_at')->nullable()->after('failed_login_count');
            $table->string('totp_secret')->nullable()->after('password_changed_at');
            $table->boolean('totp_enabled')->default(false)->after('totp_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn([
                'role',
                'locked_until',
                'failed_login_count',
                'password_changed_at',
                'totp_secret',
                'totp_enabled',
            ]);
        });
    }
};
