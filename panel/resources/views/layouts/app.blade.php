<!DOCTYPE html>
<html lang="pt-BR" x-data="{ darkMode: localStorage.getItem('theme') === 'dark', sidebarOpen: true }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GPanel') — {{ config('app.name', 'GPanel') }}</title>

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
                        // Cloudflare-inspired blue (#056dff) remapped to both 'brand' and 'indigo'
                        // so all existing views using indigo-* automatically get the new color
                        brand: {
                            50:  '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#056dff',
                            600: '#0052cc',
                            700: '#0041a8',
                            800: '#003185',
                            900: '#002163',
                        },
                        indigo: {
                            50:  '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#056dff',
                            600: '#0052cc',
                            700: '#0041a8',
                            800: '#003185',
                            900: '#002163',
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
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #374151; }

        /* Nav item ativo */
        .nav-active {
            background-color: rgba(5, 109, 255, 0.08);
            color: #056dff;
            font-weight: 500;
        }
        .dark .nav-active {
            background-color: rgba(5, 109, 255, 0.15);
            color: #60a5fa;
        }
    </style>
    @stack('head')
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- ── Sidebar ──────────────────────────────────────────────────────── -->
    <aside class="w-56 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col flex-shrink-0">

        <!-- Logo -->
        <div class="flex items-center gap-2.5 px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="w-7 h-7 bg-brand-500 rounded-md flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                          d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </div>
            <span class="font-semibold text-gray-900 dark:text-white text-sm tracking-tight">GPanel</span>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5">

            @php
            $navItems = [
                ['route' => 'dashboard',       'match' => 'dashboard',   'label' => 'Dashboard',   'admin' => false,
                 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['route' => 'sites.index',     'match' => 'sites.*',     'label' => 'Sites',       'admin' => false,
                 'icon' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9'],
                ['route' => 'databases.index', 'match' => 'databases.*', 'label' => 'Bancos de Dados', 'admin' => true,
                 'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4'],
                ['route' => 'firewall.index',  'match' => 'firewall.*',  'label' => 'Firewall',    'admin' => true,
                 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                ['route' => 'backups.index',   'match' => 'backups.*',   'label' => 'Backups',     'admin' => true,
                 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                ['route' => 'users.index',     'match' => 'users.*',     'label' => 'Usuários',    'admin' => true,
                 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ];
            @endphp

            @foreach($navItems as $item)
            @if(!$item['admin'] || auth()->user()->isAdmin())
            @php $isActive = request()->routeIs($item['match']); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors
                      {{ $isActive
                           ? 'nav-active'
                           : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white' }}">
                <svg class="w-4 h-4 flex-shrink-0 {{ $isActive ? 'text-brand-500' : 'text-gray-400 dark:text-gray-500' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                </svg>
                {{ $item['label'] }}
            </a>
            @endif
            @endforeach

        </nav>

        <!-- User info -->
        <div class="border-t border-gray-200 dark:border-gray-800 px-4 py-3">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-full bg-brand-500 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sair"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- ── Main Content ──────────────────────────────────────────────────── -->
    <main class="flex-1 overflow-y-auto flex flex-col">

        <!-- Top bar -->
        <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-6 py-3.5 flex items-center justify-between flex-shrink-0">
            <h1 class="text-base font-semibold text-gray-900 dark:text-white">@yield('header', 'Dashboard')</h1>
            <div class="flex items-center gap-2">
                <!-- Dark mode toggle -->
                <button @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')"
                        class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400 transition-colors">
                    <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-6 pt-4">
            @if(session('success'))
                <div class="mb-4 flex items-center gap-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg text-sm"
                     x-data x-init="setTimeout(() => $el.remove(), 5000)">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('info'))
                <div class="mb-4 flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 px-4 py-3 rounded-lg text-sm"
                     x-data x-init="setTimeout(() => $el.remove(), 5000)">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('info') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif
        </div>

        <!-- Page Content -->
        <div class="flex-1 px-6 pb-8 pt-4">
            @yield('content')
        </div>
    </main>
</div>

@stack('scripts')
</body>
</html>
