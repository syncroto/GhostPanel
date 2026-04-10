@extends('layouts.app')

@section('title', 'Bancos de Dados')
@section('header', 'Bancos de Dados')

@section('content')

<div x-data="{ showForm: false, resetModal: false, resetDb: null, resetPass: '', resetLoading: false, msg: '', msgType: 'success' }">

@if(session('success'))
    <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-lg px-4 py-3 text-sm">
        {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg px-4 py-3 text-sm">
        {{ $errors->first() }}
    </div>
@endif

<!-- AJAX message -->
<div x-show="msg" x-cloak x-transition
     :class="msgType==='success'?'bg-green-50 border-green-200 text-green-700':'bg-red-50 border-red-200 text-red-700'"
     class="mb-4 border rounded-lg px-4 py-3 text-sm flex justify-between items-center">
    <span x-text="msg"></span>
    <button @click="msg=''" class="ml-4 opacity-60 hover:opacity-100">✕</button>
</div>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $databases->count() }} banco(s) cadastrado(s)</p>
    <div class="flex items-center gap-2">
        <a href="/phpmyadmin" target="_blank"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-brand-500 hover:text-brand-600 text-gray-600 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
            phpMyAdmin
        </a>
        <button @click="showForm = !showForm"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 shadow-sm shadow-brand-500/20 hover:bg-brand-600 hover:-translate-y-0.5 transition-all text-white text-sm font-semibold rounded-full transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Banco
        </button>
    </div>
</div>

<!-- Formulário -->
<div x-show="showForm" x-cloak x-transition
     class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-5">Criar banco de dados</h2>
    <form method="POST" action="{{ route('databases.store') }}">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tipo</label>
                <select name="driver" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="mysql">MySQL</option>
                    <option value="postgresql">PostgreSQL</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nome do banco</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="meu_banco"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Usuário</label>
                <input type="text" name="username" value="{{ old('username') }}" required placeholder="meu_usuario"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Senha</label>
                <input type="password" name="password" required placeholder="Mínimo 8 caracteres"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Site vinculado (opcional)</label>
                <select name="site_id" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">— Nenhum —</option>
                    @foreach(\App\Models\Site::orderBy('domain')->get() as $site)
                        <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>{{ $site->domain }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex gap-3 mt-5">
            <button type="button" @click="showForm = false"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                Cancelar
            </button>
            <button type="submit"
                    class="px-5 py-2 bg-brand-500 shadow-sm shadow-brand-500/20 hover:bg-brand-600 hover:-translate-y-0.5 transition-all text-white text-sm font-semibold rounded-full transition-colors">
                Criar banco
            </button>
        </div>
    </form>
</div>

<!-- Lista -->
@if($databases->isEmpty())
    <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-12 text-center">
        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
        </svg>
        <p class="text-gray-500 dark:text-gray-400">Nenhum banco criado ainda.</p>
    </div>
@else
    <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Banco</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Usuário</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tipo</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Site</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Criado em</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                @foreach($databases as $db)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="px-5 py-4 font-medium text-gray-900 dark:text-white">{{ $db->name }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $db->username }}</td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $db->driver === 'mysql' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                            {{ strtoupper($db->driver) }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ $db->site?->domain ?? '—' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $db->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-1">
                            @if($db->driver === 'mysql')
                            <button @click="resetModal=true; resetDb={{ json_encode(['id'=>$db->id,'name'=>$db->name,'username'=>$db->username]) }}; resetPass=''"
                                    class="p-1.5 text-gray-400 hover:text-brand-600 rounded" title="Redefinir Senha">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            </button>
                            @endif
                            <form method="POST" action="{{ route('databases.destroy', $db) }}"
                                  onsubmit="return confirm('Remover banco {{ $db->name }}? Esta ação é irreversível.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded" title="Remover">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Reset Password Modal --}}
<div x-show="resetModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.5)">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6">
        <p class="font-semibold text-gray-900 dark:text-white mb-1">Redefinir senha do banco</p>
        <p class="text-xs text-gray-400 mb-4" x-text="resetDb ? resetDb.username + ' @ ' + resetDb.name : ''"></p>
        <input x-model="resetPass" type="password" placeholder="Nova senha (mín. 8 caracteres)"
               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 mb-4">
        <div class="flex gap-3">
            <button @click="resetModal=false" class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
            <button @click="doResetPass()" :disabled="resetLoading || resetPass.length < 8"
                    class="flex-1 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
                <span x-text="resetLoading ? 'Aguarde...' : 'Redefinir'"></span>
            </button>
        </div>
    </div>
</div>

</div>
@push('scripts')
<script>
async function doResetPass() {
    const c = document.querySelector('[x-data]').__x.$data;
    c.resetLoading = true;
    try {
        const r = await fetch(`/databases/${c.resetDb.id}/reset-password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ password: c.resetPass })
        });
        const d = await r.json();
        c.msg = d.message || d.error;
        c.msgType = r.ok ? 'success' : 'error';
        if (r.ok) c.resetModal = false;
    } catch(e) {
        c.msg = 'Erro de conexão.'; c.msgType = 'error';
    }
    c.resetLoading = false;
}
</script>
@endpush
@endsection
