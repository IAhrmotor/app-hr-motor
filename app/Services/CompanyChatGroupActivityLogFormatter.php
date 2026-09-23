<?php

namespace App\Services;

use App\Models\CompanyChatGroupActivityLog;

class CompanyChatGroupActivityLogFormatter
{
    public function format(CompanyChatGroupActivityLog $record): string
    {
        $changes = $record->changes ?? [];
        $lines = [];

        if (array_key_exists('name_before', $changes) || array_key_exists('name_after', $changes)) {
            $before = $changes['name_before'] ?? null;
            $after = $changes['name_after'] ?? null;

            if ($record->action !== CompanyChatGroupActivityLog::ACTION_UPDATED || $before !== $after) {
                $lines[] = $this->line('Nombre', $before, $after);
            }
        } elseif (isset($changes['name']) && is_array($changes['name'])) {
            $lines[] = $this->line('Nombre', $changes['name']['from'] ?? null, $changes['name']['to'] ?? null);
        }

        if (array_key_exists('participants_added', $changes)) {
            $this->appendListLine($lines, 'Participantes añadidos', $changes['participants_added']);
        }

        if (array_key_exists('participants_removed', $changes)) {
            $this->appendListLine($lines, 'Participantes eliminados', $changes['participants_removed']);
        }

        if (array_key_exists('participants_after', $changes) && ! array_key_exists('participants_added', $changes)) {
            $this->appendListLine($lines, 'Participantes', $changes['participants_after']);
        }

        if (array_key_exists('participants_before', $changes) && $record->action === CompanyChatGroupActivityLog::ACTION_DELETED) {
            $this->appendListLine($lines, 'Participantes', $changes['participants_before']);
        }

        if (isset($changes['participants']) && is_array($changes['participants'])) {
            $before = $this->names($changes['participants']['from'] ?? null);
            $after = $this->names($changes['participants']['to'] ?? null);

            if ($record->action !== CompanyChatGroupActivityLog::ACTION_UPDATED) {
                $this->appendListLine($lines, 'Participantes', $after ?: $before);

                return implode("\n", $lines);
            }

            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            if ($added !== []) {
                $this->appendListLine($lines, 'Participantes añadidos', $added);
            }

            if ($removed !== []) {
                $this->appendListLine($lines, 'Participantes eliminados', $removed);
            }

        }

        if ($lines === [] && $changes !== []) {
            foreach ($changes as $field => $change) {
                if (in_array($field, ['name_before', 'name_after', 'participants_before', 'participants_after', 'participants_added', 'participants_removed'], true)) {
                    continue;
                }

                if (is_array($change) && (array_key_exists('from', $change) || array_key_exists('to', $change))) {
                    $lines[] = $this->line($this->translateField((string) $field), $change['from'] ?? null, $change['to'] ?? null);
                }
            }
        }

        if ($lines !== []) {
            return implode("\n", $lines);
        }

        return match ($record->action) {
            CompanyChatGroupActivityLog::ACTION_CREATED => 'Creación del grupo',
            CompanyChatGroupActivityLog::ACTION_UPDATED => 'Sin cambios adicionales registrados',
            CompanyChatGroupActivityLog::ACTION_DELETED => 'Eliminación del grupo',
            default => 'Sin detalles registrados',
        };
    }

    private function appendListLine(array &$lines, string $label, mixed $value): void
    {
        $names = $this->names($value);

        if ($names !== []) {
            $lines[] = $label.': '.implode(', ', $names);
        }
    }

    private function line(string $label, mixed $before, mixed $after): string
    {
        return sprintf('%s: %s → %s', $label, $this->display($before), $this->display($after));
    }

    private function names(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->flatten()
                ->filter(fn (mixed $name): bool => filled($name))
                ->map(fn (mixed $name): string => trim((string) $name))
                ->values()
                ->all();
        }

        if (blank($value)) {
            return [];
        }

        return collect(explode(',', (string) $value))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->values()
            ->all();
    }

    private function display(mixed $value): string
    {
        if (is_array($value)) {
            $names = $this->names($value);

            return $names === [] ? 'Vacío' : implode(', ', $names);
        }

        return blank($value) ? 'Vacío' : (string) $value;
    }

    private function translateField(string $field): string
    {
        return match ($field) {
            'name' => 'Nombre',
            'participants' => 'Participantes',
            'success' => 'Correcto',
            'failure', 'error' => 'Error',
            'empty' => 'Vacío',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }
}
