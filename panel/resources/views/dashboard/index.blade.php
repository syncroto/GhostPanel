@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('subheader', 'Good morning, ' . explode(' ', trim(auth()->user()->name ?? 'User'))[0] . '!')

@section('actions')
    <!-- Example actions area matching the Examples share/export buttons -->
    <div class="flex items-center gap-2">
        <button class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium rounded-full transition-colors shadow-sm shadow-brand-500/20">
            Export Data
        </button>
        <button class="px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-full hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
            <svg class="w-4 h-4 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Widget
        </button>
    </div>
@endsection

@section('content')

{{-- Stats Grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    {{-- Sites ativos --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 relative overflow-hidden group">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Sites Ativos</span>
            <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-brand-500 group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                </svg>
            </div>
        </div>
        <div class="flex items-baseline gap-3">
            <span class="text-4xl font-bold text-gray-900 dark:text-white tracking-tight">{{ $stats['sites_running'] ?? 0 }}</span>
        </div>
        <div class="mt-4 flex items-center gap-2 text-sm">
            <span class="inline-flex items-center gap-1 text-green-600 bg-green-50 dark:bg-green-900/30 dark:text-green-400 px-2.5 py-1 rounded-full font-medium text-xs">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                Total: {{ $stats['sites_total'] ?? 0 }}
            </span>
            <span class="text-gray-400 dark:text-gray-500 text-xs text-nowrap">sites no servidor</span>
        </div>
    </div>

    {{-- Uso de Disco --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 relative overflow-hidden group">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Disco Utilizado</span>
            <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-brand-500 group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582 4-8 4s8 1.79 8 4"/>
                </svg>
            </div>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-4xl font-bold text-gray-900 dark:text-white tracking-tight">{{ $stats['disk']['percent'] ?? 0 }}</span>
            <span class="text-xl font-semibold text-gray-400 dark:text-gray-500">%</span>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                @php $diskP = $stats['disk']['percent'] ?? 0; @endphp
                <div class="h-full rounded-full transition-all duration-1000 {{ $diskP > 80 ? 'bg-brand-500' : 'bg-gray-400 dark:bg-gray-500' }}" style="width: {{ $diskP }}%"></div>
            </div>
        </div>
    </div>

    {{-- MemÃ³ria RAM --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 relative overflow-hidden group">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">MemÃ³ria RAM</span>
            <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-brand-500 group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                </svg>
            </div>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-4xl font-bold text-gray-900 dark:text-white tracking-tight">{{ $stats['memory']['percent'] ?? 0 }}</span>
            <span class="text-xl font-semibold text-gray-400 dark:text-gray-500">%</span>
        </div>
        <div class="mt-4 flex items-center gap-2 text-sm">
            <span class="inline-flex items-center text-gray-600 dark:text-gray-300 font-medium text-xs bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-full">
                {{ $stats['memory']['used_mb'] ?? 0 }}M / {{ $stats['memory']['total_mb'] ?? 0 }}M
            </span>
        </div>
    </div>

    {{-- Uptime --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 relative overflow-hidden group">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Uptime do Servidor</span>
            <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-brand-500 group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="flex items-baseline gap-2">
            @php
                // Try to format uptime string slightly to fit the large number style if possible
                // e.g. "5 days" to "5d" - simplified for now
            @endphp
            <span class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white tracking-tight truncate">{{ $stats['uptime'] ?? 'N/A' }}</span>
        </div>
        <div class="mt-4 flex items-center gap-2 text-sm">
            <span class="inline-flex items-center gap-1 text-gray-500 bg-gray-50 dark:bg-gray-800 px-2.5 py-1 rounded-full font-medium text-xs">
                Load: {{ $stats['load'] ?? '0.00' }}
            </span>
        </div>
    </div>

</div>

{{-- Main Charts & Activity Area --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Main Activity Chart / Sites --}}
    <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 flex flex-col">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Projetos Recentes</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Todos os seus projetos de hospedagem em um só lugar.</p>
            </div>
            <a href="{{ route('sites.index') ?? '#' }}" class="p-2 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 hover:text-gray-900 hover:border-gray-300 dark:hover:text-white transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        @if(empty($recentSites) || (is_object($recentSites) && $recentSites->isEmpty()))
            <div class="flex-1 flex flex-col items-center justify-center py-12">
                <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4 text-gray-300 dark:text-gray-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-4">You don't have any sites yet.</p>
                <a href="{{ route('sites.create') ?? '#' }}" class="px-5 py-2.5 bg-brand-500 text-white rounded-full font-medium text-sm hover:bg-brand-600 transition-colors shadow-md shadow-brand-500/20">
                    Criar seu primeiro site
                </a>
            </div>
        @else
            <div class="flex w-full flex-col mt-2">
                <!-- Table Header Replica -->
                <div class="flex items-center text-xs font-semibold text-gray-400 dark:text-gray-500 pb-3 border-b border-gray-100 dark:border-gray-800 mb-4 px-2">
                    <div class="w-12">No</div>
                    <div class="flex-1">Nome do Domínio</div>
                    <div class="w-24 text-right">HTTPS</div>
                    <div class="w-32 text-right">Status</div>
                </div>

                <div class="space-y-4 px-2">
                    @foreach($recentSites as $idx => $site)
                    <div class="flex items-center group">
                        <div class="w-12 text-sm text-gray-400">#{{ $idx + 1 }}</div>
                        <div class="flex-1 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white text-sm group-hover:text-brand-500 transition-colors cursor-pointer">{{ $site->domain ?? 'domain.com' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ is_object($site) && method_exists($site, 'getTypeLabel') ? $site->getTypeLabel() : 'Standard' }}</p>
                            </div>
                        </div>
                        <div class="w-24 text-right">
                            @if(isset($site->ssl_enabled) && $site->ssl_enabled)
                                <div class="inline-flex w-7 h-7 rounded-full bg-green-50 dark:bg-green-900/30 items-center justify-center">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @else
                                <div class="inline-flex w-7 h-7 rounded-full bg-gray-50 dark:bg-gray-800 items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="w-32 text-right">
                            @php
                                $statusC = isset($site->status) ? $site->status : 'running';
                            @endphp
                            <span class="inline-flex items-center justify-center px-3 py-1 text-xs font-semibold rounded-full
                                {{ $statusC === 'running' ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' :
                                   ($statusC === 'creating' ? 'bg-brand-50 text-brand-500 dark:bg-brand-900/30 dark:text-brand-400' :
                                   'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400') }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $statusC === 'running' ? 'bg-green-500' : ($statusC === 'creating' ? 'bg-brand-500' : 'bg-red-500') }}"></span>
                                {{ ucfirst($statusC) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- System Services Area --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Status dos Serviços</h3>
        
        <div class="space-y-5">
            @if(isset($services) && is_iterable($services))
                @foreach($services as $name => $service)
                <div class="flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center border border-gray-100 dark:border-gray-700">
                            <!-- Try an icon depending on service name? Or just generic server -->
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                        </div>
                        <div>
                            <span class="text-sm font-bold text-gray-900 dark:text-white block">{{ $service['label'] ?? ucfirst($name) }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Daemon Central</span>
                        </div>
                    </div>
                    
                    @php
                        $srvStatus = $service['status'] ?? 'unknown';
                    @endphp
                    <div>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full
                            {{ $srvStatus === 'running' ? 'bg-green-50 dark:bg-green-900/20 text-green-500' :
                               ($srvStatus === 'stopped' ? 'bg-red-50 dark:bg-red-900/20 text-red-500' : 'bg-gray-50 dark:bg-gray-800 text-gray-500') }}"
                              title="{{ $srvStatus === 'running' ? 'Active' : 'Stopped' }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                @if($srvStatus === 'running')
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                @elseif($srvStatus === 'stopped')
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                @else
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                @endif
                            </svg>
                        </span>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>

</div>

@endsection

