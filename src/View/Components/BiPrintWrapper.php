<?php

namespace iEducar\Packages\Bis\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class BiPrintWrapper extends Component
{
    public string $title;

    public function __construct(string $title = 'BI')
    {
        $this->title = $title;
    }

    public function render(): View
    {
        return view('bis::components.bi-print-wrapper', [
            'title' => $this->title,
        ]);
    }
}

