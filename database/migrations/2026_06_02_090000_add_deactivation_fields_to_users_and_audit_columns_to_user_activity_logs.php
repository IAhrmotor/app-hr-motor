<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('disabled_at')->nullable()->after('activated_at');
            $table->foreignId('disabled_by')->nullable()->after('disabled_at')->constrained('users')->nullOnDelete();
            $table->string('disabled_reason', 255)->nullable()->after('disabled_by');
        });

        Schema::table('user_activity_logs', function (Blueprint $table): void {
            $table->string('result', 40)->nullable()->after('action');
            $table->text('reason')->nullable()->after('changes');
            $table->string('ip_address', 45)->nullable()->after('reason');
            $table->text('user_agent')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('user_activity_logs', function (Blueprint $table): void {
            $table->dropColumn(['result', 'reason', 'ip_address', 'user_agent']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('disabled_by');
            $table->dropColumn(['disabled_at', 'disabled_reason']);
        });
    }
};
