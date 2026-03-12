<?php

namespace iEducar\Packages\Bis\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class BiPowered extends Component
{
    public ?string $author;

    public ?string $url;

    public function __construct(?string $author = null, ?string $url = null)
    {
        $this->author = $author;
        $this->url = $url;
    }

    public function render(): View
    {
        return view('bis::components.bi-powered', [
            'author' => $this->author,
            'url' => $this->url,
        ]);
    }
}

