<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - nipnime</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-[#0f1115] text-gray-300 font-['Inter']">
    
    <nav class="bg-[#0f1115]/90 backdrop-blur-xl border-b border-white/5 sticky top-0 z-50 shadow-xl shadow-black/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                
                <div class="flex items-center gap-4 xl:gap-10"> {{-- Gap diubah ke xl --}}
                    <a href="{{ route('home') }}" class="group flex items-center gap-2 sm:gap-3 hover:scale-105 transition-transform flex-shrink-0">
                        <div class="w-9 h-9 sm:w-11 sm:h-11 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center shadow-lg shadow-red-600/30">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        </div>
                        <span class="text-xl sm:text-3xl font-black text-white tracking-tighter font-['Montserrat'] uppercase"><span class="text-red-600">nip</span>nime</span>
                    </a>
                    
                    {{-- Navigasi Desktop: Hanya muncul di layar sangat lebar (XL) agar tidak tabrakan di tablet --}}
                    <div class="hidden xl:flex items-center space-x-1 text-sm font-bold uppercase tracking-widest">
                        <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('home') ? 'text-red-500 bg-white/10' : '' }}">🏠 Home</a>
                        <a href="{{ route('search') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('search') ? 'text-red-500 bg-white/10' : '' }}">📺 Daftar Anime</a>
                        <a href="{{ route('schedule') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('schedule') ? 'text-red-500 bg-white/10' : '' }}">📅 Jadwal</a>
                        <a href="{{ route('search', ['type' => 'Movie']) }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition">🎬 Movie</a>
                        @auth
                        <a href="{{ route('request.index') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('request.*') ? 'text-red-500 bg-white/10' : '' }}">📝 Request</a>
                        @endauth
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-4 lg:gap-6">
                    {{-- Search Desktop: Hanya muncul di XL --}}
                    <form action="{{ route('search') }}" method="GET" class="hidden xl:block relative group">
                        <input type="text" name="search" placeholder="Cari anime..." 
                               class="w-48 xl:w-72 bg-[#1a1d24] border-2 border-white/10 text-white rounded-full px-5 py-2.5 text-sm focus:border-red-600 outline-none transition-all">
                    </form>

                    <div class="flex items-center gap-2 sm:gap-4">
                        @auth
                            <div class="flex items-center gap-2 sm:gap-3">
                                <span class="text-sm font-bold text-gray-300 hidden md:inline">{{ Auth::user()->name }}</span>
                                <div class="relative" id="profileDropdown">
                                    <button id="profileButton" class="w-9 h-9 sm:w-11 sm:h-11 rounded-full overflow-hidden bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center text-white text-sm sm:text-base font-black border-2 border-red-600/50">
                                        @if(Auth::user()->avatar)
                                            <img src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                                        @else
                                            {{ substr(Auth::user()->name, 0, 1) }}
                                        @endif
                                    </button>
                                    <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-[#1a1d24] rounded-xl shadow-xl opacity-0 invisible transition-all duration-300 border border-white/10 z-50">
                                        <div class="p-3 border-b border-white/10 text-xs font-bold text-white truncate">{{ Auth::user()->name }}</div>
                                        <a href="{{ route('profile.show') }}" class="block px-4 py-3 hover:bg-white/5 text-sm font-bold text-gray-300 hover:text-red-500 transition">👤 PROFIL</a>
                                        <form action="{{ route('auth.logout') }}" method="POST">@csrf<button type="submit" class="w-full text-left px-4 py-3 text-red-500 hover:bg-white/5 text-sm font-bold">🚪 LOGOUT</button></form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('auth.login') }}" class="text-xs sm:text-sm font-bold hover:text-red-500 px-2 sm:px-4 py-2 rounded-lg hover:bg-white/10 uppercase tracking-wider">🔐 Masuk</a>
                            <a href="{{ route('auth.register') }}" class="text-xs sm:text-sm font-bold px-3 sm:px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg shadow-lg uppercase tracking-wider">Daftar</a>
                        @endauth

                        {{-- SAKLAR EFEK (DESKTOP/TABLET) --}}
                        @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
                        <div class="hidden sm:flex items-center gap-2 border-l border-white/10 pl-4 ml-1 sm:ml-2">
                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" id="holidayToggle" class="sr-only peer" onchange="toggleHolidayEffect()">
                                <div class="w-10 h-5 bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-600"></div>
                                <span class="ml-2 text-[10px] font-black text-gray-400 uppercase tracking-widest peer-checked:text-red-500 hidden lg:inline transition-colors">Efek</span>
                            </label>
                        </div>
                        @endif

                        {{-- Tombol Hamburger: Muncul di Tablet & HP --}}
                        <button id="mobileMenuBtn" class="xl:hidden p-2 text-gray-400 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="mobileMenu" class="hidden xl:hidden border-t border-white/10 bg-[#0f1115]">
            <div class="px-4 py-4 space-y-2">
                <form action="{{ route('search') }}" method="GET" class="mb-4">
                    <input type="text" name="search" placeholder="Cari anime..." class="w-full bg-[#1a1d24] border border-white/10 text-white rounded-xl px-4 py-2.5 text-sm outline-none">
                </form>
                <a href="{{ route('home') }}" class="block px-4 py-3 rounded-xl text-sm font-bold {{ request()->routeIs('home') ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }} uppercase transition">🏠 Home</a>
                <a href="{{ route('search') }}" class="block px-4 py-3 rounded-xl text-sm font-bold {{ request()->routeIs('search') ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }} uppercase transition">📺 Daftar Anime</a>
                <a href="{{ route('schedule') }}" class="block px-4 py-3 rounded-xl text-sm font-bold {{ request()->routeIs('schedule') ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }} uppercase transition">📅 Jadwal</a>
                
                @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
                <div class="flex items-center justify-between px-4 py-3 rounded-xl text-gray-300 bg-white/5 border border-white/5 mt-4">
                    <span class="text-sm font-bold uppercase tracking-wider">✨ Efek Visual</span>
                    <label class="relative inline-flex items-center cursor-pointer select-none">
                        <input type="checkbox" id="holidayToggleMobile" class="sr-only peer" onchange="toggleHolidayEffect()">
                        <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                    </label>
                </div>
                @endif
            </div>
        </div>
    </nav>

    <main>@yield('content')</main>

    <footer class="bg-gradient-to-t from-[#000000] via-[#0a0c10] to-[#1a1d24] border-t border-white/10 py-10 mt-20 text-center">
        <div class="max-w-7xl mx-auto px-4 text-gray-500 text-xs sm:text-sm italic">
            © 2024 nipnime. All rights reserved.
        </div>
    </footer>

    @livewireScripts
    @livewire('notifications')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('profileButton');
            const profileMenu = document.getElementById('profileMenu');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');

            if (profileBtn && profileMenu) {
                profileBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    profileMenu.classList.toggle('opacity-0');
                    profileMenu.classList.toggle('invisible');
                });
                document.addEventListener('click', () => profileMenu.classList.add('opacity-0', 'invisible'));
            }

            if (mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
            }
        });
    </script>

    {{-- Script Efek Liburan (Tetap Sama) --}}
    @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
        <div id="holiday-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 99998;"></div>
        @if($holidaySettings['christmas']) <script src="https://unpkg.com/magic-snowflakes/dist/snowflakes.min.js"></script> @endif
        @if($holidaySettings['new_year']) <script src="https://unpkg.com/fireworks-js@2.x/dist/index.umd.js"></script> @endif
        <script>
            let holidayInstance = null;
            function startEffect() {
                const isDisabled = localStorage.getItem('nipnime_effects_disabled') === 'true';
                if(document.getElementById('holidayToggle')) document.getElementById('holidayToggle').checked = !isDisabled;
                if(document.getElementById('holidayToggleMobile')) document.getElementById('holidayToggleMobile').checked = !isDisabled;
                if (isDisabled) return;
                const container = document.getElementById('holiday-container');
                @if($holidaySettings['christmas'])
                    holidayInstance = new Snowflakes({ color: '#ffffff', container: container });
                @elseif($holidaySettings['new_year'])
                    holidayInstance = new Fireworks.default(container, { intensity: 20, autoresize: true });
                    holidayInstance.start();
                @endif
            }
            function toggleHolidayEffect() {
                const isDisabled = localStorage.getItem('nipnime_effects_disabled') === 'true';
                localStorage.setItem('nipnime_effects_disabled', isDisabled ? 'false' : 'true');
                location.reload(); 
            }
            document.addEventListener('DOMContentLoaded', startEffect);
        </script>
    @endif
</body>
</html>