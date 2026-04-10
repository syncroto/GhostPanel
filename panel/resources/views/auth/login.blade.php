<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ghost Panel</title>
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
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
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-[#f0f2f5] text-gray-900 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

<!-- Decorative background blobs (aesthetic) -->
<div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
<div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>

<div class="w-full max-w-[400px] relative z-10">

    <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-brand-500/10 rounded-full mb-4">
            <svg class="w-7 h-7 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Bem-vindo de volta</h1>
        <p class="text-gray-500 text-sm mt-2">Faça login no seu painel Ghost Panel</p>
    </div>

    <div class="bg-white rounded-3xl p-8 shadow-[0px_4px_24px_rgba(0,0,0,0.04)] border border-gray-100">

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-100 rounded-xl px-4 py-3 text-sm text-red-600 flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
                <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" x-data="{ loading: false }" @submit="loading = true">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Endereço de e-mail</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
            </div>

            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-semibold text-gray-700">Senha</label>
                </div>
                <input type="password" name="password" required
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
            </div>

            <button type="submit" :disabled="loading"
                    class="w-full py-3 px-4 bg-brand-500 hover:bg-brand-600 shadow-md shadow-brand-500/20 disabled:opacity-50 text-white font-semibold rounded-xl transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                <svg x-show="loading" class="animate-spin w-4 h-4" x-cloak fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="loading ? 'Entrando...' : 'Entrar na Plataforma'"></span>
            </button>
        </form>

    </div>

    <p class="text-center text-xs text-gray-400 mt-8">
        Recupere via terminal: <code class="bg-gray-200 px-2 py-1 rounded-md text-gray-600 font-medium tracking-tight">gpanel admin:reset-password</code>
    </p>

</div>

</body>
</html>

