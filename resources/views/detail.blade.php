@extends('layouts.app')

@section('title', $anime->title)

@section('content')
    <!-- Hero Section -->
    <div class="relative min-h-screen bg-gradient-to-b from-[#1a1d24] to-[#0f1115] overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <img src="{{ asset('storage/' . $anime->poster_image) }}" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-[#0f1115] via-[#0f1115]/50 to-transparent"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 py-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 items-start">
                <!-- Poster -->
                <div class="md:col-span-1">
                    <div class="sticky top-28">
                        <img src="{{ asset('storage/' . $anime->poster_image) }}" 
                             alt="{{ $anime->title }}"
                             class="w-full rounded-2xl shadow-2xl shadow-black/50 border-2 border-white/10 hover:border-red-600/50 transition-all">
                        @if($anime->episodes->count() > 0)
                            <a href="{{ route('watch', $anime->episodes->first()) }}" 
                               class="mt-6 w-full flex items-center justify-center px-6 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-red-600/30 uppercase tracking-wide">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                                Tonton Sekarang
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Info -->
                <div class="md:col-span-3">
                    <!-- Breadcrumb -->
                    <div class="flex items-center gap-2 mb-6 text-sm text-gray-400">
                        <a href="{{ route('home') }}" class="hover:text-red-500 transition">Home</a>
                        <span>/</span>
                        <a href="{{ route('search') }}" class="hover:text-red-500 transition">Anime</a>
                        <span>/</span>
                        <span class="text-white font-semibold line-clamp-1">{{ $anime->title }}</span>
                    </div>

                    <!-- Title & Status -->
                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6 leading-tight uppercase tracking-tight">
                        {{ $anime->title }}
                    </h1>

                    <!-- Stats Badges -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <div class="px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-black rounded-xl shadow-lg shadow-red-600/30 text-sm uppercase tracking-wider">
                            {{ $anime->type }}
                        </div>
                        <div class="px-5 py-3 bg-white/10 border border-white/20 text-white font-semibold rounded-xl backdrop-blur-sm text-sm uppercase tracking-wider">
                            {{ $anime->status }}
                        </div>
                        <div class="px-5 py-3 bg-yellow-500/20 border border-yellow-500/50 text-yellow-400 font-black rounded-xl text-sm uppercase tracking-wider">
                            ⭐ {{ number_format($anime->rating, 1) }}/10
                        </div>
                        @if($anime->release_year)
                            <div class="px-5 py-3 bg-white/10 border border-white/20 text-gray-300 font-semibold rounded-xl backdrop-blur-sm text-sm">
                                📅 {{ $anime->release_year }}
                            </div>
                        @endif
                        @if($anime->episodes->count() > 0)
                            <div class="px-5 py-3 bg-white/10 border border-white/20 text-gray-300 font-semibold rounded-xl backdrop-blur-sm text-sm">
                                📺 {{ $anime->episodes->count() }} Episode
                            </div>
                        @endif
                    </div>

                    <!-- Genres -->
                    <div class="mb-8">
                        <p class="text-gray-400 text-xs font-black uppercase tracking-widest mb-3">🎭 Genre</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($anime->genres as $genre)
                                <a href="{{ route('search', ['genre' => $genre->id]) }}" 
                                   class="px-4 py-2 bg-gradient-to-r from-red-600/20 to-red-700/20 hover:from-red-600 hover:to-red-700 text-red-400 hover:text-white border border-red-600/30 hover:border-red-600 rounded-lg transition-all text-sm font-bold uppercase tracking-wider">
                                    {{ $genre->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Synopsis -->
                    <div class="mb-8 bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-sm">
                        <p class="text-gray-400 text-xs font-black uppercase tracking-widest mb-4">📖 Sinopsis</p>
                        <p class="text-gray-200 leading-relaxed text-lg">{{ $anime->synopsis }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Episodes Section -->
    <div class="max-w-7xl mx-auto px-4 py-20">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
            <!-- Episodes List -->
            <div class="lg:col-span-3">
                <div class="mb-10">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-1.5 h-10 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                        <div>
                            <h2 class="text-3xl font-black text-white uppercase tracking-tight">Daftar Episode</h2>
                            <p class="text-gray-400 text-sm mt-1">{{ $anime->episodes->count() }} episode tersedia</p>
                        </div>
                    </div>
                </div>

                @if($anime->episodes->count() > 0)
                    <div class="space-y-3">
                        @foreach($anime->episodes as $episode)
                            <a href="{{ route('watch', $episode) }}" 
                               class="group flex items-center p-5 bg-gradient-to-r from-[#1a1d24] to-[#0f1115] border border-white/10 hover:border-red-600/50 rounded-2xl transition-all duration-300 hover:shadow-xl hover:shadow-red-600/20">
                                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center font-black text-white mr-5 group-hover:scale-110 transition-transform">
                                    {{ $episode->episode_number }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-bold text-lg group-hover:text-red-500 transition-colors">
                                        {{ $episode->title ?: "Episode " . $episode->episode_number }}
                                    </p>
                                    @if($episode->description)
                                        <p class="text-gray-400 text-sm line-clamp-1 mt-1">{{ $episode->description }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2 text-gray-400 text-sm ml-4 flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                    </svg>
                                    <span class="font-semibold">{{ $episode->videoServers->count() }}</span>
                                </div>
                                <div class="ml-4 text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-1.429 5.951 1.429a1 1 0 001.169-1.409l-7-14z"/>
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16 bg-white/5 border-2 border-dashed border-white/10 rounded-2xl">
                        <div class="text-4xl mb-4">🎬</div>
                        <p class="text-gray-400 font-semibold text-lg">Episode belum tersedia</p>
                        <p class="text-gray-500 text-sm mt-2">Cek kembali nanti untuk update episode terbaru</p>
                    </div>
                @endif
            </div>

            <!-- Related Animes Sidebar -->
            <aside>
                <div class="sticky top-28 bg-gradient-to-br from-[#1a1d24] to-[#0f1115] border-2 border-white/10 rounded-2xl p-6 hover:border-red-600/50 transition-all">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-1.5 h-6 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                        <h3 class="text-xl font-black text-white uppercase tracking-tight">Anime Serupa</h3>
                    </div>
                    <div class="space-y-4">
                        @if($relatedAnimes->count() > 0)
                            @foreach($relatedAnimes as $related)
                                <a href="{{ route('detail', $related) }}" 
                                   class="block group">
                                    <div class="relative h-40 bg-[#0f1115] rounded-xl overflow-hidden mb-3 border border-white/10 group-hover:border-red-600/50 transition-all shadow-lg">
                                        <img src="{{ asset('storage/' . $related->poster_image) }}" 
                                             alt="{{ $related->title }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <div class="absolute top-2 left-2 bg-red-600 text-[10px] font-black px-2 py-1 rounded text-white">
                                            {{ $related->type }}
                                        </div>
                                    </div>
                                    <p class="text-white font-bold text-sm group-hover:text-red-500 transition line-clamp-2">{{ $related->title }}</p>
                                    <p class="text-yellow-500 text-sm font-black mt-1">⭐ {{ number_format($related->rating, 1) }}</p>
                                </a>
                            @endforeach
                        @else
                            <p class="text-gray-400 text-sm text-center py-8">Tidak ada anime serupa</p>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1, h2 {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
