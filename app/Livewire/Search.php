<?php

namespace App\Livewire;

use Livewire\Component;

class Search extends Component
{
    public $query;
    
    public function render()
    {
        return view('livewire.search');
    }
}
