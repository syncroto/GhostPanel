@extends('layouts.app')

@section('title', 'Novo Site')
@section('header', 'Criar Novo Site')

@section('content')

<div class="max-w-2xl" x-data="{
    type: '{{ old('type', 'php') }}',
    domain: '{{ old('domain', '') }}',
    port: '{{ old('port', '') }}',
    portStatus: null,
    portChecking: false,
    customPath: false,
    get rootPath() {
        return this.domain ? '/var/www/sites/' + this.domain + '/public' : '';
    },
    get needsPort() {
        return this.type === 'nodejs' || this.type === 'python';
    },
    async checkPort() {
        if (!this.port || this.port < 1024) return;
        this.portChecking = true;
        this.portStatus = null;
        try {
            const r = await fetch('{{ route('sites.check-port') }}?port=' + this.port, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await r.json();
            this.portStatus = data;
        } catch(e) {}
        this.portChecking = false;
    }
}">

<div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-6">
    <form method="POST" action="{{ route('sites.store') }}" novalidate>
        @csrf

        @if($errors->any())
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        {{-- Domínio --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Domínio <span class="text-red-500">*</span>
            </label>
            <input type="text" name="domain" x-model="domain" placeholder="exemplo.com"
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 @error('domain') border-red-500 @enderror">
            <p class="mt-1 text-xs text-gray-500">Sem http:// — apenas o domínio (ex: meusite.com)</p>
        </div>

        {{-- Tipo de Stack --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Tipo de Aplicação <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach([
                    ['php',       'PHP',       'blue'],
                    ['nodejs',    'Node.js',   'green'],
                    ['python',    'Python',    'yellow'],
                    ['wordpress', 'WordPress', 'purple'],
                    ['html5',     'HTML5 / Static', 'orange'],
                ] as [$value, $label, $color])
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="{{ $value }}" x-model="type" class="sr-only">
                    <div class="border-2 rounded-xl p-3 text-center transition-all"
                         :class="type === '{{ $value }}'
                            ? 'border-{{ $color }}-500 bg-{{ $color }}-50 dark:bg-{{ $color }}-900/30'
                            : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'">
                        <p class="font-semibold text-sm"
                           :class="type === '{{ $value }}' ? 'text-{{ $color }}-600 dark:text-{{ $color }}-400' : 'text-gray-700 dark:text-gray-300'">
                            {{ $label }}
                        </p>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Versão PHP --}}
        <div class="mb-5" x-show="type === 'php' || type === 'wordpress'" x-cloak>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Versão do PHP</label>
            <select name="php_version" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="8.1">PHP 8.1</option>
                <option value="8.2" selected>PHP 8.2 (recomendado)</option>
                <option value="8.3">PHP 8.3</option>
            </select>
        </div>

        {{-- Versão Node.js --}}
        <div class="mb-5" x-show="type === 'nodejs'" x-cloak>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Versão do Node.js</label>
            <select name="node_version" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="18">Node.js 18 LTS</option>
                <option value="20" selected>Node.js 20 LTS (recomendado)</option>
                <option value="22">Node.js 22</option>
            </select>
        </div>

        {{-- Porta (Node.js / Python) --}}
        <div class="mb-5" x-show="needsPort" x-cloak>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Porta da Aplicação <span class="text-red-500">*</span>
            </label>
            <div class="flex gap-2">
                <input type="number" name="port" x-model="port" @blur="checkPort()" @input="portStatus=null"
                       placeholder="Ex: 3000" min="1024" max="65535"
                       class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 @error('port') border-red-500 @enderror">
                <button type="button" @click="checkPort()"
                        class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                    <span x-text="portChecking ? '...' : 'Verificar'"></span>
                </button>
            </div>
            <div x-show="portStatus !== null" class="mt-1.5 text-xs flex items-center gap-1">
                <template x-if="portStatus && portStatus.available">
                    <span class="text-green-600 dark:text-green-400">✓ Porta disponível</span>
                </template>
                <template x-if="portStatus && !portStatus.available">
                    <span class="text-red-500" x-text="'✗ ' + (portStatus.reason || 'Porta em uso')"></span>
                </template>
            </div>
            <p class="mt-1 text-xs text-gray-500">Porta onde sua aplicação irá ouvir. O Nginx fará o proxy reverso.</p>
        </div>

        {{-- Diretório root --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Diretório Root</label>
                <button type="button" @click="customPath = !customPath" class="text-xs text-brand-500 dark:text-indigo-400 hover:underline">
                    <span x-text="customPath ? 'Usar padrão' : 'Personalizar'"></span>
                </button>
            </div>
            <div x-show="!customPath" class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-500 dark:text-gray-400 font-mono">
                <span x-text="rootPath || '/var/www/sites/{domain}/public'"></span>
            </div>
            <input x-show="customPath" type="text" name="root_path" :placeholder="rootPath"
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <input x-show="!customPath" type="hidden" name="root_path" :value="rootPath">
        </div>

        {{-- Botões --}}
        <div class="flex gap-3">
            <a href="{{ route('sites.index') }}"
               class="flex-1 text-center py-2.5 px-4 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                Cancelar
            </a>
            <button type="submit"
                    class="flex-1 py-2.5 px-4 bg-brand-500 shadow-sm shadow-brand-500/20 hover:bg-brand-600 hover:-translate-y-0.5 transition-all text-white font-semibold rounded-full transition-colors">
                Criar Site
            </button>
        </div>
    </form>
</div>

<div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 text-sm text-blue-700 dark:text-blue-300">
    <strong>O que será criado automaticamente:</strong>
    <ul class="mt-2 space-y-1 list-disc list-inside text-blue-600 dark:text-blue-400">
        <li>Diretório do site com permissões corretas</li>
        <li x-show="type === 'php' || type === 'wordpress'">Vhost Nginx + pool PHP-FPM dedicado</li>
        <li x-show="type === 'nodejs' || type === 'python'">Serviço Supervisor + proxy reverso Nginx na porta configurada</li>
        <li x-show="type === 'html5'">Vhost Nginx para servir arquivos estáticos</li>
        <li x-show="type === 'wordpress'">Download automático do WordPress via WP-CLI</li>
    </ul>
</div>

</div>
@endsection
