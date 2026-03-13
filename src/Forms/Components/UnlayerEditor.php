<?php

declare(strict_types=1);

namespace Mmabas77\FilamentUnlayer\Forms\Components;

use Filament\Forms\Components\Field;

class UnlayerEditor extends Field
{
    protected string $view = 'filament-unlayer::forms.components.unlayer-editor';

    protected array|\Closure $mergeTags = [];

    /** @param array|\Closure $tags */
    public function mergeTags(array|\Closure $tags): static
    {
        $this->mergeTags = $tags;

        return $this;
    }

    public function getMergeTags(): array
    {
        return $this->evaluate($this->mergeTags) ?? [];
    }
}
