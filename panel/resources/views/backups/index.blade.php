@extends('layouts.app')

@section('title', 'Backups')
@section('header', 'Backups')

@section('content')

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

<!-- Ações de backup -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    @foreach([
        ['full',      'Backup Completo',     'Sites + Bancos de Dados', '#6366f1'],
        ['sites',     'Backup de Sites',     'Apenas arquivos /var/www', '#0ea5e9'],
        ['databases', 'Backup de Bancos',    'MySQL e PostgreSQL', '#10b981'],
    ] as [$target, $label, $desc, $color])
    <form method="POST" action="{{ route('backups.store') }}">
        @csrf
        <input type="hidden" name="target" value="{{ $target }}">
        <button type="submit"
                class="w-full p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-indigo-500 transition-colors text-left group">
            <div class="flex items-center gap-3 mb-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                </svg>
                <span class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ $label }}</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $desc }}</p>
        </button>
    </form>
    @endforeach
</div>

<!-- Lista de backups -->
<div class="flex items-center justify-between mb-4">
    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Backups salvos</h2>
    <span class="text-xs text-gray-400">Armazenados em /gpanel/storage/backups/</span>
</div>

@if(empty($backups))
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
        </svg>
        <p class="text-gray-500 dark:text-gray-400">Nenhum backup encontrado.</p>
        <p class="text-xs text-gray-400 mt-1">Clique em um dos botões acima para criar o primeiro backup.</p>
    </div>
@else
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Arquivo</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tamanho</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Data</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($backups as $backup)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ $backup['name'] }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $backup['size'] }}</td>
                    <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $backup['date'] }}</td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-1 justify-end">
                            <a href="{{ route('backups.download', $backup['name']) }}"
                               class="p-1.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded" title="Download">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('backups.destroy', $backup['name']) }}"
                                  onsubmit="return confirm('Remover {{ $backup['name'] }}?')">
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

@endsection
