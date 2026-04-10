<!DOCTYPE html>
<html lang="pt-BR" x-data="{ darkMode: localStorage.getItem('theme') === 'dark', profileOpen: false }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ghost Panel') — {{ config('app.name', 'Ghost Panel') }}</title>

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        // Orange/Red Theme
                        brand: {
                            50:  '#fff0eb',
                            100: '#ffdbce',
                            200: '#ffbca5',
                            300: '#ff9472',
                            400: '#ff6239',
                            500: '#f95f36', // Primary brand color
                            600: '#e5451a',
                            700: '#be3210',
                            800: '#9b2a11',
                            900: '#7d2613',
                        },
                        indigo: {
                            50:  '#fff0eb',
                            100: '#ffdbce',
                            200: '#ffbca5',
                            300: '#ff9472',
                            400: '#ff6239',
                            500: '#f95f36',
                            600: '#e5451a',
                            700: '#be3210',
                            800: '#9b2a11',
                            900: '#7d2613',
                        },
                    },
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, sans-serif; }

        /* Scrollbar minimalista */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 6px; }
        .dark ::-webkit-scrollbar-thumb { background: #374151; }

        /* Efeito glass/blur opcional para elementos soltos, como dropdowns */
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass-panel {
            background: rgba(31, 41, 55, 0.7);
            border: 1px solid rgba(75, 85, 99, 0.3);
        }
    </style>
    @stack('head')
</head>
<body class="bg-[#f0f2f5] dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen antialiased flex flex-col">

<!-- Top Navigation Bar -->
<header class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md sticky top-0 z-40 border-b border-gray-200 dark:border-gray-800">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo Section -->
            <div class="flex items-center gap-3">
                <span class="font-bold text-gray-900 dark:text-white text-lg tracking-tight">Ghost Panel</span>
            </div>

            <!-- Center Navigation Pills -->
            <nav class="hidden md:flex flex-1 justify-center">
                <div class="flex items-center gap-1.5 p-1 bg-gray-100/50 dark:bg-gray-800/50 rounded-full border border-gray-200 dark:border-gray-700/50">
                    @php
                    $navItems = [
                        ['route' => 'dashboard',       'match' => 'dashboard',   'label' => 'Dashboard',  'admin' => false],
                        ['route' => 'sites.index',     'match' => 'sites.*',     'label' => 'Sites',      'admin' => false],
                        ['route' => 'databases.index', 'match' => 'databases.*', 'label' => 'Bancos',   'admin' => true],
                        ['route' => 'backups.index',   'match' => 'backups.*',   'label' => 'Backups',    'admin' => true],
                        ['route' => 'users.index',     'match' => 'users.*',     'label' => 'Usuários',   'admin' => true],
                    ];
                    @endphp

                    @foreach($navItems as $item)
                        @if(!$item['admin'] || auth()->user()->isAdmin())
                            @php $isActive = request()->routeIs($item['match']); @endphp
                            <a href="{{ route($item['route']) }}"
                               class="px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200
                                      {{ $isActive 
                                          ? 'bg-brand-500 text-white shadow-sm shadow-brand-500/30' 
                                          : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200/50 dark:hover:bg-gray-700/50' }}">
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </nav>

            <!-- Right Actions Area -->
            <div class="flex items-center gap-3">
                <!-- Search Icon (Placeholder) -->
                <button class="p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </button>

                <!-- Notifications Icon -->
                <button class="p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    <!-- Red Dot -->
                    <span class="absolute top-1.5 right-2 w-2 h-2 bg-red-500 border-2 border-white dark:border-gray-900 rounded-full"></span>
                </button>

                <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                <!-- Dark Mode Toggle -->
                <button @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')" 
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                    <svg x-show="darkMode" class="w-5 h-5" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </button>

                <!-- Profile Dropdown -->
                <div class="relative">
                    <button @click="profileOpen = !profileOpen" @click.away="profileOpen = false" class="flex items-center gap-2 pl-2 focus:outline-none">
                        <div class="w-8 h-8 rounded-full bg-brand-500 flex items-center justify-center text-white text-sm font-semibold shadow-md shadow-brand-500/20">
                            {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                        </div>
                    </button>
                    <!-- Dropdown Content -->
                    <div x-show="profileOpen" x-transition x-cloak
                         class="absolute right-0 mt-3 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden py-1">
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()->name ?? 'User' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email ?? 'user@example.com' }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Sair
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</header>

<!-- Main Container -->
<main class="flex-1 w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col">

    <!-- Header Section for The Page -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@yield('header', 'Dashboard')</h1>
            @hasSection('subheader')
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">@yield('subheader')</p>
            @endif
        </div>
        <div>
            @yield('actions')
        </div>
    </div>

    <!-- Flash Messages -->
    <div class="">
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition.duration.500ms x-init="setTimeout(() => show = false, 5000)"
                 class="mb-6 flex items-center gap-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-5 py-4 rounded-xl text-sm shadow-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div x-data="{ show: true }" x-show="show" x-transition.duration.500ms x-init="setTimeout(() => show = false, 5000)"
                 class="mb-6 flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 px-5 py-4 rounded-xl text-sm shadow-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                {{ session('info') }}
            </div>
        @endif
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition.duration.500ms x-init="setTimeout(() => show = false, 5000)"
                 class="mb-6 flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-5 py-4 rounded-xl text-sm shadow-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif
    </div>

    <!-- Page Content -->
    <div class="flex-1 w-full flex flex-col">
        @yield('content')
        
        <!-- Footer -->
        <footer class="mt-auto pt-8 pb-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <p>&copy; {{ date('Y') }} Todos os direitos reservados.</p>
            <div class="flex items-center gap-2">
                <a href="https://github.com/syncroto" target="_blank" rel="noopener noreferrer" class="hover:text-brand-500 transition-colors flex items-center gap-1.5 font-medium">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"></path>
                    </svg>
                    Ghost Panel - GitHub
                </a>
            </div>
        </footer>
    </div>
</main>

@stack('scripts')
</body>
</html>
