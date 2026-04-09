<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — GPanel</title>
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
                        brand: { 500: '#056dff', 600: '#0052cc', 700: '#0041a8' }
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
<body class="bg-gray-50 text-gray-900 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">

    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-brand-500 rounded-xl mb-4 shadow-sm">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">GPanel</h1>
        <p class="text-gray-500 text-sm mt-1">Painel de gerenciamento de servidor</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 p-7 shadow-sm">

        @if(session('error'))
            <div class="mb-5 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-600">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" x-data="{ loading: false }" @submit="loading = true">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">E-mail</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Senha</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors">
            </div>

            <button type="submit" :disabled="loading"
                    class="w-full py-2.5 px-4 bg-brand-500 hover:bg-brand-600 disabled:opacity-50 text-white font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="loading ? 'Entrando...' : 'Entrar'"></span>
            </button>
        </form>

    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        Esqueceu a senha? <code class="bg-gray-100 px-1.5 py-0.5 rounded text-gray-500">gpanel admin:reset-password</code>
    </p>

</div>

</body>
</html>
