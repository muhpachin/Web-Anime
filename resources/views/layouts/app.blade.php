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
    
    <!-- Navbar -->
    <nav class="bg-[#0f1115]/90 backdrop-blur-xl border-b border-white/5 sticky top-0 z-50 shadow-xl shadow-black/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-10">
                    <a href="{{ route('home') }}" class="group flex items-center gap-3 hover:scale-105 transition-transform">
                        <div class="w-11 h-11 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center shadow-lg shadow-red-600/30 group-hover:shadow-red-600/50 transition-all">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        </div>
                        <span class="text-3xl font-black text-white tracking-tighter font-['Montserrat'] uppercase hidden sm:inline"><span class="text-red-600">nip</span>nime</span>
                    </a>
                    
                    <!-- Navigation Links -->
                    <div class="hidden lg:flex items-center space-x-1 text-sm font-bold uppercase tracking-widest">
                        <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('home') ? 'text-red-500 bg-white/10' : '' }}">
                            🏠 Home
                        </a>
                        <a href="{{ route('search') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('search') ? 'text-red-500 bg-white/10' : '' }}">
                            📺 Daftar Anime
                        </a>
                        <a href="{{ route('schedule') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('schedule') ? 'text-red-500 bg-white/10' : '' }}">
                            📅 Jadwal
                        </a>
                        <a href="{{ route('search', ['type' => 'Movie']) }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition">
                            🎬 Movie
                        </a>
                    </div>
                </div>

                <!-- Search & Auth -->
                <div class="flex items-center gap-6">
                    <!-- Search Bar -->
                    <form action="{{ route('search') }}" method="GET" class="hidden lg:block relative group">
                        <input type="text" name="search" placeholder="Cari anime..." 
                               class="w-72 bg-[#1a1d24] border-2 border-white/10 text-white rounded-full px-5 py-2.5 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600 focus:placeholder-gray-500">
                        <button type="submit" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    </form>

                    <!-- Auth Links -->
                    <div class="flex items-center gap-4">
                        @auth
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-gray-300 hidden sm:inline">{{ Auth::user()->name }}</span>
                                <div class="relative" id="profileDropdown">
                                    <button id="profileButton" class="w-11 h-11 rounded-full overflow-hidden bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center text-white font-black hover:shadow-lg hover:shadow-red-600/40 transition-all uppercase border-2 border-red-600/50">
                                        @if(Auth::user()->avatar)
                                            <img src="{{ asset('storage/' . Auth::user()->avatar) }}" 
                                                 alt="{{ Auth::user()->name }}"
                                                 class="w-full h-full object-cover">
                                        @else
                                            {{ substr(Auth::user()->name, 0, 1) }}
                                        @endif
                                    </button>
                                    <!-- Dropdown Menu -->
                                    <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-[#1a1d24] rounded-xl shadow-xl opacity-0 invisible transition-all duration-300 border border-white/10 z-50">
                                        <div class="p-3 border-b border-white/10 flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center text-white font-black flex-shrink-0">
                                                @if(Auth::user()->avatar)
                                                    <img src="{{ asset('storage/' . Auth::user()->avatar) }}" 
                                                         alt="{{ Auth::user()->name }}"
                                                         class="w-full h-full object-cover">
                                                @else
                                                    {{ substr(Auth::user()->name, 0, 1) }}
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-white">{{ Auth::user()->name }}</p>
                                                <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('profile.show') }}" class="block w-full text-left px-4 py-3 hover:bg-white/5 text-sm font-bold transition text-gray-300 hover:text-red-500">
                                            👤 PROFIL
                                        </a>
                                        <form action="{{ route('auth.logout') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-4 py-3 text-red-500 hover:bg-white/5 text-sm font-bold transition">
                                                🚪 LOGOUT
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('auth.login') }}" class="text-sm font-bold hover:text-red-500 transition px-4 py-2 rounded-lg hover:bg-white/10 uppercase tracking-wider">
                                🔐 Masuk
                            </a>
                            <a href="{{ route('auth.register') }}" class="text-sm font-bold px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-lg transition-all shadow-lg shadow-red-600/30 uppercase tracking-wider">
                                ✓ Daftar
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dropdown Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileButton = document.getElementById('profileButton');
            const profileMenu = document.getElementById('profileMenu');
            const profileDropdown = document.getElementById('profileDropdown');

            if (profileButton && profileMenu) {
                // Toggle on button click
                profileButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.classList.toggle('opacity-0');
                    profileMenu.classList.toggle('invisible');
                });

                // Close when clicking outside
                document.addEventListener('click', function(e) {
                    if (!profileDropdown.contains(e.target)) {
                        profileMenu.classList.add('opacity-0', 'invisible');
                    }
                });

                // Close when clicking a menu item (except forms)
                const profileLink = profileMenu.querySelector('a');
                if (profileLink) {
                    profileLink.addEventListener('click', function() {
                        profileMenu.classList.add('opacity-0', 'invisible');
                    });
                }
            }
        });
    </script>

    <!-- Main Content -->
    <main>@yield('content')</main>

    <!-- Footer -->
    <footer class="bg-gradient-to-t from-[#000000] via-[#0a0c10] to-[#1a1d24] border-t border-white/10 py-16 mt-20">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Footer Content -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
                <!-- Branding -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        </div>
                        <span class="text-2xl font-black text-white font-['Montserrat'] uppercase"><span class="text-red-600">nip</span>nime</span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">Platform streaming anime terlengkap dengan subtitle Indonesia berkualitas tinggi.</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-white font-black uppercase tracking-wider mb-4">Navigasi</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="{{ route('home') }}" class="hover:text-red-500 transition">Home</a></li>
                        <li><a href="{{ route('search') }}" class="hover:text-red-500 transition">Daftar Anime</a></li>
                        <li><a href="{{ route('schedule') }}" class="hover:text-red-500 transition">Jadwal Tayang</a></li>
                        <li><a href="{{ route('search', ['type' => 'Movie']) }}" class="hover:text-red-500 transition">Movie</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h4 class="text-white font-black uppercase tracking-wider mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#" class="hover:text-red-500 transition">DMCA</a></li>
                        <li><a href="#" class="hover:text-red-500 transition">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-red-500 transition">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-red-500 transition">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Social & Newsletter -->
                <div>
                    <h4 class="text-white font-black uppercase tracking-wider mb-4">Ikuti Kami</h4>
                    <div class="flex gap-3 mb-6">
                        <a href="#" class="w-10 h-10 bg-white/10 hover:bg-red-600 rounded-lg flex items-center justify-center text-white transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 hover:bg-red-600 rounded-lg flex items-center justify-center text-white transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.474-2.237-1.668-2.237-.91 0-1.451.613-1.688 1.208-.087.214-.11.512-.11.811v5.787h-3.553s.046-9.409 0-10.375h3.553v1.47c.458-.707 1.274-1.713 3.102-1.713 2.269 0 3.969 1.483 3.969 4.667v5.951z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 hover:bg-red-600 rounded-lg flex items-center justify-center text-white transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2s9 5 20 5a9.5 9.5 0 00-9-5.5c4.75 2.25 7-1 7-4 0-.5 0-1-1-1.25A4.5 4.5 0 0023 3z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-white/10 pt-8">
                <p class="text-gray-500 text-center text-sm">
                    © 2024 nipnime. All rights reserved. | Made with ❤️ for anime fans
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts
    @livewire('notifications')

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

        main {
            animation: fadeInUp 0.5s ease-out;
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0f1115;
        }

        ::-webkit-scrollbar-thumb {
            background: #dc2626;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #b91c1c;
        }
    </style>
</body>
</html>