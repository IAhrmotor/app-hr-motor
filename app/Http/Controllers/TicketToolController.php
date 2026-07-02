<?php

namespace App\Http\Controllers;

use App\Models\TicketTool;
use App\Models\TicketToolActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketToolController extends Controller
{
    public function index(): View
    {
        return view('admin.ticket-tools.index', [
            'tools' => TicketTool::query()->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.ticket-tools.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:ticket_tools,name'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $tool = TicketTool::query()->create($validated);
        $this->recordActivity(
            request: $request,
            action: TicketToolActivityLog::ACTION_CREATED,
            tool: $tool,
            changes: [
                'name' => ['from' => null, 'to' => $tool->name],
                'color' => ['from' => null, 'to' => $tool->color],
            ],
        );

        return redirect()
            ->route('admin.ticket-tools.index')
            ->with('success', 'Herramienta creada correctamente.');
    }

    public function edit(TicketTool $ticketTool): View
    {
        return view('admin.ticket-tools.edit', [
            'tool' => $ticketTool,
        ]);
    }

    public function update(Request $request, TicketTool $ticketTool): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('ticket_tools', 'name')->ignore($ticketTool->id)],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $changes = [];

        if ($ticketTool->name !== $validated['name']) {
            $changes['name'] = ['from' => $ticketTool->name, 'to' => $validated['name']];
        }

        if ($ticketTool->color !== $validated['color']) {
            $changes['color'] = ['from' => $ticketTool->color, 'to' => $validated['color']];
        }

        $ticketTool->update($validated);
        $this->recordActivity(
            request: $request,
            action: TicketToolActivityLog::ACTION_UPDATED,
            tool: $ticketTool,
            changes: $changes,
        );

        return redirect()
            ->route('admin.ticket-tools.index')
            ->with('success', 'Herramienta actualizada correctamente.');
    }

    public function destroy(Request $request, TicketTool $ticketTool): RedirectResponse
    {
        $this->recordActivity(
            request: $request,
            action: TicketToolActivityLog::ACTION_DELETED,
            tool: $ticketTool,
            changes: [
                'name' => ['from' => $ticketTool->name, 'to' => null],
                'color' => ['from' => $ticketTool->color, 'to' => null],
            ],
        );

        $ticketTool->delete();

        return redirect()
            ->route('admin.ticket-tools.index')
            ->with('success', 'Herramienta eliminada correctamente.');
    }

    private function recordActivity(Request $request, string $action, TicketTool $tool, array $changes): void
    {
        TicketToolActivityLog::query()->create([
            'action' => $action,
            'actor_user_id' => $request->user()?->id,
            'actor_name' => $request->user()?->name ?? 'Sistema',
            'actor_email' => $request->user()?->email,
            'target_ticket_tool_id' => $tool->id,
            'target_name' => $tool->name,
            'target_color' => $tool->color,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
