<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class BulletinComposerField extends Field
{
    protected string $view = 'filament.forms.components.bulletin-composer';

    protected ?int $maxLength = null;

    public function maxLength(?int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }
}
