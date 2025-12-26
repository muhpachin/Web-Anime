@extends('layouts.app')
@section('title', 'Nonton ' . $episode->anime->title . ' Ep ' . $episode->episode_number)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <div class="lg:col-span-3">
            <div class="rounded-2xl overflow-hidden shadow-2xl bg-black aspect-video border border-white/5">
                @livewire('video-player', ['episode' => $episode])
            </div>

            <div class="mt-8 bg-[#1a1d24] rounded-2xl p-8 border border-white/5">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <h1 class="text-3xl font-black text-white tracking-tight">
                        {{ $episode->anime->title }} <span class="text-red-600">- Ep {{ $episode->episode_number }}</span>
                    </h1>
                    <div class="flex gap-2">
                        <button class="px-4 py-2 bg-[#2a2e38] hover:bg-red-600 text-white text-xs font-bold rounded-lg transition">PREV</button>
                        <button class="px-4 py-2 bg-[#2a2e38] hover:bg-red-600 text-white text-xs font-bold rounded-lg transition">NEXT</button>
                    </div>
                </div>
                <p class="text-gray-400 leading-relaxed italic border-l-4 border-red-600 pl-4">{{ $episode->description }}</p>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="bg-[#1a1d24] rounded-2xl p-6 border border-white/5">
                <h3 class="text-lg font-black text-white mb-4 uppercase tracking-widest italic">Daftar Episode</h3>
                <div class="space-y-2 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($animeEpisodes as $ep)
                        <a href="{{ route('watch', $ep) }}" 
                           class="flex items-center gap-4 p-3 rounded-xl transition-all {{ $ep->id === $episode->id ? 'bg-red-600 text-white' : 'bg-[#2a2e38] hover:bg-[#343a46]' }}">
                            <div class="w-10 h-10 flex-shrink-0 bg-black/20 rounded-lg flex items-center justify-center font-bold">
                                {{ $ep->episode_number }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold truncate leading-tight uppercase">{{ $ep->title }}</p>
                                <p class="text-[10px] opacity-60">Baru Saja</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            
            <div class="bg-[#1a1d24] rounded-2xl p-4 border border-white/5">
                <img src="{{ asset('storage/' . $episode->anime->poster_image) }}" class="w-full h-40 object-cover rounded-xl mb-4 shadow-lg">
                <h4 class="text-sm font-black text-white uppercase">{{ $episode->anime->title }}</h4>
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-yellow-500 font-bold">★ {{ number_format($episode->anime->rating, 1) }}</span>
                    <span class="text-xs text-gray-500 italic">{{ $episode->anime->type }}</span>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #dc2626; border-radius: 10px; }
</style>
@endsection