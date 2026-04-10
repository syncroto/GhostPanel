<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração Inicial — GPanel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md" x-data="{
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
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-brand-500 shadow-sm shadow-brand-500/20 rounded-2xl mb-4">
            <span class="text-white font-bold text-3xl">G</span>
        </div>
        <h1 class="text-3xl font-bold text-white">GPanel</h1>
        <p class="text-gray-400 mt-1">Configuração inicial do painel</p>
    </div>

    <!-- Progress steps -->
    <div class="flex items-center justify-center gap-2 mb-8">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                 :class="step >= 1 ? 'bg-brand-500 shadow-sm shadow-brand-500/20 text-white' : 'bg-gray-700 text-gray-400'">1</div>
            <span class="text-sm" :class="step >= 1 ? 'text-white' : 'text-gray-500'">Admin</span>
        </div>
        <div class="w-8 h-px bg-gray-700"></div>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                 :class="step >= 2 ? 'bg-brand-500 shadow-sm shadow-brand-500/20 text-white' : 'bg-gray-700 text-gray-400'">2</div>
            <span class="text-sm" :class="step >= 2 ? 'text-white' : 'text-gray-500'">Servidor</span>
        </div>
    </div>

    <!-- Card -->
    <div class="bg-gray-900 rounded-2xl border border-gray-800 p-8">

        <form method="POST" action="{{ route('setup.store') }}" novalidate @submit="loading = true">
            @csrf

            <!-- Erros globais -->
            @if($errors->any())
                <div class="mb-6 bg-red-900/30 border border-red-800 rounded-lg p-4">
                    <ul class="list-disc list-inside text-sm text-red-300 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Step 1: Dados do Admin -->
            <div x-show="step === 1" x-cloak>
                <h2 class="text-xl font-semibold mb-6">Criar conta de administrador</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Nome completo</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="Ex: João Silva"
                               class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">E-mail</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="admin@exemplo.com"
                               class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Senha</label>
                        <input type="password" name="password" required minlength="12"
                               placeholder="Mínimo 12 caracteres"
                               class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        <p class="mt-1.5 text-xs text-gray-500">Use uma senha forte com letras, números e símbolos.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Confirmar senha</label>
                        <input type="password" name="password_confirmation" required minlength="12"
                               placeholder="Repita a senha"
                               class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>
                </div>

                <p x-show="stepError" x-text="stepError" class="mt-3 text-sm text-red-400"></p>

                <button type="button" @click="goNext()"
                        class="mt-4 w-full py-3 px-4 bg-brand-500 shadow-sm shadow-brand-500/20 hover:bg-brand-600 hover:-translate-y-0.5 transition-all text-white font-semibold rounded-full transition-colors">
                    Próximo
                </button>
            </div>

            <!-- Step 2: Configurações do servidor -->
            <div x-show="step === 2" x-cloak>
                <h2 class="text-xl font-semibold mb-6">Configurações do servidor</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Nome do servidor</label>
                        <input type="text" name="server_name" value="{{ old('server_name', 'Meu Servidor') }}" required
                               placeholder="Ex: VPS Produção"
                               class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <!-- Resumo das configurações detectadas -->
                    <div class="mt-4 bg-gray-800 rounded-lg p-4 space-y-2 text-sm">
                        <p class="font-medium text-gray-300 mb-3">Informações do servidor detectadas:</p>
                        <div class="flex justify-between text-gray-400">
                            <span>Hostname</span>
                            <span class="text-gray-200">{{ gethostname() }}</span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>Sistema</span>
                            <span class="text-gray-200">{{ php_uname('s') . ' ' . php_uname('r') }}</span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>PHP</span>
                            <span class="text-gray-200">{{ PHP_VERSION }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="button" @click="step = 1"
                            class="flex-1 py-3 px-4 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition-colors">
                        Voltar
                    </button>
                    <button type="submit" :disabled="loading"
                            class="flex-1 py-3 px-4 bg-brand-500 shadow-sm shadow-brand-500/20 hover:bg-brand-600 hover:-translate-y-0.5 transition-all disabled:opacity-50 text-white font-semibold rounded-full transition-colors flex items-center justify-center gap-2">
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Configurando...' : 'Concluir Setup'"></span>
                    </button>
                </div>
            </div>

        </form>
    </div>

    <p class="text-center text-xs text-gray-600 mt-6">
        GPanel v1.0 — Esta página só aparece uma vez.
    </p>
</div>

</body>
</html>
