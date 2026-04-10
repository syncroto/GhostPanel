<!DOCTYPE html>
<html lang="pt-BR" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração Inicial — Ghost Panel</title>
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { 
            darkMode: 'class',
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
                            500: '#f95f36',
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

<!-- Decorative background blobs -->
<div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
<div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>

<div class="w-full max-w-[480px] relative z-10" x-data="{
    step: 1,
    loading: false,
    stepError: '',
    goNext() {
        this.stepError = '';
        const name = document.querySelector('[name=name]').value.trim();
        const email = document.querySelector('[name=email]').value.trim();
        const pw = document.querySelector('[name=password]').value;
        const pwc = document.querySelector('[name=password_confirmation]').value;
        if (!name) { this.stepError = 'Preencha o nome.'; return; }
        if (!email) { this.stepError = 'Preencha o e-mail.'; return; }
        if (pw.length < 12) { this.stepError = 'A senha precisa ter pelo menos 12 caracteres.'; return; }
        if (pw !== pwc) { this.stepError = 'As senhas não coincidem.'; return; }
        this.step = 2;
    }
}">

    <!-- Header -->
    <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-brand-500/10 rounded-full mb-4">
            <span class="text-brand-500 font-bold text-2xl">G</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">GPanel Setup</h1>
        <p class="text-gray-500 text-sm mt-2">Configuração inicial do painel</p>
    </div>

    <!-- Progress steps -->
    <div class="flex items-center justify-center gap-2 mb-8">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all shadow-sm"
                 :class="step >= 1 ? 'bg-brand-500 shadow-brand-500/30 text-white' : 'bg-gray-200 text-gray-500'">1</div>
            <span class="text-sm font-medium" :class="step >= 1 ? 'text-gray-900' : 'text-gray-400'">Admin</span>
        </div>
        <div class="w-10 h-0.5" :class="step >= 2 ? 'bg-brand-500' : 'bg-gray-200'"></div>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all shadow-sm"
                 :class="step >= 2 ? 'bg-brand-500 shadow-brand-500/30 text-white' : 'bg-gray-200 text-gray-500'">2</div>
            <span class="text-sm font-medium" :class="step >= 2 ? 'text-gray-900' : 'text-gray-400'">Servidor</span>
        </div>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-3xl shadow-[0px_4px_24px_rgba(0,0,0,0.04)] border border-gray-100 p-8">

        <form method="POST" action="{{ route('setup.store') }}" novalidate @submit="loading = true">
            @csrf

            <!-- Erros globais -->
            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
                    <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Step 1: Dados do Admin -->
            <div x-show="step === 1" x-cloak>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Criar conta de administrador</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nome completo</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="Ex: João Silva"
                               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">E-mail</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="admin@exemplo.com"
                               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Senha</label>
                        <input type="password" name="password" required minlength="12"
                               placeholder="Mínimo 12 caracteres"
                               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        <p class="mt-1.5 text-xs text-gray-400">Use uma senha forte com letras, números e símbolos.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirmar senha</label>
                        <input type="password" name="password_confirmation" required minlength="12"
                               placeholder="Repita a senha"
                               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>
                </div>

                <p x-show="stepError" x-text="stepError" class="mt-4 text-sm font-medium text-red-500 flex items-center gap-1"></p>

                <button type="button" @click="goNext()"
                        class="mt-6 w-full py-3 px-4 bg-gray-900 shadow-sm hover:bg-black hover:-translate-y-0.5 transition-all text-white font-semibold rounded-xl">
                    Próximo Passo
                </button>
            </div>

            <!-- Step 2: Configurações do servidor -->
            <div x-show="step === 2" x-cloak>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Configurações do servidor</h2>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nome do servidor</label>
                        <input type="text" name="server_name" value="{{ old('server_name', 'Meu Servidor') }}" required
                               placeholder="Ex: VPS Produção"
                               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                    </div>

                    <!-- Resumo das configurações detectadas -->
                    <div class="mt-4 bg-gray-50 border border-gray-100 rounded-2xl p-5 space-y-3 text-sm">
                        <p class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 9V5a1 1 0 112 0v4a1 1 0 11-2 0zm1 4a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>
                            Informações detectadas
                        </p>
                        <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                            <span class="text-gray-500 font-medium">Hostname</span>
                            <span class="text-gray-900 font-bold bg-white px-2 py-1 rounded shadow-sm">{{ gethostname() }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                            <span class="text-gray-500 font-medium">Sistema</span>
                            <span class="text-gray-900 font-bold bg-white px-2 py-1 rounded shadow-sm">{{ php_uname('s') . ' ' . php_uname('r') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-medium">PHP</span>
                            <span class="text-gray-900 font-bold bg-white px-2 py-1 rounded shadow-sm">{{ PHP_VERSION }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" @click="step = 1"
                            class="px-5 py-3 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-semibold rounded-xl transition-all">
                        Voltar
                    </button>
                    <button type="submit" :disabled="loading"
                            class="flex-1 py-3 px-4 bg-brand-500 shadow-md shadow-brand-500/20 hover:bg-brand-600 hover:-translate-y-0.5 transition-all disabled:opacity-50 text-white font-semibold rounded-xl flex items-center justify-center gap-2">
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Configurando...' : 'Concluir Instalação'"></span>
                    </button>
                </div>
            </div>

        </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-8 font-medium">
        GPanel Workspace — Configuração única inicial
    </p>
</div>

</body>
</html>
