<?php

namespace App\Console\Commands;

use App\Models\Dealership;
use App\Models\User;
use App\Services\CompanyChatDefaultGroupSyncService;
use Illuminate\Console\Command;

class SyncCompanyChatDefaultGroups extends Command
{
    protected $signature = 'chat:sync-default-groups';

    protected $description = 'Sincroniza los grupos automáticos de chat por rol adicional y delegación para todos los usuarios existentes.';

    public function handle(CompanyChatDefaultGroupSyncService $syncService): int
    {
        $syncService->ensureDefaultGroupsExist();

        $syncedUsers = 0;

        User::query()
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$syncedUsers, $syncService): void {
                foreach ($users as $user) {
                    $syncService->syncUser($user, false);
                    $syncedUsers++;
                }
            });

        $syncedDealerships = 0;

        Dealership::query()
            ->orderBy('id')
            ->chunkById(100, function ($dealerships) use (&$syncedDealerships, $syncService): void {
                foreach ($dealerships as $dealership) {
                    $syncService->syncDealership($dealership);
                    $syncedDealerships++;
                }
            });

        $this->info(sprintf('Sincronizados %d usuarios y %d delegaciones.', $syncedUsers, $syncedDealerships));

        return self::SUCCESS;
    }
}
