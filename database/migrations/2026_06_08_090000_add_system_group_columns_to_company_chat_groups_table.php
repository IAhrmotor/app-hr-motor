<?php

use App\Models\CompanyChatGroup;
use App\Models\Dealership;
use App\Models\User;
use App\Services\CompanyChatDefaultGroupSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_chat_groups', function (Blueprint $table): void {
            $table->string('system_group_type', 32)->nullable()->after('name');
            $table->string('system_group_key', 255)->nullable()->after('system_group_type');
        });

        Schema::table('company_chat_groups', function (Blueprint $table): void {
            $table->unique(['system_group_type', 'system_group_key'], 'ccg_system_group_unique');
        });

        $syncService = app(CompanyChatDefaultGroupSyncService::class);
        $syncService->ensureDefaultGroupsExist();

        User::query()->orderBy('id')->chunkById(100, function ($users) use ($syncService): void {
            foreach ($users as $user) {
                $syncService->syncUser($user, false);
            }
        });

        Dealership::query()->orderBy('id')->chunkById(100, function ($dealerships) use ($syncService): void {
            foreach ($dealerships as $dealership) {
                $syncService->syncDealership($dealership);
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_chat_groups', function (Blueprint $table): void {
            $table->dropUnique('ccg_system_group_unique');
            $table->dropColumn(['system_group_type', 'system_group_key']);
        });

        CompanyChatGroup::query()
            ->whereNotNull('system_group_type')
            ->whereNotNull('system_group_key')
            ->update([
                'system_group_type' => null,
                'system_group_key' => null,
            ]);
    }
};
