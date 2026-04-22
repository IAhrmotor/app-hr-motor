<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesAgendaExtensions;
use App\Models\Contact;
use App\Models\ContentActivityLog;
use App\Services\ContentActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    use HandlesAgendaExtensions;

    public function index(Request $request): View|JsonResponse
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
                            ->orWhere('enreach_extension', 'like', "%{$normalizedSearch}%");
                    }
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('admin.contacts.partials.index-results', [
                    'contacts' => $contacts,
                    'search' => $search,
                ])->render(),
            ]);
        }

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
            'enreach_extension' => $this->agendaExtensionRules(),
        ]);

        $this->agendaValidationHook($validator, ignoreContactId: null);

        $validated = $validator->validate();

        $contact = Contact::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'enreach_extension' => $validated['enreach_extension'] ?? null,
        ]);

        app(ContentActivityLogger::class)->record(
            actor: $request->user(),
            contentType: ContentActivityLog::CONTENT_TYPE_CONTACT,
            action: ContentActivityLog::ACTION_CREATED,
            targetName: $contact->name,
            targetReference: $contact->enreach_extension ?: $contact->phone,
            changes: [
                'name' => ['from' => null, 'to' => $contact->name],
                'email' => ['from' => null, 'to' => $contact->email],
                'phone' => ['from' => null, 'to' => $contact->phone],
                'enreach_extension' => ['from' => null, 'to' => $contact->enreach_extension],
            ],
        );

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
            'enreach_extension' => $this->agendaExtensionRules(),
        ]);

        $this->agendaValidationHook($validator, ignoreContactId: $contact->id);

        $validated = $validator->validate();

        $changes = [];

        if ($contact->name !== $validated['name']) {
            $changes['name'] = ['from' => $contact->name, 'to' => $validated['name']];
        }

        if ($contact->email !== ($validated['email'] ?? null)) {
            $changes['email'] = ['from' => $contact->email, 'to' => $validated['email'] ?? null];
        }

        if ($contact->phone !== ($validated['phone'] ?? null)) {
            $changes['phone'] = ['from' => $contact->phone, 'to' => $validated['phone'] ?? null];
        }

        if ($contact->enreach_extension !== ($validated['enreach_extension'] ?? null)) {
            $changes['enreach_extension'] = ['from' => $contact->enreach_extension, 'to' => $validated['enreach_extension'] ?? null];
        }

        $contact->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'enreach_extension' => $validated['enreach_extension'] ?? null,
        ]);

        app(ContentActivityLogger::class)->record(
            actor: $request->user(),
            contentType: ContentActivityLog::CONTENT_TYPE_CONTACT,
            action: ContentActivityLog::ACTION_UPDATED,
            targetName: $contact->name,
            targetReference: $contact->enreach_extension ?: $contact->phone,
            changes: $changes,
        );

        return redirect()
            ->route('admin.contacts.index')
            ->with('success', 'Contacto actualizado correctamente.');
    }

    public function destroy(Request $request, Contact $contact): RedirectResponse
    {
        app(ContentActivityLogger::class)->record(
            actor: $request->user(),
            contentType: ContentActivityLog::CONTENT_TYPE_CONTACT,
            action: ContentActivityLog::ACTION_DELETED,
            targetName: $contact->name,
            targetReference: $contact->enreach_extension ?: $contact->phone,
            changes: [
                'name' => ['from' => $contact->name, 'to' => null],
                'email' => ['from' => $contact->email, 'to' => null],
                'phone' => ['from' => $contact->phone, 'to' => null],
                'enreach_extension' => ['from' => $contact->enreach_extension, 'to' => null],
            ],
        );

        $contact->delete();

        return redirect()
            ->route('admin.contacts.index')
            ->with('success', 'Contacto eliminado correctamente.');
    }
}
