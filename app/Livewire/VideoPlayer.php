<?php

namespace App\Livewire;

use App\Models\Episode;
use Livewire\Component;
use Illuminate\Support\Str;

class VideoPlayer extends Component
{
    public int $episodeId;
    public int $selectedServerId = 0;

    public function mount(Episode $episode)
    {
        $this->episodeId = $episode->id;
        
        // Get first active server
        $firstServer = $episode->videoServers()->where('is_active', true)->first();
        if ($firstServer) {
            $this->selectedServerId = $firstServer->id;
        }
    }

    public function selectServer($serverId)
    {
        $this->selectedServerId = $serverId;
    }

    public function render()
    {
        // Fresh load episode with active video servers
        $episode = Episode::with(['videoServers' => function($q) {
            $q->where('is_active', true);
        }])->find($this->episodeId);
        
        $selectedServer = null;
        if ($this->selectedServerId && $episode) {
            $selectedServer = $episode->videoServers->firstWhere('id', $this->selectedServerId);
        }

        return view('livewire.video-player', [
            'episode' => $episode,
            'selectedServer' => $selectedServer,
            'selectedServerId' => $this->selectedServerId,
        ]);
    }
}
