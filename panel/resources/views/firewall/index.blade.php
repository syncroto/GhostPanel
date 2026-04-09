@extends('layouts.app')

@section('title', 'Firewall')
@section('header', 'Firewall (UFW)')

@section('content')

<div x-data="{ showForm: false }">

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

<!-- Status -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-2">
        <div class="w-2.5 h-2.5 rounded-full {{ $status === 'active' ? 'bg-green-500' : 'bg-red-500' }}"></div>
        <span class="text-sm font-medium {{ $status === 'active' ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
            UFW {{ $status === 'active' ? 'Ativo' : 'Inativo' }}
        </span>
    </div>
    <button @click="showForm = !showForm"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nova Regra
    </button>
</div>

<!-- Formulário -->
<div x-show="showForm" x-cloak x-transition
     class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-5">Adicionar regra</h2>
    <form method="POST" action="{{ route('firewall.store') }}">
        @csrf
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Porta</label>
                <input type="number" name="port" value="{{ old('port') }}" required min="1" max="65535" placeholder="80"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Protocolo</label>
                <select name="protocol" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="tcp">TCP</option>
                    <option value="udp">UDP</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Ação</label>
                <select name="action" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="allow">Allow</option>
                    <option value="deny">Deny</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">De IP (opcional)</label>
                <input type="text" name="from" value="{{ old('from') }}" placeholder="0.0.0.0/0"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
        <div class="flex gap-3 mt-5">
            <button type="button" @click="showForm = false"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                Cancelar
            </button>
            <button type="submit"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Adicionar regra
            </button>
        </div>
    </form>
</div>

<!-- Regras rápidas -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    @foreach([['80','HTTP'],['443','HTTPS'],['22','SSH'],['3306','MySQL']] as [$port, $label])
    <form method="POST" action="{{ route('firewall.store') }}">
        @csrf
        <input type="hidden" name="port" value="{{ $port }}">
        <input type="hidden" name="protocol" value="tcp">
        <input type="hidden" name="action" value="allow">
        <button type="submit" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
            Allow {{ $label }} ({{ $port }})
        </button>
    </form>
    @endforeach
</div>

<!-- Lista de regras -->
@if(empty($rules))
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-10 text-center">
        <p class="text-gray-500 dark:text-gray-400 text-sm">Nenhuma regra encontrada ou UFW inativo.</p>
    </div>
@else
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">#</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Destino</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Ação</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">De</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($rules as $rule)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $rule['num'] }}</td>
                    <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $rule['to'] }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $rule['action'] === 'allow' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                            {{ strtoupper($rule['action']) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $rule['from'] }}</td>
                    <td class="px-5 py-3">
                        <form method="POST" action="{{ route('firewall.destroy') }}"
                              onsubmit="return confirm('Remover regra #{{ $rule['num'] }}?')">
                            @csrf
                            @method('DELETE')
                            {{-- passa a regra para deletar por número --}}
                            <input type="hidden" name="port"     value="{{ preg_replace('/[^0-9]/', '', $rule['to']) ?: '0' }}">
                            <input type="hidden" name="protocol" value="tcp">
                            <input type="hidden" name="action"   value="{{ $rule['action'] }}">
                            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

</div>
@endsection
