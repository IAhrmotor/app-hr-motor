<?php

namespace App\Http\Controllers;

use App\Models\Dealership;
use App\Models\DealershipActivityLog;
use App\Models\PurchaseLeaderboardEntry;
use App\Models\SalesLeaderboardEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DealershipController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $sort = $request->query('sort', 'name');
        $direction = $request->query('direction', 'asc');

        $allowedSorts = ['name', 'salesforce_id', 'created_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'name';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $dealerships = Dealership::query()
            ->withCount('users')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('name', 'like', "%{$search}%")
                        ->orWhere('salesforce_id', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('google_maps_url', 'like', "%{$search}%")
                        ->orWhere('reviews_url', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return view('dealerships.index', compact('dealerships', 'search', 'sort', 'direction'));
    }

    public function create(): View
    {
        return view('dealerships.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:dealerships,name'],
            'salesforce_id' => ['required', 'string', 'max:255', 'unique:dealerships,salesforce_id'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'phone' => ['required', 'string', 'max:255'],
            'google_maps_url' => ['required', 'url', 'max:255'],
            'reviews_url' => ['required', 'url', 'max:255'],
        ]);

        $dealership = Dealership::create([
            'name' => $validated['name'],
            'salesforce_id' => $validated['salesforce_id'],
            'phone' => $validated['phone'],
            'google_maps_url' => $validated['google_maps_url'],
            'reviews_url' => $validated['reviews_url'],
        ]);

        $dealership->image_path = $this->storeImage($request, $dealership);
        $dealership->save();

        $this->storeActivityLog(
            actor: $request->user(),
            dealership: $dealership,
            action: DealershipActivityLog::ACTION_CREATED,
        );

        return redirect()
            ->route('dealerships.index')
            ->with('success', 'Delegacion creada correctamente.');
    }

    public function show(Dealership $dealership): View
    {
        $dealership->load(['users' => fn ($query) => $query->orderBy('name')]);

        $monthlyPerformance = $this->buildMonthlyPerformanceData($dealership);

        return view('dealerships.show', [
            'dealership' => $dealership,
            'userMonthlyStats' => $monthlyPerformance['user_stats'],
            'dealershipMonthlyRankings' => $monthlyPerformance['dealership_rankings'],
        ]);
    }

    public function edit(Dealership $dealership): View
    {
        return view('dealerships.edit', compact('dealership'));
    }

    public function update(Request $request, Dealership $dealership): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:dealerships,name,' . $dealership->id],
            'salesforce_id' => ['required', 'string', 'max:255', 'unique:dealerships,salesforce_id,' . $dealership->id],
            'image' => [$dealership->image_path ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'phone' => ['required', 'string', 'max:255'],
            'google_maps_url' => ['required', 'url', 'max:255'],
            'reviews_url' => ['required', 'url', 'max:255'],
        ]);

        $changes = $this->buildChangeSet($dealership, [
            'name' => $validated['name'],
            'salesforce_id' => $validated['salesforce_id'],
            'phone' => $validated['phone'],
            'google_maps_url' => $validated['google_maps_url'],
            'reviews_url' => $validated['reviews_url'],
        ]);

        $dealership->fill([
            'name' => $validated['name'],
            'salesforce_id' => $validated['salesforce_id'],
            'phone' => $validated['phone'],
            'google_maps_url' => $validated['google_maps_url'],
            'reviews_url' => $validated['reviews_url'],
        ]);

        if ($request->hasFile('image')) {
            $dealership->image_path = $this->storeImage($request, $dealership);
            $changes['Imagen'] = [
                'from' => 'Anterior',
                'to' => 'Actualizada',
            ];
        }

        $dealership->save();

        $dealership->users()->update([
            'dealership' => $dealership->name,
        ]);

        if ($changes !== []) {
            $this->storeActivityLog(
                actor: $request->user(),
                dealership: $dealership,
                action: DealershipActivityLog::ACTION_UPDATED,
                changes: $changes,
            );
        }

        return redirect()
            ->route('dealerships.index')
            ->with('success', 'Delegacion actualizada correctamente.');
    }

    public function destroy(Dealership $dealership): RedirectResponse
    {
        if ($dealership->users()->exists()) {
            return redirect()
                ->route('dealerships.index')
                ->with('error', 'No puedes eliminar una delegacion con usuarios asignados.');
        }

        $this->storeActivityLog(
            actor: request()->user(),
            dealership: $dealership,
            action: DealershipActivityLog::ACTION_DELETED,
        );

        $this->deleteImage($dealership);
        $dealership->delete();

        return redirect()
            ->route('dealerships.index')
            ->with('success', 'Delegacion eliminada correctamente.');
    }

    private function storeImage(Request $request, Dealership $dealership): string
    {
        $directory = public_path('images/dealerships');
        File::ensureDirectoryExists($directory);

        $image = $request->file('image');
        $extension = $image->getClientOriginalExtension() ?: $image->extension() ?: 'png';
        $filename = sprintf('%s-%s.%s', $dealership->id, Str::uuid(), strtolower($extension));
        $image->move($directory, $filename);

        $this->deleteImage($dealership);

        return 'images/dealerships/' . $filename;
    }

    private function deleteImage(Dealership $dealership): void
    {
        if (! $dealership->image_path) {
            return;
        }

        $path = public_path($dealership->image_path);

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    private function storeActivityLog(User $actor, Dealership $dealership, string $action, array $changes = []): void
    {
        DealershipActivityLog::query()->create([
            'action' => $action,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_dealership_id' => $dealership->id,
            'target_name' => $dealership->name,
            'target_salesforce_id' => $dealership->salesforce_id,
            'target_phone' => $dealership->phone,
            'changes' => $changes === [] ? null : $changes,
            'created_at' => now(),
        ]);
    }

    private function buildChangeSet(Dealership $dealership, array $newValues): array
    {
        $labels = [
            'name' => 'Nombre',
            'salesforce_id' => 'ID Salesforce',
            'phone' => 'Telefono',
            'google_maps_url' => 'Google Maps',
            'reviews_url' => 'Resenas',
        ];

        return collect($newValues)
            ->filter(fn ($value, $field) => $dealership->{$field} !== $value)
            ->mapWithKeys(fn ($value, $field) => [
                $labels[$field] ?? $field => [
                    'from' => $dealership->{$field},
                    'to' => $value,
                ],
            ])
            ->all();
    }

    private function buildMonthlyPerformanceData(Dealership $dealership): array
    {
        $userStats = $dealership->users
            ->mapWithKeys(fn (User $user) => [$user->id => ['sales' => 0, 'purchases' => 0]])
            ->all();

        $dealershipRankings = [
            'sales' => null,
            'purchases' => null,
        ];

        if (! Schema::hasTable('sales_leaderboard_entries') || ! Schema::hasTable('purchase_leaderboard_entries')) {
            return [
                'user_stats' => $userStats,
                'dealership_rankings' => $dealershipRankings,
            ];
        }

        $salesEntries = SalesLeaderboardEntry::query()
            ->with(['user.assignedDealership'])
            ->when($this->excludedLeaderboardUserIds() !== [], function ($query) {
                $query->whereNotIn('salesforce_user_id', $this->excludedLeaderboardUserIds());
            })
            ->get();

        $purchaseEntries = PurchaseLeaderboardEntry::query()
            ->with(['user.assignedDealership'])
            ->when($this->excludedLeaderboardUserIds() !== [], function ($query) {
                $query->whereNotIn('salesforce_user_id', $this->excludedLeaderboardUserIds());
            })
            ->get();

        foreach ($salesEntries as $entry) {
            if ($entry->user_id && array_key_exists($entry->user_id, $userStats)) {
                $userStats[$entry->user_id]['sales'] = (int) round((float) $entry->total_sales);
            }
        }

        foreach ($purchaseEntries as $entry) {
            if ($entry->user_id && array_key_exists($entry->user_id, $userStats)) {
                $userStats[$entry->user_id]['purchases'] = (int) round((float) $entry->total_purchases);
            }
        }

        $dealershipRankings['sales'] = $this->resolveDealershipRankingPosition(
            dealership: $dealership,
            entries: $salesEntries,
            metricField: 'total_sales'
        );

        $dealershipRankings['purchases'] = $this->resolveDealershipRankingPosition(
            dealership: $dealership,
            entries: $purchaseEntries,
            metricField: 'total_purchases'
        );

        return [
            'user_stats' => $userStats,
            'dealership_rankings' => $dealershipRankings,
        ];
    }

    private function resolveDealershipRankingPosition(Dealership $dealership, Collection $entries, string $metricField): ?int
    {
        $position = $entries
            ->groupBy(function ($entry): string {
                $dealershipId = $entry->user?->assignedDealership?->getKey();

                if ($dealershipId !== null) {
                    return 'id:' . $dealershipId;
                }

                $dealershipName = trim((string) ($entry->user?->resolved_dealership_name ?? ''));

                return 'name:' . ($dealershipName !== '' ? Str::lower($dealershipName) : 'sin-delegacion-asignada');
            })
            ->map(function (Collection $dealershipEntries, string $groupKey) use ($metricField) {
                $representative = $dealershipEntries->first();
                $dealershipName = trim((string) ($representative->user?->resolved_dealership_name ?? ''));

                return [
                    'group_key' => $groupKey,
                    'metric_total' => (float) $dealershipEntries->sum($metricField),
                    'name' => $dealershipName !== '' ? $dealershipName : 'Sin delegacion asignada',
                ];
            })
            ->sort(function (array $left, array $right) {
                $metricComparison = $right['metric_total'] <=> $left['metric_total'];

                if ($metricComparison !== 0) {
                    return $metricComparison;
                }

                return strcmp($left['name'], $right['name']);
            })
            ->values()
            ->search(fn (array $entry): bool => $entry['group_key'] === 'id:' . $dealership->id);

        return $position === false ? null : $position + 1;
    }

    private function excludedLeaderboardUserIds(): array
    {
        return config('services.salesforce.excluded_leaderboard_user_ids', []);
    }
}
