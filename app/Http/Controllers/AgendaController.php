<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesAgendaExtensions;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AgendaController extends Controller
{
    use HandlesAgendaExtensions;

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $results = $this->paginateAgendaEntries($request, $search);
        $agendaStats = $this->buildAgendaStats();

        if ($this->isAjaxAgendaRequest($request)) {
            return response()->json([
                'html' => view('agenda.partials.results', compact('results'))->render(),
            ]);
        }

        return view('agenda.index', compact('results', 'search', 'agendaStats'));
    }

    protected function paginateAgendaEntries(Request $request, string $search): LengthAwarePaginator
    {
        $normalizedSearch = $this->normalizeAgendaValue($search);

        $entries = $this->buildEntries()
            ->filter(function (array $entry) use ($search, $normalizedSearch): bool {
                if ($search === '') {
                    return true;
                }

                $haystack = strtolower(implode(' ', array_filter([
                    $entry['name'] ?? '',
                    $entry['email'] ?? '',
                    $entry['phone'] ?? '',
                    $entry['enreach_extension'] ?? '',
                    $entry['subtitle'] ?? '',
                ])));

                if (str_contains($haystack, strtolower($search))) {
                    return true;
                }

                if ($normalizedSearch === null) {
                    return false;
                }

                return collect([
                    $entry['phone'] ?? null,
                    $entry['enreach_extension'] ?? null,
                ])->filter()->contains(fn (string $value) => str_contains($value, $normalizedSearch));
            })
            ->sortBy(fn (array $entry) => sprintf('%s-%s', strtolower($entry['name']), $entry['type']))
            ->values();

        $perPage = 12;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $entries->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $pageItems,
            $entries->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => collect($request->query())->except('ajax')->all(),
            ]
        );
    }

    protected function buildEntries(): Collection
    {
        $users = User::query()
            ->where('is_active', true)
            ->whereNull('disabled_at')
            ->select([
                'id',
                'name',
                'email',
                'avatar_path',
                'phone',
                'enreach_extension',
                'role',
                'extra_role',
                'dealership',
                'dealership_id',
            ])
            ->get()
            ->map(function (User $user): array {
                $extraRoleLabel = $user->extra_role
                    ? (User::extraRoleLabels()[$user->extra_role] ?? ucfirst((string) $user->extra_role))
                    : null;

                $subtitle = collect([
                    $extraRoleLabel,
                    $user->resolved_dealership_name,
                ])
                    ->filter()
                    ->join(' · ');

                return [
                    'type' => 'user',
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'enreach_extension' => $user->enreach_extension,
                    'subtitle' => $subtitle !== '' ? $subtitle : 'Sin delegación',
                    'route' => route('agenda.users.show', $user),
                    'avatar' => $user->avatar_url,
                ];
            });

        $contacts = Contact::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'enreach_extension',
            ])
            ->get()
            ->map(function (Contact $contact): array {
                return [
                    'type' => 'contact',
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'enreach_extension' => $contact->enreach_extension,
                    'subtitle' => 'Contacto externo',
                    'route' => route('agenda.contacts.show', $contact),
                    'avatar' => asset('images/users/hrmotor-default-user-avatar.png'),
                ];
            });

        return $users->merge($contacts);
    }

    protected function isAjaxAgendaRequest(Request $request): bool
    {
        return $request->boolean('ajax');
    }

    protected function buildAgendaStats(): array
    {
        return [
            'users_total' => User::query()
                ->where('is_active', true)
                ->whereNull('disabled_at')
                ->count(),
            'contacts' => Contact::query()->count(),
            'total' => User::query()
                ->where('is_active', true)
                ->whereNull('disabled_at')
                ->count() + Contact::query()->count(),
        ];
    }
}
