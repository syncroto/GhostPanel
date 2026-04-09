@extends('layouts.app')

@section('title', 'Vhost — ' . $site->domain)
@section('header', 'Editor Nginx — ' . $site->domain)

@section('content')

<div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
    <a href="{{ route('sites.index') }}" class="hover:text-indigo-500">Sites</a>
    <span>/</span>
    <a href="{{ route('sites.show', $site) }}" class="hover:text-indigo-500">{{ $site->domain }}</a>
    <span>/</span>
    <span class="text-gray-900 dark:text-white font-medium">Vhost Nginx</span>
</div>

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

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Configuração Nginx</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $site->nginx_config_path ?? 'Caminho não definido' }}</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-amber-500 dark:text-amber-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            Edição direta — cuidado com a sintaxe
        </div>
    </div>

    <form method="POST" action="{{ route('sites.vhost.save', $site) }}">
        @csrf
        <textarea name="content" rows="30" spellcheck="false"
                  class="w-full bg-gray-950 dark:bg-gray-900 text-green-400 font-mono text-sm px-5 py-4 focus:outline-none resize-none border-0"
                  style="tab-size: 4;">{{ $content }}</textarea>

        <div class="flex items-center justify-between px-5 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <a href="{{ route('sites.show', $site) }}"
               class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Salvar e Recarregar Nginx
            </button>
        </div>
    </form>
</div>

@endsection
