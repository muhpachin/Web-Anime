<div class="bg-slate-800 rounded-lg overflow-hidden">
    <!-- Server Selector Dropdown -->
    <div class="flex items-center justify-between p-4 border-b border-slate-700 bg-slate-900">
        <label class="text-gray-300 font-semibold text-sm">Pilih Server:</label>
        @if(count($episode->videoServers) > 0)
            <select 
                wire:change="selectServer($event.target.value)"
                class="px-4 py-2 rounded bg-slate-700 text-white text-sm font-semibold hover:bg-slate-600 transition cursor-pointer"
            >
                @foreach($episode->videoServers as $server)
                    <option value="{{ $server->id }}" {{ $selectedServerId === $server->id ? 'selected' : '' }}>
                        {{ $server->server_name }}
                    </option>
                @endforeach
            </select>
        @else
            <p class="text-gray-400 text-sm">No servers available</p>
        @endif
    </div>

    <!-- Video Player Container -->
    <div class="relative w-full bg-black" style="min-height: 500px; display: flex; align-items: center; justify-content: center;">
        @if($selectedServer)
            @if(str_contains($selectedServer->embed_url, '<iframe'))
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                    {!! $selectedServer->embed_url !!}
                </div>
            @elseif(str($selectedServer->embed_url)->lower()->endsWith('.mp4'))
                <video src="{{ $selectedServer->embed_url }}" 
                       style="width: 100%; height: 100%; max-height: 600px; object-fit: contain;" 
                       controls 
                       preload="metadata"
                       autoplay>
                </video>
            @elseif(str($selectedServer->embed_url)->lower()->endsWith('.m3u8'))
                <video id="hls-player-{{ $selectedServer->id }}" 
                       style="width: 100%; height: 100%; max-height: 600px; object-fit: contain;" 
                       controls
                       autoplay>
                </video>
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
                    style="width: 100%; height: 100%; min-height: 500px;" 
                    frameborder="0" 
                    allow="autoplay; fullscreen; picture-in-picture; encrypted-media" 
                    allowfullscreen
                    referrerpolicy="no-referrer">
                </iframe>
            @else
                <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9ca3af;">
                    <p>Invalid embed URL</p>
                    <p style="font-size: 12px; margin-top: 8px; color: #6b7280; max-width: 500px; word-break: break-all;">{{ $selectedServer->embed_url }}</p>
                </div>
            @endif
        @else
            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                <p>No video available</p>
            </div>
        @endif
    </div>

    </div>

    <!-- Server Info -->
    @if($selectedServer)
        <div class="p-4 border-t border-slate-700 bg-slate-900">
            <p class="text-gray-300 text-sm">
                <span class="font-semibold">Server Aktif:</span> {{ $selectedServer->server_name }}
            </p>
        </div>
    @endif
</div>
