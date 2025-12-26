<div class="bg-slate-800 rounded-lg overflow-hidden">
    <!-- Debug: Show server count -->
    <div class="text-xs text-gray-500 p-2 bg-slate-900">
        Servers: {{ count($episode->videoServers) }} | Selected ID: {{ $selectedServerId }}
    </div>

    <!-- Server Tabs -->
    <div class="flex flex-wrap gap-2 p-4 border-b border-slate-700 bg-slate-900">
        @if(count($episode->videoServers) > 0)
            @foreach($episode->videoServers as $server)
                <button 
                    wire:click="selectServer({{ $server->id }})"
                    @class([
                        'px-4 py-2 rounded font-semibold transition',
                        'bg-red-600 text-white' => $selectedServerId === $server->id,
                        'bg-slate-700 text-gray-300 hover:bg-slate-600' => $selectedServerId !== $server->id,
                    ])
                >
                    {{ $server->server_name }}
                </button>
            @endforeach
        @else
            <p class="text-gray-400 text-sm">No servers available</p>
        @endif
    </div>

    <!-- Video Player -->
    <div class="aspect-video bg-black relative">
        @if($selectedServer)
            <div class="w-full h-full flex items-center justify-center">
                @if(str_contains($selectedServer->embed_url, '<iframe'))
                    {!! $selectedServer->embed_url !!}
                @elseif(str($selectedServer->embed_url)->lower()->endsWith('.mp4'))
                    <video src="{{ $selectedServer->embed_url }}" class="w-full h-full" controls preload="metadata"></video>
                @elseif(str($selectedServer->embed_url)->lower()->endsWith('.m3u8'))
                    <video id="hls-player-{{ $selectedServer->id }}" class="w-full h-full" controls></video>
                    @push('scripts')
                        <script>
                            (function initHlsPlayer(){
                                const video = document.getElementById('hls-player-{{ $selectedServer->id }}');
                                const src = '{{ $selectedServer->embed_url }}';
                                if (!video) return;
                                function setup(){
                                    if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                        video.src = src;
                                    } else if (window.Hls) {
                                        const hls = new Hls({
                                            maxBufferLength: 30,
                                            enableWorker: true,
                                        });
                                        hls.loadSource(src);
                                        hls.attachMedia(video);
                                    }
                                }
                                if (!window.Hls) {
                                    var s=document.createElement('script');
                                    s.src='https://cdn.jsdelivr.net/npm/hls.js@latest';
                                    s.onload=setup;
                                    document.head.appendChild(s);
                                } else {
                                    setup();
                                }
                            })();
                        </script>
                    @endpush
                @elseif(str_contains($selectedServer->embed_url, 'http'))
                    <iframe 
                        src="{{ $selectedServer->embed_url }}" 
                        class="w-full h-full" 
                        frameborder="0" 
                        allow="autoplay; fullscreen; picture-in-picture; encrypted-media" 
                        allowfullscreen
                        referrerpolicy="no-referrer">
                    </iframe>
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                        <p>Invalid embed URL</p>
                        <p class="text-xs mt-2 text-gray-500 max-w-md break-all">{{ $selectedServer->embed_url }}</p>
                    </div>
                @endif
            </div>
        @else
            <div class="w-full h-full flex items-center justify-center">
                <p class="text-gray-400">No video available</p>
            </div>
        @endif
    </div>

    <!-- Server Info -->
    @if($selectedServer)
        <div class="p-4 border-t border-slate-700">
            <p class="text-gray-300 text-sm">
                <span class="font-semibold">Current Server:</span> {{ $selectedServer->server_name }}
            </p>
        </div>
    @endif
</div>
