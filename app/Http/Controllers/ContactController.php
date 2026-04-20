<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesAgendaExtensions;
use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    use HandlesAgendaExtensions;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $normalizedSearch = $this->normalizeAgendaValue($search);

        $contacts = Contact::query()
            ->when($search !== '' || $normalizedSearch, function ($query) use ($search, $normalizedSearch): void {
                $query->where(function ($subquery) use ($search, $normalizedSearch): void {
                    if ($search !== '') {
                        $subquery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    }

                    if ($normalizedSearch) {
                        $subquery->orWhere('phone', 'like', "%{$normalizedSearch}%")
                            ->orWhere('threecx_extension', 'like', "%{$normalizedSearch}%")
                            ->orWhere('enreach_phone', 'like', "%{$normalizedSearch}%")
                            ->orWhere('enreach_extension', 'like', "%{$normalizedSearch}%");
                    }
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.contacts.index', compact('contacts', 'search'));
    }

    public function create(): View
    {
        return view('admin.contacts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => $this->agendaPhoneRules(),
            'threecx_extension' => $this->agendaExtensionRules(required: true),
            'enreach_phone' => $this->agendaPhoneRules(),
            'enreach_extension' => $this->agendaExtensionRules(),
        ]);

        $this->agendaValidationHook($validator, ignoreContactId: null);

        $validated = $validator->validate();

        Contact::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'threecx_extension' => $validated['threecx_extension'],
            'enreach_phone' => $validated['enreach_phone'] ?? null,
            'enreach_extension' => $validated['enreach_extension'] ?? null,
        ]);

        return redirect()
            ->route('admin.contacts.index')
            ->with('success', 'Contacto creado correctamente.');
    }

    public function show(Contact $contact): View
    {
        return view('admin.contacts.show', compact('contact'));
    }

    public function edit(Contact $contact): View
    {
        return view('admin.contacts.edit', compact('contact'));
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => $this->agendaPhoneRules(),
            'threecx_extension' => $this->agendaExtensionRules(required: true),
            'enreach_phone' => $this->agendaPhoneRules(),
            'enreach_extension' => $this->agendaExtensionRules(),
        ]);

        $this->agendaValidationHook($validator, ignoreContactId: $contact->id);

        $validated = $validator->validate();

        $contact->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'threecx_extension' => $validated['threecx_extension'],
            'enreach_phone' => $validated['enreach_phone'] ?? null,
            'enreach_extension' => $validated['enreach_extension'] ?? null,
        ]);

        return redirect()
            ->route('admin.contacts.index')
            ->with('success', 'Contacto actualizado correctamente.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()
            ->route('admin.contacts.index')
            ->with('success', 'Contacto eliminado correctamente.');
    }
}
