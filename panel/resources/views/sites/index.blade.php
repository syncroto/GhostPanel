@extends('layouts.app')

@section('title', 'Sites')
@section('header', 'Sites')

@section('content')

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $sites->count() }} site(s) cadastrado(s)</p>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('sites.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Novo Site
    </a>
    @endif
</div>

@if($sites->isEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
        </svg>
        @if(auth()->user()->isAdmin())
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-1">Nenhum site ainda</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Crie seu primeiro site e o GPanel configurará tudo automaticamente.</p>
        <a href="{{ route('sites.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors">
            Criar primeiro site
        </a>
        @else
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-1">Nenhum site disponível</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum site foi atribuído à sua conta ainda.</p>
        @endif
    </div>
@else
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Domínio</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stack</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">SSL</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Criado em</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($sites as $site)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('sites.show', $site) }}"
                               class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                {{ $site->domain }}
                            </a>
                            <a href="http://{{ $site->domain }}" target="_blank"
                               class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $site->root_path }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $site->type === 'php' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' :
                               ($site->type === 'nodejs' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' :
                               ($site->type === 'python' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' :
                               'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300')) }}">
                            {{ $site->getTypeLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-1.5">
                            <div class="w-1.5 h-1.5 rounded-full
                                {{ $site->status === 'running' ? 'bg-green-500' :
                                   ($site->status === 'creating' ? 'bg-yellow-500 animate-pulse' :
                                   ($site->status === 'error' ? 'bg-red-500' : 'bg-gray-400')) }}"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ $site->status }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        @if($site->ssl_enabled)
                            <div class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-xs">Ativo</span>
                            </div>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $site->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-1" x-data>
                            <a href="{{ route('sites.show', $site) }}"
                               class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Gerenciar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </a>

                            <form method="POST" action="{{ route('sites.destroy', $site) }}"
                                  onsubmit="return confirm('Tem certeza que deseja remover {{ $site->domain }}? Esta ação é irreversível.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Remover">
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
