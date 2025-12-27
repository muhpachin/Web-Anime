{{-- video-player.blade.php --}}
<div class="bg-slate-800 rounded-lg overflow-hidden shadow-xl">
    <div class="flex items-center justify-between p-4 border-b border-slate-700 bg-slate-900">
        <label class="text-gray-300 font-semibold text-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12l4-4m-4 4l4 4" />
            </svg>
            Pilih Server:
        </label>
        @if(count($episode->videoServers) > 0)
            <select 
                wire:change="selectServer($event.target.value)"
                class="px-4 py-2 rounded bg-slate-700 text-white text-sm font-semibold hover:bg-slate-600 transition cursor-pointer border-none focus:ring-2 focus:ring-red-500"
            >
                @foreach($episode->videoServers as $server)
                    <option value="{{ $server->id }}" {{ $selectedServerId === $server->id ? 'selected' : '' }}>
                        {{ $server->server_name }}
                    </option>
                @endforeach
            </select>
        @else
            <p class="text-gray-400 text-sm italic">No servers available</p>
        @endif
    </div>

    <div class="w-full bg-black relative aspect-video group">
        @if($selectedServer)
            @if(str_contains($selectedServer->embed_url, '<iframe'))
                {{-- Handle Full Iframe Tags --}}
                <div class="absolute inset-0 w-full h-full">
                    <style>
                        .video-wrapper iframe { 
                            width: 100% !important; 
                            height: 100% !important; 
                            position: absolute; 
                            top: 0; 
                            left: 0; 
                            border: none;
                        }
                    </style>
                    <div class="video-wrapper w-full h-full">
                        {!! $selectedServer->embed_url !!}
                    </div>
                </div>

            @elseif(str($selectedServer->embed_url)->lower()->endsWith('.mp4'))
                {{-- Handle Direct MP4 Links --}}
                <video src="{{ $selectedServer->embed_url }}" 
                       class="absolute inset-0 w-full h-full object-contain" 
                       controls 
                       preload="metadata"
                       autoplay>
                </video>

            @elseif(str($selectedServer->embed_url)->lower()->endsWith('.m3u8'))
                {{-- Handle HLS Streaming Links --}}
                <video id="hls-player-{{ $selectedServer->id }}" 
                       class="absolute inset-0 w-full h-full object-contain" 
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
                {{-- Handle Standard Embed URL (Iframe) --}}
                <iframe 
                    src="{{ $selectedServer->embed_url }}" 
                    class="absolute inset-0 w-full h-full border-none" 
                    allow="autoplay; fullscreen; picture-in-picture; encrypted-media; clipboard-write" 
                    allowfullscreen
                    referrerpolicy="no-referrer">
                </iframe>
            @else
                {{-- Handle Error/Invalid URL --}}
                <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 bg-slate-900 p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="font-semibold text-sm text-center">Tautan video tidak valid atau tidak didukung.</p>
                    <p class="text-[10px] mt-2 opacity-50 break-all max-w-xs text-center">{{ $selectedServer->embed_url }}</p>
                </div>
            @endif
        @else
            {{-- Placeholder when no server selected --}}
            <div class="absolute inset-0 flex items-center justify-center text-gray-400 bg-slate-900">
                <div class="text-center">
                    <div class="animate-pulse inline-block p-4 bg-slate-800 rounded-full mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium tracking-wide">Pilih server untuk memulai streaming</p>
                </div>
            </div>
        @endif
    </div>

    @if($selectedServer)
        <div class="p-4 border-t border-slate-700 bg-slate-900 flex items-center justify-between">
            <p class="text-gray-400 text-xs">
                Anda sedang menonton menggunakan <span class="text-red-500 font-bold uppercase tracking-wider">{{ $selectedServer->server_name }}</span>
            </p>
            <div class="flex gap-2">
                <span class="px-2 py-0.5 rounded bg-green-500/10 text-green-500 text-[10px] font-bold uppercase tracking-tight">HD Optimized</span>
            </div>
        </div>
    @endif
</div>