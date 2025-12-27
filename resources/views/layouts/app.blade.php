<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - nipnime</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-[#0f1115] text-gray-300 font-['Inter'] overflow-x-hidden">
    
    <nav class="bg-[#0f1115]/95 backdrop-blur-xl border-b border-white/5 sticky top-0 z-50 shadow-xl shadow-black/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">
                
                <div class="flex items-center gap-3 md:gap-8">
                    <a href="{{ route('home') }}" class="group flex items-center gap-2 hover:scale-105 transition-transform shrink-0">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-red-600 to-red-700 rounded-lg md:rounded-xl flex items-center justify-center shadow-lg shadow-red-600/20 group-hover:shadow-red-600/40 transition-all">
                            <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        </div>
                        <span class="text-lg sm:text-2xl md:text-3xl font-black text-white tracking-tighter font-['Montserrat'] uppercase"><span class="text-red-600">nip</span>nime</span>
                    </a>
                    
                    <div class="hidden lg:flex items-center space-x-1 text-[11px] xl:text-xs font-bold uppercase tracking-widest">
                        <a href="{{ route('home') }}" class="px-3 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('home') ? 'text-red-500 bg-white/10' : '' }}">Home</a>
                        <a href="{{ route('search') }}" class="px-3 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('search') ? 'text-red-500 bg-white/10' : '' }}">Anime</a>
                        <a href="{{ route('schedule') }}" class="px-3 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('schedule') ? 'text-red-500 bg-white/10' : '' }}">Jadwal</a>
                        <a href="{{ route('search', ['type' => 'Movie']) }}" class="px-3 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition">Movie</a>
                    </div>
                </div>

                <div class="flex items-center gap-1 sm:gap-3 md:gap-6">
                    
                    <form action="{{ route('search') }}" method="GET" class="hidden md:block relative group">
                        <input type="text" name="search" placeholder="Cari anime..." 
                               class="w-40 lg:w-64 xl:w-80 bg-[#1a1d24] border border-white/10 text-white rounded-full px-4 py-2 text-xs focus:border-red-600 focus:ring-4 focus:ring-red-600/10 transition-all placeholder-gray-600">
                        <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </form>

                    <button id="mobileSearchBtn" class="md:hidden p-2 text-gray-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>

                    <div class="flex items-center gap-2">
                        @auth
                            <div class="relative" id="profileDropdown">
                                <button id="profileButton" class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-red-600 flex items-center justify-center text-white text-xs md:text-sm font-black border-2 border-red-600/20 overflow-hidden">
                                    @if(Auth::user()->avatar)
                                        <img src="{{ asset('storage/' . Auth::user()->avatar) }}" class="w-full h-full object-cover">
                                    @else
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    @endif
                                </button>
                                <div id="profileMenu" class="absolute right-0 mt-3 w-56 bg-[#1a1d24] rounded-xl shadow-2xl opacity-0 invisible transition-all border border-white/10 z-50">
                                    <div class="p-4 border-b border-white/5">
                                        <p class="text-xs font-bold text-gray-500 uppercase">Akun Saya</p>
                                        <p class="text-sm font-bold text-white truncate">{{ Auth::user()->name }}</p>
                                    </div>
                                    <a href="{{ route('profile.show') }}" class="block px-4 py-3 text-sm hover:bg-white/5 transition">👤 Profil</a>
                                    <form action="{{ route('auth.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-white/5 transition">🚪 Logout</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('auth.login') }}" class="text-[10px] md:text-xs font-bold uppercase p-2 hover:text-red-500 transition">Masuk</a>
                            <a href="{{ route('auth.register') }}" class="text-[10px] md:text-xs font-bold uppercase px-3 py-2 md:px-4 md:py-2.5 bg-red-600 text-white rounded-lg shadow-lg shadow-red-600/20 hover:bg-red-700 transition-all">Daftar</a>
                        @endauth
                    </div>

                    @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
                    <div class="hidden sm:flex items-center border-l border-white/10 pl-4 ml-1">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="holidayToggle" class="sr-only peer" onchange="toggleHolidayEffect()">
                            <div class="w-8 h-4 bg-gray-700 rounded-full peer peer-checked:bg-red-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-4"></div>
                        </label>
                    </div>
                    @endif

                    <button id="mobileMenuBtn" class="lg:hidden p-2 text-gray-400 hover:text-white transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobileSearchBar" class="hidden md:hidden px-4 pb-4 animate-fadeIn">
            <form action="{{ route('search') }}" method="GET">
                <input type="text" name="search" placeholder="Cari anime..." class="w-full bg-[#1a1d24] border border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-0">
            </form>
        </div>

        <div id="mobileMenu" class="hidden lg:hidden border-t border-white/5 bg-[#0f1115]/98 overflow-y-auto max-h-[calc(100vh-80px)]">
            <div class="p-4 space-y-1">
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold {{ request()->routeIs('home') ? 'bg-red-600 text-white' : 'text-gray-400 hover:bg-white/5' }}">🏠 HOME</a>
                <a href="{{ route('search') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold {{ request()->routeIs('search') ? 'bg-red-600 text-white' : 'text-gray-400 hover:bg-white/5' }}">📺 DAFTAR ANIME</a>
                <a href="{{ route('schedule') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold {{ request()->routeIs('schedule') ? 'bg-red-600 text-white' : 'text-gray-400 hover:bg-white/5' }}">📅 JADWAL TAYANG</a>
                <a href="{{ route('search', ['type' => 'Movie']) }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-gray-400 hover:bg-white/5">🎬 MOVIE</a>
                
                <div class="pt-4 border-t border-white/5 mt-4">
                    @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
                    <div class="flex items-center justify-between px-4 py-4 bg-white/5 rounded-xl">
                        <span class="text-xs font-bold uppercase tracking-widest text-gray-400">✨ Efek Visual</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="holidayToggleMobile" class="sr-only peer" onchange="toggleHolidayEffect()">
                            <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-red-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10 min-h-[60vh]">
        @yield('content')
    </main>

    <footer class="bg-gradient-to-t from-black via-[#0a0c10] to-[#0f1115] border-t border-white/5 py-12 md:py-20 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 md:gap-12 mb-12">
                
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        </div>
                        <span class="text-xl font-black text-white font-['Montserrat'] uppercase"><span class="text-red-600">nip</span>nime</span>
                    </div>
                    <p class="text-gray-500 text-sm leading-relaxed">Nikmati pengalaman menonton anime subtitle Indonesia terbaik dengan kualitas video HD dan server yang stabil.</p>
                </div>

                <div>
                    <h4 class="text-white font-bold uppercase tracking-wider mb-5 text-sm">Navigasi</h4>
                    <ul class="space-y-3 text-sm text-gray-500">
                        <li><a href="{{ route('home') }}" class="hover:text-red-500 transition">Beranda</a></li>
                        <li><a href="{{ route('search') }}" class="hover:text-red-500 transition">Daftar Anime</a></li>
                        <li><a href="{{ route('schedule') }}" class="hover:text-red-500 transition">Jadwal Rilis</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold uppercase tracking-wider mb-5 text-sm">Bantuan</h4>
                    <ul class="space-y-3 text-sm text-gray-500">
                        <li><a href="#" class="hover:text-red-500 transition">DMCA</a></li>
                        <li><a href="#" class="hover:text-red-500 transition">Kebijakan Privasi</a></li>
                        <li><a href="#" class="hover:text-red-500 transition">Hubungi Kami</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold uppercase tracking-wider mb-5 text-sm">Komunitas</h4>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-red-600 hover:text-white transition-all text-gray-400">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-red-600 hover:text-white transition-all text-gray-400">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-white/5 pt-8 text-center">
                <p class="text-gray-600 text-[10px] md:text-xs uppercase tracking-[0.2em]">© 2025 nipnime — Built for the community</p>
            </div>
        </div>
    </footer>

    @livewireScripts
    @livewire('notifications')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('profileButton');
            const profileMenu = document.getElementById('profileMenu');
            const profileDropdown = document.getElementById('profileDropdown');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileSearchBtn = document.getElementById('mobileSearchBtn');
            const mobileSearchBar = document.getElementById('mobileSearchBar');

            // Dropdown Profile
            if (profileBtn && profileMenu) {
                profileBtn.onclick = (e) => {
                    e.stopPropagation();
                    profileMenu.classList.toggle('opacity-0');
                    profileMenu.classList.toggle('invisible');
                    profileMenu.classList.toggle('translate-y-2');
                };
                window.onclick = () => {
                    profileMenu.classList.add('opacity-0', 'invisible', 'translate-y-2');
                };
            }

            // Mobile Menu
            if (mobileMenuBtn) {
                mobileMenuBtn.onclick = () => {
                    mobileMenu.classList.toggle('hidden');
                    document.body.classList.toggle('overflow-hidden');
                    if (mobileSearchBar) mobileSearchBar.classList.add('hidden');
                };
            }

            // Mobile Search
            if (mobileSearchBtn) {
                mobileSearchBtn.onclick = () => {
                    mobileSearchBar.classList.toggle('hidden');
                    if (!mobileSearchBar.classList.contains('hidden')) {
                        mobileSearchBar.querySelector('input').focus();
                    }
                };
            }
        });
    </script>

    @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
        <div id="holiday-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 99998;"></div>
        @if($holidaySettings['christmas']) <script src="https://unpkg.com/magic-snowflakes/dist/snowflakes.min.js"></script> @endif
        @if($holidaySettings['new_year']) <script src="https://unpkg.com/fireworks-js@2.x/dist/index.umd.js"></script> @endif
        <script>
            function startEffect() {
                const container = document.getElementById('holiday-container');
                const isDisabled = localStorage.getItem('nipnime_effects_disabled') === 'true';
                document.getElementById('holidayToggle').checked = !isDisabled;
                if(document.getElementById('holidayToggleMobile')) document.getElementById('holidayToggleMobile').checked = !isDisabled;
                if (isDisabled) return;
                @if($holidaySettings['christmas']) new Snowflakes({ color: '#ffffff', container: container });
                @elseif($holidaySettings['new_year']) new Fireworks.default(container, { intensity: 15 }).start(); @endif
            }
            function toggleHolidayEffect() {
                localStorage.setItem('nipnime_effects_disabled', localStorage.getItem('nipnime_effects_disabled') === 'true' ? 'false' : 'true');
                location.reload(); 
            }
            document.addEventListener('DOMContentLoaded', startEffect);
        </script>
    @endif

    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        main { animation: fadeInUp 0.4s ease-out forwards; }
        .animate-fadeIn { animation: fadeInUp 0.2s ease-out forwards; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f1115; }
        ::-webkit-scrollbar-thumb { background: #dc2626; border-radius: 10px; }
    </style>
</body>
</html>