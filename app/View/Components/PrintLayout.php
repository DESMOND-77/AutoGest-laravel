<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PrintLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $backUrl = null,
    ) {}

    public function render(): View
    {
        return view('layouts.print');
    }
}
