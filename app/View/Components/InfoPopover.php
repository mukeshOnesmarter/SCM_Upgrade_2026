<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InfoPopover extends Component
{
    public string $title;

    public string $route;

    public string $chapter;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $title = 'Info', string $route = '#', string $chapter = '1')
    {
        $this->title = $title;
        $this->route = $route;
        $this->chapter = $chapter;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|\Closure|string
     */
    public function render()
    {
        return view('components.info-popover');
    }
}
