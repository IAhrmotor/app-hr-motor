<?php

use App\Models\CompanyChatGroup;
use App\Models\User;
use App\Services\CompanyChatDefaultGroupSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', User::ROLE_HR_NEWCARS)
            ->update([
                'role' => User::ROLE_USER,
                'extra_role' => User::ROLE_HR_NEWCARS,
            ]);

        $legacyGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_ROLE)
            ->where('system_group_key', User::ROLE_HR_NEWCARS)
            ->first();

        if ($legacyGroup) {
            $targetGroup = CompanyChatGroup::query()
                ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE)
                ->where('system_group_key', User::ROLE_HR_NEWCARS)
                ->first();

            if (! $targetGroup || $targetGroup->is($legacyGroup)) {
                $legacyGroup->forceFill([
                    'name' => User::extraRoleLabels()[User::ROLE_HR_NEWCARS] ?? 'HR NewCars',
                    'system_group_type' => CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE,
                    'system_group_key' => User::ROLE_HR_NEWCARS,
                ])->save();
            }
        }

        $syncService = app(CompanyChatDefaultGroupSyncService::class);

        User::query()
            ->where('extra_role', User::ROLE_HR_NEWCARS)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($syncService): void {
                foreach ($users as $user) {
                    $syncService->syncUser($user, false);
                }
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->where('extra_role', User::ROLE_HR_NEWCARS)
            ->where('role', User::ROLE_USER)
            ->update([
                'role' => User::ROLE_HR_NEWCARS,
                'extra_role' => null,
            ]);

        $legacyGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE)
            ->where('system_group_key', User::ROLE_HR_NEWCARS)
            ->first();

        if ($legacyGroup) {
            $legacyGroup->forceFill([
                'name' => 'HR NewCars',
                'system_group_type' => CompanyChatGroup::SYSTEM_GROUP_TYPE_ROLE,
                'system_group_key' => User::ROLE_HR_NEWCARS,
            ])->save();
        }

    }
};
