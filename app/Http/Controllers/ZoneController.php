<?php

namespace App\Http\Controllers;

use App\Models\Dealership;
use App\Models\Zone;
use App\Models\ZoneActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ZoneController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = $request->query('search');
        $sort = $request->query('sort', 'name');
        $direction = $request->query('direction', 'asc');

        $allowedSorts = ['name', 'dealerships_count', 'created_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'name';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $zones = Zone::query()
            ->withCount('dealerships')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('name', 'like', "%{$search}%")
                        ->orWhereHas('dealerships', function ($dealershipQuery) use ($search) {
                            $dealershipQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('admin.zones.partials.index-results', [
                    'zones' => $zones,
                    'search' => $search,
                    'sort' => $sort,
                    'direction' => $direction,
                ])->render(),
            ]);
        }

        return view('admin.zones.index', compact('zones', 'search', 'sort', 'direction'));
    }

    public function create(): View
    {
        return view('admin.zones.create', [
            'availableDealerships' => $this->availableDealerships(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateZoneRequest($request);

        $dealershipIds = collect($validated['dealership_ids'])
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $conflictingDealerships = $this->conflictingDealershipNames($dealershipIds->all());

        if ($conflictingDealerships !== []) {
            return back()
                ->withInput()
                ->withErrors([
                    'dealership_ids' => 'Hay delegaciones que ya pertenecen a otra zona: ' . implode(', ', $conflictingDealerships) . '.',
                ]);
        }

        $zone = DB::transaction(function () use ($request, $validated, $dealershipIds) {
            $zone = Zone::query()->create([
                'name' => $validated['name'],
            ]);

            $dealershipNames = $this->dealershipNames($dealershipIds->all());

            Dealership::query()
                ->whereIn('id', $dealershipIds->all())
                ->update(['zone_id' => $zone->id]);

            $this->storeActivityLog(
                actor: $request->user(),
                zone: $zone,
                action: ZoneActivityLog::ACTION_CREATED,
                changes: [
                    'Nombre' => ['from' => null, 'to' => $zone->name],
                    'Delegaciones' => ['from' => null, 'to' => implode(', ', $dealershipNames)],
                ],
                dealershipNames: $dealershipNames,
            );

            return $zone;
        });

        return redirect()
            ->route('admin.zones.index')
            ->with('success', 'Zona creada correctamente.');
    }

    public function edit(Zone $zone): View
    {
        $zone->load('dealerships');

        return view('admin.zones.edit', [
            'zone' => $zone,
            'availableDealerships' => $this->availableDealerships(),
        ]);
    }

    public function update(Request $request, Zone $zone): RedirectResponse
    {
        $validated = $this->validateZoneRequest($request, $zone);

        $dealershipIds = collect($validated['dealership_ids'])
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $conflictingDealerships = $this->conflictingDealershipNames($dealershipIds->all(), $zone);

        if ($conflictingDealerships !== []) {
            return back()
                ->withInput()
                ->withErrors([
                    'dealership_ids' => 'Hay delegaciones que ya pertenecen a otra zona: ' . implode(', ', $conflictingDealerships) . '.',
                ]);
        }

        $zone->load('dealerships');
        $previousDealershipIds = $zone->dealerships->pluck('id')->map(fn ($value) => (int) $value)->values();
        $changes = [];
        $previousDealershipNames = $this->dealershipNames($previousDealershipIds->all());
        $newDealershipNames = $this->dealershipNames($dealershipIds->all());

        if ($zone->name !== $validated['name']) {
            $changes['Nombre'] = ['from' => $zone->name, 'to' => $validated['name']];
        }

        $previousDealershipNamesSummary = implode(', ', $previousDealershipNames);
        $newDealershipNamesSummary = implode(', ', $newDealershipNames);

        if ($previousDealershipNamesSummary !== $newDealershipNamesSummary) {
            $changes['Delegaciones'] = ['from' => $previousDealershipNamesSummary, 'to' => $newDealershipNamesSummary];
        }

        DB::transaction(function () use ($request, $zone, $validated, $dealershipIds, $previousDealershipIds, $changes, $newDealershipNames): void {
            $zone->update([
                'name' => $validated['name'],
            ]);

            $removedDealershipIds = $previousDealershipIds->diff($dealershipIds)->values();
            $addedDealershipIds = $dealershipIds->diff($previousDealershipIds)->values();

            if ($removedDealershipIds->isNotEmpty()) {
                Dealership::query()
                    ->whereIn('id', $removedDealershipIds->all())
                    ->update(['zone_id' => null]);
            }

            if ($addedDealershipIds->isNotEmpty()) {
                Dealership::query()
                    ->whereIn('id', $addedDealershipIds->all())
                    ->update(['zone_id' => $zone->id]);
            }

            if ($changes !== []) {
                $this->storeActivityLog(
                    actor: $request->user(),
                    zone: $zone,
                    action: ZoneActivityLog::ACTION_UPDATED,
                    changes: $changes,
                    dealershipNames: $newDealershipNames,
                );
            }
        });

        return redirect()
            ->route('admin.zones.index')
            ->with('success', 'Zona actualizada correctamente.');
    }

    public function destroy(Request $request, Zone $zone): RedirectResponse
    {
        $zone->load('dealerships');
        $dealershipNames = $this->dealershipNames($zone->dealerships->pluck('id')->all());
        $dealershipNamesSummary = implode(', ', $dealershipNames);

        DB::transaction(function () use ($request, $zone, $dealershipNames, $dealershipNamesSummary): void {
            $zone->dealerships()->update(['zone_id' => null]);

            $this->storeActivityLog(
                actor: $request->user(),
                zone: $zone,
                action: ZoneActivityLog::ACTION_DELETED,
                changes: [
                    'Nombre' => ['from' => $zone->name, 'to' => null],
                    'Delegaciones' => ['from' => $dealershipNamesSummary, 'to' => null],
                ],
                dealershipNames: $dealershipNames,
            );

            $zone->delete();
        });

        return redirect()
            ->route('admin.zones.index')
            ->with('success', 'Zona eliminada correctamente.');
    }

    private function validateZoneRequest(Request $request, ?Zone $zone = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('zones', 'name')->ignore($zone?->id),
            ],
            'dealership_ids' => ['required', 'array', 'min:1'],
            'dealership_ids.*' => [
                'integer',
                Rule::exists('dealerships', 'id')->where(function ($query) use ($zone): void {
                    $query->whereNull('zone_id');

                    if ($zone) {
                        $query->orWhere('zone_id', $zone->id);
                    }
                }),
            ],
        ]);

        return $validated;
    }

    private function availableDealerships()
    {
        return Dealership::query()
            ->with('zone')
            ->orderBy('name')
            ->get();
    }

    private function conflictingDealershipNames(array $dealershipIds, ?Zone $zone = null): array
    {
        if ($dealershipIds === []) {
            return [];
        }

        return Dealership::query()
            ->with('zone')
            ->whereIn('id', $dealershipIds)
            ->whereNotNull('zone_id')
            ->when($zone, fn ($query) => $query->where('zone_id', '!=', $zone->id))
            ->get()
            ->map(fn (Dealership $dealership) => $dealership->name)
            ->all();
    }

    private function dealershipNames(array $dealershipIds): array
    {
        if ($dealershipIds === []) {
            return [];
        }

        return Dealership::query()
            ->whereIn('id', $dealershipIds)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function storeActivityLog(User $actor, Zone $zone, string $action, array $changes = [], array $dealershipNames = []): void
    {
        ZoneActivityLog::query()->create([
            'action' => $action,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_zone_id' => $zone->id,
            'target_name' => $zone->name,
            'target_dealerships' => $dealershipNames,
            'changes' => $changes === [] ? null : $changes,
            'created_at' => now(),
        ]);
    }
}
