<?php

namespace App\Http\Controllers;

use App\Models\Dealership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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

        return redirect()
            ->route('dealerships.index')
            ->with('success', 'Delegación creada correctamente.');
    }

    public function show(Dealership $dealership): View
    {
        $dealership->load(['users' => fn ($query) => $query->orderBy('name')]);

        return view('dealerships.show', compact('dealership'));
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

        $dealership->fill([
            'name' => $validated['name'],
            'salesforce_id' => $validated['salesforce_id'],
            'phone' => $validated['phone'],
            'google_maps_url' => $validated['google_maps_url'],
            'reviews_url' => $validated['reviews_url'],
        ]);

        if ($request->hasFile('image')) {
            $dealership->image_path = $this->storeImage($request, $dealership);
        }

        $dealership->save();

        $dealership->users()->update([
            'dealership' => $dealership->name,
        ]);

        return redirect()
            ->route('dealerships.index')
            ->with('success', 'Delegación actualizada correctamente.');
    }

    public function destroy(Dealership $dealership): RedirectResponse
    {
        if ($dealership->users()->exists()) {
            return redirect()
                ->route('dealerships.index')
                ->with('error', 'No puedes eliminar una delegación con usuarios asignados.');
        }

        $this->deleteImage($dealership);
        $dealership->delete();

        return redirect()
            ->route('dealerships.index')
            ->with('success', 'Delegación eliminada correctamente.');
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
}
