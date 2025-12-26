<?php

namespace App\Livewire;

use App\Models\Episode;
use Livewire\Component;
use Illuminate\Support\Str;

class VideoPlayer extends Component
{
    public Episode $episode;
    public int $selectedServerId = 0;

    public function mount(Episode $episode)
    {
        $this->episode = $episode;
        if ($episode->videoServers->count() > 0) {
            $this->selectedServerId = $episode->videoServers->first()->id;
        }
    }

    public function selectServer($serverId)
    {
        $this->selectedServerId = $serverId;
    }

    public function render()
    {
        $selectedServer = null;
        if ($this->selectedServerId) {
            $selectedServer = $this->episode->videoServers()->find($this->selectedServerId);
        }

        return view('livewire.video-player', [
            'episode' => $this->episode,
            'selectedServer' => $selectedServer,
        ]);
    }
}
