@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')

{{-- Stats Grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Sites ativos --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Sites Ativos</span>
            <div class="w-9 h-9 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['sites_running'] }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">de {{ $stats['sites_total'] }} total</p>
    </div>

    {{-- Uso de Disco --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Disco</span>
            <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582 4-8 4s8 1.79 8 4"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['disk']['percent'] }}<span class="text-lg font-normal text-gray-500">%</span></p>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-2">
            <div class="h-1.5 rounded-full {{ $stats['disk']['percent'] > 80 ? 'bg-red-500' : 'bg-blue-500' }}"
                 style="width: {{ $stats['disk']['percent'] }}%"></div>
        </div>
    </div>

    {{-- Memória --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Memória RAM</span>
            <div class="w-9 h-9 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['memory']['percent'] }}<span class="text-lg font-normal text-gray-500">%</span></p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {{ $stats['memory']['used_mb'] }}MB / {{ $stats['memory']['total_mb'] }}MB
        </p>
    </div>

    {{-- Uptime / Load --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Uptime</span>
            <div class="w-9 h-9 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ $stats['uptime'] }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Load: {{ $stats['load'] }}</p>
    </div>

</div>

{{-- Serviços + Sites recentes --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Serviços --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Serviços</h3>
        <div class="space-y-3">
            @foreach($services as $name => $service)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full {{ $service['status'] === 'running' ? 'bg-green-500' : ($service['status'] === 'stopped' ? 'bg-red-500' : 'bg-gray-400') }}"></div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $service['label'] }}</span>
                </div>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full
                    {{ $service['status'] === 'running' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' :
                       ($service['status'] === 'stopped' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400') }}">
                    {{ $service['status'] === 'running' ? 'Ativo' : ($service['status'] === 'stopped' ? 'Parado' : 'N/A') }}
                </span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Sites recentes --}}
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-white">Sites Recentes</h3>
            <a href="{{ route('sites.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Ver todos</a>
        </div>

        @if($recentSites->isEmpty())
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum site criado ainda.</p>
                <a href="{{ route('sites.create') }}"
                   class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    Criar primeiro site
                </a>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($recentSites as $site)
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $site->domain }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $site->getTypeLabel() }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($site->ssl_enabled)
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full
                            {{ $site->status === 'running' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' :
                               ($site->status === 'creating' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400' :
                               'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400') }}">
                            {{ ucfirst($site->status) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@endsection
