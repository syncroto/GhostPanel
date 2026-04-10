@extends('layouts.app')

@section('title', $site->domain)
@section('header', $site->domain)

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css"/>
<script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.js"></script>
<style>
/* File Manager */
.fm-row:hover     { background: #f8fafc; }
.fm-row.selected  { background: #eff6ff; }
.dark .fm-row:hover    { background: #1f2937; }
.dark .fm-row.selected { background: #1e3a5f; }
.ctx-menu { box-shadow: 0 4px 20px rgba(0,0,0,.15); }
</style>
@endpush

@section('content')
<div x-data="siteManager()" x-init="init()" @click="fmCtxClose()">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
        <a href="{{ route('sites.index') }}" class="hover:text-brand-600">Sites</a>
        <span>/</span>
        <span class="text-gray-900 dark:text-white font-medium">{{ $site->domain }}</span>
    </div>

    {{-- AJAX message --}}
    <div x-show="msg" x-cloak x-transition
         :class="msgType==='success'?'bg-green-50 border-green-200 text-green-700':'bg-red-50 border-red-200 text-red-700'"
         class="mb-4 border rounded-lg px-4 py-3 text-sm flex justify-between items-center">
        <span x-text="msg"></span>
        <button @click="msg=''" class="ml-4 opacity-60 hover:opacity-100">✕</button>
    </div>

    @foreach(['info'=>'blue','success'=>'green','error'=>'red'] as $type => $color)
    @if(session($type))
    <div class="mb-4 bg-{{ $color }}-50 dark:bg-{{ $color }}-900/20 border border-{{ $color }}-200 dark:border-{{ $color }}-800 text-{{ $color }}-700 dark:text-{{ $color }}-300 rounded-lg px-4 py-3 text-sm">
        {{ session($type) }}
    </div>
    @endif
    @endforeach
    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
        {{ $errors->first() }}
    </div>
    @endif

    {{-- Site header card --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-5 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $site->domain }}</h1>
                    <a href="http{{ $site->ssl_enabled?'s':'' }}://{{ $site->domain }}" target="_blank" class="text-gray-400 hover:text-brand-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
                <p class="text-xs text-gray-400 font-mono">{{ $site->root_path }}</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full {{ $site->status==='running'?'bg-green-500':($site->status==='creating'?'bg-yellow-500 animate-pulse':($site->status==='error'?'bg-red-500':'bg-gray-400')) }}"></div>
                <span class="text-sm font-medium capitalize {{ $site->status==='running'?'text-green-600':($site->status==='error'?'text-red-500':'text-gray-500') }}">{{ $site->status }}</span>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Stack</p>
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ ['php'=>'bg-blue-100 text-blue-700','nodejs'=>'bg-green-100 text-green-700','python'=>'bg-yellow-100 text-yellow-700','wordpress'=>'bg-purple-100 text-purple-700','html5'=>'bg-orange-100 text-orange-700'][$site->type]??'bg-gray-100 text-gray-700' }}">{{ $site->getTypeLabel() }}</span>
            </div>
            <div><p class="text-xs text-gray-400 mb-0.5">SSL</p><span class="text-sm {{ $site->ssl_enabled?'text-green-600':'text-gray-400' }}">{{ $site->ssl_enabled?'Ativo':'Inativo' }}</span></div>
            @if($site->port)<div><p class="text-xs text-gray-400 mb-0.5">Porta</p><span class="text-sm font-mono text-gray-700 dark:text-gray-300">:{{ $site->port }}</span></div>@endif
            <div><p class="text-xs text-gray-400 mb-0.5">Criado em</p><span class="text-sm text-gray-600 dark:text-gray-300">{{ $site->created_at->format('d/m/Y H:i') }}</span></div>
            <div><p class="text-xs text-gray-400 mb-0.5">ID</p><span class="text-sm text-gray-600 dark:text-gray-300">#{{ $site->id }}</span></div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="grid grid-cols-3 gap-3 mb-4">
        <!-- <button @click="doAction('{{ route('sites.toggle-ssl',$site->domain) }}')" :disabled="actionLoading"
                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-brand-500 hover:text-brand-600 text-sm font-medium disabled:opacity-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            {{ $site->ssl_enabled?'Remover SSL':'Ativar SSL' }}
        </button> -->
        <!-- <form method="POST" action="{{ route('sites.destroy',$site->domain) }}" onsubmit="return confirm('Remover {{ $site->domain }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-red-400 hover:text-red-600 text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Remover
            </button>
        </form> -->
    </div>

    {{-- Tabs --}}
    <div class="flex flex-wrap gap-1 mb-4 bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-1.5">
        @foreach([
            ['info','Info','M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['terminal','Terminal','M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['logs','Logs','M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['files','Arquivos','M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
            ['databases','Databases','M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4'],
            ['cron','Cron','M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['vhost','Nginx','M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
            ['security','Segurança','M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ['backups','Backups','M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
        ] as [$t,$l,$i])
        <button @click="setTab('{{ $t }}')"
                :class="tab==='{{ $t }}' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $i }}"/></svg>
            {{ $l }}
        </button>
        @endforeach
    </div>

    {{-- ── TAB: Info ── --}}
    <div x-show="tab==='info'" class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-5">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Detalhes técnicos</h2>
        <div class="space-y-0 text-sm divide-y divide-gray-50 dark:divide-gray-700">
            <!-- @if($site->nginx_config_path)
            <div class="flex justify-between items-center py-2.5"><span class="text-gray-500">Config Nginx</span><code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $site->nginx_config_path }}</code></div>
            @endif -->
            @if($site->supervisor_program)
            <div class="flex justify-between items-center py-2.5"><span class="text-gray-500">Supervisor</span><code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $site->supervisor_program }}</code></div>
            @endif
            <!-- <div class="flex justify-between items-center py-2.5"><span class="text-gray-500">Root path</span><code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $site->root_path }}</code></div> -->
            @if($site->port)
            <div class="flex justify-between items-center py-2.5"><span class="text-gray-500">Porta app</span><code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">:{{ $site->port }}</code></div>
            @endif
        </div>
    </div>

    {{-- ── TAB: Terminal ── --}}
    <div x-show="tab==='terminal'" class="rounded-xl border border-gray-700 overflow-hidden bg-gray-950">
        <div class="flex items-center justify-between px-4 py-2 bg-gray-900 border-b border-gray-700">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-red-500"></div><div class="w-3 h-3 rounded-full bg-yellow-500"></div><div class="w-3 h-3 rounded-full bg-green-500"></div>
                <span class="ml-2 text-xs text-gray-400 font-mono">{{ $site->domain }} — bash</span>
            </div>
            <button @click="reconnectTerminal()" class="text-xs text-gray-500 hover:text-gray-300">Reconectar</button>
        </div>
        <div id="terminal" class="p-1" style="height: 420px;"></div>
    </div>

    {{-- ── TAB: Logs ── --}}
    <div x-show="tab==='logs'" class="rounded-xl border border-gray-700 overflow-hidden bg-gray-950">
        <div class="flex items-center gap-2 px-4 py-2 bg-gray-900 border-b border-gray-700">
            <span class="text-xs text-gray-400 font-medium">Streaming de Logs</span>
            <select x-model="logType" @change="startLogs()" class="ml-auto text-xs bg-gray-800 text-gray-300 border border-gray-600 rounded px-2 py-1">
                <optgroup label="Este Site">
                    <option value="var/www/sites/{{ $site->domain }}/logs/access.log">{{ $site->domain }} — Access</option>
                    <option value="var/www/sites/{{ $site->domain }}/logs/error.log">{{ $site->domain }} — Error</option>
                </optgroup>
                <optgroup label="Nginx Global">
                    <option value="nginx/access">Nginx — Access (global)</option>
                    <option value="nginx/error">Nginx — Error (global)</option>
                </optgroup>
                <optgroup label="GPanel">
                    <option value="gpanel/app">Laravel — App</option>
                </optgroup>
            </select>
            <button @click="clearLogs()" class="text-xs text-gray-500 hover:text-gray-300">Limpar</button>
        </div>
        <pre id="log-output" class="text-green-400 font-mono text-xs p-4 h-80 overflow-y-auto whitespace-pre-wrap"></pre>
    </div>

    {{-- ── TAB: Arquivos — File Manager ── --}}
    <div x-show="tab==='files'" class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 overflow-hidden" style="min-height: 520px;">

        {{-- Toolbar --}}
        <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 flex-wrap">

            {{-- Add New dropdown --}}
            <div class="relative" x-data="{ open: false }">
                <button @click.stop="open=!open"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Novo
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak @click.outside="open=false"
                     class="absolute left-0 top-8 z-30 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg w-44 py-1">
                    <button @click="fmNewModal('file'); open=false"
                            class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Novo arquivo
                    </button>
                    <button @click="fmNewModal('dir'); open=false"
                            class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-8-4h.01M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                        Nova pasta
                    </button>
                    <hr class="border-gray-100 dark:border-gray-700 my-1">
                    <button @click="fmUploadModal(); open=false"
                            class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Upload de arquivo
                    </button>
                </div>
            </div>

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-1 flex-1 min-w-0 text-xs font-mono">
                <button @click="fmLoad('{{ addslashes('/var/www/sites/'.$site->domain) }}/')"
                        class="text-brand-600 hover:underline font-medium">{{ $site->domain }}</button>
                <template x-for="(crumb, i) in fmBreadcrumbs()" :key="i">
                    <span class="flex items-center gap-1">
                        <span class="text-gray-400">/</span>
                        <button @click="fmLoad(crumb.path)" class="text-brand-600 hover:underline" x-text="crumb.name"></button>
                    </span>
                </template>
            </div>

            {{-- Refresh --}}
            <button @click="fmLoad(fmPath)" :class="fmLoading?'animate-spin':''" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
        </div>

        {{-- Two-column layout: tree + list --}}
        <div class="flex" style="height: 460px;">

            {{-- Left: Directory Tree --}}
            <div class="w-44 border-r border-gray-200 dark:border-gray-700 overflow-y-auto bg-gray-50 dark:bg-gray-900/40 flex-shrink-0">
                <div class="py-1">
                    <template x-if="fmTree">
                        <div>
                            <div x-data @click="fmLoad(fmTreeRootPath)"
                                 class="flex items-center gap-1.5 px-2 py-1.5 cursor-pointer hover:bg-white dark:hover:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-3.5 h-3.5 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                {{ $site->domain }}
                            </div>
                            <template x-for="node in fmTree" :key="node.path">
                                <div x-data="{ node: node }">
                                    <div class="flex items-center gap-1 px-2 py-1 cursor-pointer hover:bg-white dark:hover:bg-gray-700 text-xs text-gray-600 dark:text-gray-400"
                                         :class="fmPath.startsWith(node.path)?'bg-brand-50 text-brand-700 dark:bg-brand-900/20 dark:text-brand-300':''"
                                         @click="fmLoad(node.path); fmTreeExpand(node)">
                                        <svg class="w-3 h-3 flex-shrink-0" :class="node.expanded?'text-brand-400':'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                                            <path x-show="!node.expanded" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"/>
                                            <path x-show="node.expanded" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                                        </svg>
                                        <svg class="w-3.5 h-3.5 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                        <span class="truncate ml-0.5" x-text="node.name"></span>
                                    </div>
                                    <div x-show="node.expanded" class="pl-3">
                                        <template x-for="child in node.children" :key="child.path">
                                            <div class="flex items-center gap-1 px-2 py-1 cursor-pointer hover:bg-white dark:hover:bg-gray-700 text-xs text-gray-500 dark:text-gray-400"
                                                 :class="fmPath===child.path?'bg-brand-50 text-brand-700 dark:bg-brand-900/20':''"
                                                 @click="fmLoad(child.path)">
                                                <svg class="w-3.5 h-3.5 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                                <span class="truncate" x-text="child.name"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Right: File List --}}
            <div class="flex-1 overflow-y-auto" @contextmenu.prevent="">

                {{-- Loading --}}
                <div x-show="fmLoading" class="flex items-center justify-center h-32 text-sm text-gray-400">
                    <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Carregando...
                </div>

                {{-- File table --}}
                <table x-show="!fmLoading" class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700 sticky top-0">
                        <tr>
                            <th class="text-left px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Nome</th>
                            <th class="text-left px-3 py-2 text-xs font-semibold text-gray-500 uppercase w-20">Tamanho</th>
                            <th class="text-left px-3 py-2 text-xs font-semibold text-gray-500 uppercase w-32">Modificado</th>
                            <th class="text-left px-3 py-2 text-xs font-semibold text-gray-500 uppercase w-16">Perms</th>
                            <th class="w-10 px-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Go up row --}}
                        <tr x-show="fmPath !== fmRootPath" class="fm-row cursor-pointer border-b border-gray-100 dark:border-gray-700/50" @dblclick="fmGoUp()">
                            <td class="px-3 py-2" colspan="5">
                                <div class="flex items-center gap-2 text-gray-400 text-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                    .. (pasta acima)
                                </div>
                            </td>
                        </tr>
                        <template x-for="item in fmItems" :key="item.name">
                            <tr class="fm-row cursor-pointer border-b border-gray-100 dark:border-gray-700/50"
                                :class="fmSelected===item.name?'selected':''"
                                @click="fmSelected=item.name"
                                @dblclick="item.type==='dir' ? fmLoad(fmPath+item.name+'/') : fmEdit(item)"
                                @contextmenu.prevent.stop="fmCtxOpen($event, item)">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        {{-- Icon --}}
                                        <template x-if="item.type==='dir'">
                                            <svg class="w-5 h-5 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                        </template>
                                        <template x-if="item.type!=='dir'">
                                            <span class="inline-flex items-center justify-center w-8 h-5 rounded text-white text-[9px] font-bold flex-shrink-0"
                                                  :class="fmExtColor(item.ext)" x-text="(item.ext||'FILE').toUpperCase().substring(0,4)"></span>
                                        </template>
                                        <span class="text-gray-900 dark:text-white text-sm" x-text="item.name"></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-500" x-text="item.size || '—'"></td>
                                <td class="px-3 py-2 text-xs text-gray-400" x-text="item.modified"></td>
                                <td class="px-3 py-2">
                                    <code class="text-xs text-gray-400 font-mono" x-text="item.perms"></code>
                                </td>
                                <td class="px-2 py-2">
                                    <button @click.stop="fmCtxOpen($event, item)"
                                            class="p-1 text-gray-300 hover:text-gray-600 dark:hover:text-gray-300 rounded">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!fmLoading && fmItems.length===0">
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">Pasta vazia</td>
                        </tr>
                    </tbody>
                </table>

                {{-- Status bar --}}
                <div x-show="!fmLoading" class="sticky bottom-0 px-3 py-1.5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 text-xs text-gray-400">
                    <span x-text="fmItems.length + ' item(s)'"></span>
                    <span x-show="fmSelected" x-text="' — ' + fmSelected + ' selecionado'"></span>
                </div>
            </div>
        </div>

        {{-- Context Menu --}}
        <div x-show="fmCtx.open" x-cloak
             :style="`position:fixed;top:${fmCtx.y}px;left:${fmCtx.x}px;z-index:9999`"
             class="ctx-menu bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl w-44 py-1 text-sm"
             @click.stop="">
            <template x-if="fmCtx.item && fmCtx.item.type==='file'">
                <div>
                    <button @click="fmEdit(fmCtx.item); fmCtxClose()"
                            class="w-full flex items-center gap-2.5 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Editar
                    </button>
                    <button @click="fmDownload(fmCtx.item); fmCtxClose()"
                            class="w-full flex items-center gap-2.5 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download
                    </button>
                </div>
            </template>
            <button @click="fmRenameModal(fmCtx.item); fmCtxClose()"
                    class="w-full flex items-center gap-2.5 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                Renomear
            </button>
            <button @click="fmChmodModal(fmCtx.item); fmCtxClose()"
                    class="w-full flex items-center gap-2.5 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                Permissões
            </button>
            <hr class="border-gray-100 dark:border-gray-700 my-1">
            <button @click="fmDeleteModal(fmCtx.item); fmCtxClose()"
                    class="w-full flex items-center gap-2.5 px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Excluir
            </button>
        </div>
    </div>

    {{-- ── MODAL: File Editor ── --}}
    <div x-show="fmEditor.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,.5)">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-4xl flex flex-col" style="max-height:90vh">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="fmEditor.name"></p>
                    <p class="text-xs text-gray-400 font-mono" x-text="fmEditor.path"></p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="fmSaveFile()" :disabled="fmEditor.saving"
                            class="px-4 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg disabled:opacity-50">
                        <span x-text="fmEditor.saving?'Salvando...':'Salvar'"></span>
                    </button>
                    <button @click="fmEditor.open=false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <textarea x-model="fmEditor.content"
                      spellcheck="false"
                      class="flex-1 w-full font-mono text-sm px-5 py-4 bg-gray-950 text-green-400 focus:outline-none resize-none border-0 rounded-b-2xl"
                      style="tab-size:4; min-height:400px;"></textarea>
            <div class="px-5 py-2 border-t border-gray-200 dark:border-gray-700 flex items-center gap-4 text-xs text-gray-400">
                <span x-text="'Linhas: ' + (fmEditor.content.split('\n').length)"></span>
                <span x-text="'Bytes: ' + (new Blob([fmEditor.content]).size)"></span>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Delete confirm ── --}}
    <div x-show="fmDelModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,.5)">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">Excluir arquivo</p>
                    <p class="text-sm text-gray-500">Esta ação é irreversível.</p>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2 mb-5">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="fmDelModal.item?.name"></p>
            </div>
            <div class="flex gap-3">
                <button @click="fmDelModal.open=false" class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
                <button @click="fmDelete()" :disabled="fmDelModal.loading"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
                    <span x-text="fmDelModal.loading?'Excluindo...':'Excluir'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Rename ── --}}
    <div x-show="fmRenameM.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,.5)">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6">
            <p class="font-semibold text-gray-900 dark:text-white mb-4">Renomear</p>
            <input x-model="fmRenameM.newName" @keydown.enter="fmRename()"
                   type="text" placeholder="Novo nome"
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 mb-4">
            <div class="flex gap-3">
                <button @click="fmRenameM.open=false" class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
                <button @click="fmRename()" class="flex-1 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg">Renomear</button>
            </div>
        </div>
    </div>

    {{-- ── MODAL: New File / Folder ── --}}
    <div x-show="fmNewM.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,.5)">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6">
            <p class="font-semibold text-gray-900 dark:text-white mb-4" x-text="fmNewM.type==='dir'?'Nova pasta':'Novo arquivo'"></p>
            <input x-model="fmNewM.name" @keydown.enter="fmCreate()"
                   type="text" :placeholder="fmNewM.type==='dir'?'nome-da-pasta':'arquivo.php'"
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 mb-4">
            <div class="flex gap-3">
                <button @click="fmNewM.open=false" class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
                <button @click="fmCreate()" :disabled="fmNewM.creating"
                        class="flex-1 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
                    <span x-text="fmNewM.creating?'Criando...':'Criar'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Upload ── --}}
    <div x-show="fmUploadM.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,.5)">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6">
            <p class="font-semibold text-gray-900 dark:text-white mb-2">Upload de arquivo</p>
            <p class="text-xs text-gray-400 mb-4 font-mono" x-text="fmPath"></p>
            <label class="block border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center cursor-pointer hover:border-brand-400 transition-colors"
                   :class="fmUploadM.dragging?'border-brand-500 bg-brand-50':''">
                <input type="file" multiple class="sr-only" @change="fmUploadM.files=$event.target.files">
                <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <p class="text-sm text-gray-500" x-text="fmUploadM.files&&fmUploadM.files.length ? fmUploadM.files.length+' arquivo(s) selecionado(s)' : 'Clique para selecionar ou arraste aqui'"></p>
            </label>
            <div class="flex gap-3 mt-4">
                <button @click="fmUploadM.open=false" class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
                <button @click="fmUpload()" :disabled="fmUploadM.uploading||!fmUploadM.files||!fmUploadM.files.length"
                        class="flex-1 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
                    <span x-text="fmUploadM.uploading?'Enviando...':'Enviar'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Permissions (chmod) ── --}}
    <div x-show="fmChmodM.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,.5)">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6">
            <p class="font-semibold text-gray-900 dark:text-white mb-1">Alterar permissões</p>
            <p class="text-xs text-gray-400 mb-4" x-text="fmChmodM.item?.name"></p>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Código octal</label>
                <input x-model="fmChmodM.mode" type="text" placeholder="755" maxlength="4"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-center text-lg font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
                <div class="flex gap-2 mt-2 flex-wrap">
                    @foreach(['644','664','755','775','777','600','400'] as $p)
                    <button @click="fmChmodM.mode='{{ $p }}'"
                            :class="fmChmodM.mode==='{{ $p }}'?'bg-brand-100 border-brand-400 text-brand-700':'border-gray-200 text-gray-600 hover:border-brand-300'"
                            class="px-2.5 py-1 border rounded text-xs font-mono font-medium">{{ $p }}</button>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-3">
                <button @click="fmChmodM.open=false" class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
                <button @click="fmChmod()" class="flex-1 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg">Aplicar</button>
            </div>
        </div>
    </div>

    {{-- ── TAB: Databases ── --}}
    <div x-show="tab==='databases'" x-data="{ dbResetModal: false, dbResetItem: null, dbResetPass: '', dbResetLoading: false, dbMsg: '', dbMsgType: 'success' }">

        {{-- AJAX message --}}
        <div x-show="dbMsg" x-cloak x-transition
             :class="dbMsgType==='success'?'bg-green-50 border-green-200 text-green-700':'bg-red-50 border-red-200 text-red-700'"
             class="mb-3 border rounded-lg px-4 py-2.5 text-sm flex justify-between items-center">
            <span x-text="dbMsg"></span>
            <button @click="dbMsg=''" class="ml-4 opacity-60 hover:opacity-100">✕</button>
        </div>

        {{-- Formulário criar banco inline --}}
        @php $dbCount = $site->databases->count(); $dbLimit = auth()->user()->isAdmin() ? 999 : (auth()->user()->db_limit ?? 3); @endphp
        <div x-data="{ showDbForm: false }" class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-4 mb-3">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Bancos de dados</span>
                    @if(!auth()->user()->isAdmin())
                    <span class="ml-2 text-xs text-gray-400">{{ $dbCount }}/{{ $dbLimit }} utilizados</span>
                    @endif
                </div>
                @if($dbCount < $dbLimit)
                <button @click="showDbForm = !showDbForm"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Novo banco
                </button>
                @else
                <span class="text-xs text-red-500 font-medium">Limite de {{ $dbLimit }} banco(s) atingido</span>
                @endif
            </div>

            <div x-show="showDbForm" x-cloak x-transition class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo</label>
                        <select id="db_driver" class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="mysql">MySQL</option>
                            <option value="postgresql">PostgreSQL</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nome do banco</label>
                        <input type="text" id="db_name" placeholder="meu_banco"
                               class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Usuário</label>
                        <input type="text" id="db_username" placeholder="meu_usuario"
                               class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Senha</label>
                        <input type="password" id="db_password" placeholder="Mínimo 8 caracteres"
                               class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <button @click="showDbForm = false" class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200">Cancelar</button>
                    <button @click="dbCreate($el.closest('[x-data]'), $refs)" :disabled="dbCreateLoading"
                            class="px-4 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg disabled:opacity-50">
                        <span x-text="dbCreateLoading ? 'Criando...' : 'Criar banco'"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800">
        @if($site->databases->isEmpty())
        <div class="p-8 text-center">
            <p class="text-sm text-gray-400">Nenhum banco vinculado a este site ainda.</p>
        </div>
        @else
        <table class="w-full">
            <thead><tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Banco</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Usuário</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                <th class="px-4 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                @foreach($site->databases as $db)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $db->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $db->username }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $db->driver==='mysql'?'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300':'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                            {{ strtoupper($db->driver) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            {{-- phpMyAdmin --}}
                            @if($db->driver === 'mysql')
                            <a href="/phpmyadmin/index.php?db={{ $db->name }}" target="_blank"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-orange-100 hover:text-orange-700 dark:hover:bg-orange-900/30 dark:hover:text-orange-300 rounded-lg transition-colors"
                               title="Abrir no phpMyAdmin">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                                phpMyAdmin
                            </a>

                            {{-- Reset senha --}}
                            <button @click="dbResetItem={{ json_encode(['id'=>$db->id,'name'=>$db->name,'username'=>$db->username]) }}; dbResetPass=''; dbResetModal=true"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-brand-100 hover:text-brand-700 dark:hover:bg-brand-900/30 dark:hover:text-brand-300 rounded-lg transition-colors"
                                    title="Redefinir senha">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                Senha
                            </button>
                            @endif

                            {{-- Deletar --}}
                            <form method="POST" action="{{ route('databases.destroy', $db) }}"
                                  onsubmit="return confirm('Remover banco {{ $db->name }}? Esta ação é irreversível.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-red-100 hover:text-red-700 dark:hover:bg-red-900/30 dark:hover:text-red-300 rounded-lg transition-colors"
                                        title="Remover banco">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Remover
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        </div>

        {{-- Modal reset senha --}}
        <div x-show="dbResetModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.5)">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6">
                <p class="font-semibold text-gray-900 dark:text-white mb-1">Redefinir senha do banco</p>
                <p class="text-xs text-gray-400 mb-4" x-text="dbResetItem ? dbResetItem.username + ' @ ' + dbResetItem.name : ''"></p>
                <input x-model="dbResetPass" type="password" placeholder="Nova senha (mín. 8 caracteres)"
                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 mb-4">
                <div class="flex gap-3">
                    <button @click="dbResetModal=false" class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
                    <button @click="siteDbResetPass()" :disabled="dbResetLoading || dbResetPass.length < 8"
                            class="flex-1 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
                        <span x-text="dbResetLoading ? 'Aguarde...' : 'Redefinir'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── TAB: Cron ── --}}
    <div x-show="tab==='cron'" class="space-y-4">
        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Novo Cron Job</h3>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Template</label>
                <select x-model="cronTemplate" @change="applyCronTemplate()"
                        class="w-full sm:w-80 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="every_minute">A cada minuto — * * * * *</option>
                    <option value="every_5min">A cada 5 minutos — */5 * * * *</option>
                    <option value="every_15min">A cada 15 minutos — */15 * * * *</option>
                    <option value="every_30min">A cada 30 minutos — */30 * * * *</option>
                    <option value="every_hour">A cada hora — 0 * * * *</option>
                    <option value="every_day">Todo dia à meia-noite — 0 0 * * *</option>
                    <option value="every_week">Toda semana (dom) — 0 0 * * 0</option>
                    <option value="every_month">Todo mês (dia 1) — 0 0 1 * *</option>
                    <option value="manual">Manual</option>
                </select>
            </div>
            <div class="grid grid-cols-5 gap-2 mb-4">
                <div><label class="block text-xs text-gray-500 mb-1">Minuto</label><input x-model="cronM" type="text" class="w-full px-2 py-2 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Hora</label><input x-model="cronH" type="text" class="w-full px-2 py-2 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Dia</label><input x-model="cronD" type="text" class="w-full px-2 py-2 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Mês</label><input x-model="cronMo" type="text" class="w-full px-2 py-2 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Dia Sem.</label><input x-model="cronDw" type="text" class="w-full px-2 py-2 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Comando</label>
                    <input x-model="cronCommand" type="text"
                           placeholder="/usr/bin/php {{ $site->root_path }}/../artisan schedule:run >> /dev/null 2>&1"
                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Executar como</label>
                    <input x-model="cronUser" type="text" placeholder="www-data"
                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>
            <button @click="cronAdd()" :disabled="cronLoading || !cronCommand.trim()"
                    class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg disabled:opacity-50">
                <span x-text="cronLoading ? 'Adicionando...' : 'Adicionar Cron Job'"></span>
            </button>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Jobs ativos — <span x-text="cronJobs.length"></span></p>
            </div>
            <template x-if="cronJobs.length === 0">
                <div class="px-5 py-8 text-center text-sm text-gray-400">Nenhum cron job configurado.</div>
            </template>
            <template x-if="cronJobs.length > 0">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Schedule</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Comando</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase w-24">Usuário</th>
                            <th class="w-10 px-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        <template x-for="job in cronJobs" :key="job.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3"><code class="text-xs font-mono text-brand-600 bg-brand-50 dark:bg-brand-900/20 px-2 py-0.5 rounded" x-text="job.schedule"></code></td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300 max-w-xs truncate" x-text="job.command"></td>
                                <td class="px-4 py-3 text-xs text-gray-500" x-text="job.run_as"></td>
                                <td class="px-2 py-3">
                                    <button @click="cronDelete(job.id)" class="p-1.5 text-gray-300 hover:text-red-600 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
        </div>
    </div>

    {{-- ── TAB: Nginx / Vhost ── --}}
    <div x-show="tab==='vhost'">
        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <code class="text-xs text-gray-500" x-text="vhostPath||'{{ $site->nginx_config_path }}'"></code>
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1 text-xs text-amber-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Cuidado com a sintaxe
                    </span>
                    <button @click="saveVhost()" :disabled="vhostSaving"
                            class="px-3 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-medium rounded-lg disabled:opacity-50">
                        <span x-text="vhostSaving?'Salvando...':'Salvar & Reload'"></span>
                    </button>
                </div>
            </div>
            <div x-show="vhostLoading" class="p-8 text-center text-sm text-gray-400">Carregando...</div>
            <div x-show="!vhostLoading && !vhostExists" class="p-8 text-center text-sm text-gray-400">Config Nginx não encontrada.</div>
            <textarea x-show="!vhostLoading && vhostExists" x-model="vhostContent" rows="28" spellcheck="false"
                      class="w-full bg-gray-950 text-green-400 font-mono text-sm px-5 py-4 focus:outline-none resize-none border-0"
                      style="tab-size:4;"></textarea>
        </div>
    </div>

    {{-- ── TAB: Segurança ── --}}
    <div x-show="tab==='security'" class="space-y-4">
        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Controle de Acesso por IP</h3>
            <div class="flex flex-wrap gap-4 mb-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" x-model="secIpMode" value="off" class="accent-brand-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Desativado</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" x-model="secIpMode" value="whitelist" class="accent-brand-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Whitelist — só IPs permitidos acessam</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" x-model="secIpMode" value="blacklist" class="accent-brand-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Blacklist — bloquear IPs específicos</span>
                </label>
            </div>
            <div x-show="secIpMode !== 'off'">
                <label class="block text-xs font-medium text-gray-500 mb-1.5">IPs (um por linha — aceita CIDR, ex: 192.168.1.0/24)</label>
                <textarea x-model="secIps" rows="5" placeholder="1.2.3.4&#10;192.168.0.0/24"
                          class="w-full px-3 py-2.5 text-sm font-mono border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Proteção por Senha (HTTP Basic Auth)</h3>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="secAuth" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-brand-500 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                </label>
            </div>
            <div x-show="secAuth" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Usuário</label>
                    <input x-model="secUser" type="text" placeholder="admin"
                           class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Senha <span class="text-gray-400 font-normal">(vazio = manter atual)</span></label>
                    <input x-model="secPass" type="password" placeholder="Nova senha"
                           class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button @click="saveSecurity()" :disabled="secLoading"
                    class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg disabled:opacity-50">
                <span x-text="secLoading ? 'Aplicando...' : 'Salvar e Aplicar'"></span>
            </button>
        </div>
    </div>

    {{-- ── TAB: Backups ── --}}
    <div x-show="tab==='backups'" class="space-y-4">

        {{-- AJAX message --}}
        <div x-show="bkMsg" x-cloak x-transition
             :class="bkMsgType==='success'?'bg-green-50 border-green-200 text-green-700':'bg-red-50 border-red-200 text-red-700'"
             class="border rounded-lg px-4 py-2.5 text-sm flex justify-between items-center">
            <span x-text="bkMsg"></span>
            <button @click="bkMsg=''" class="ml-4 opacity-60 hover:opacity-100">✕</button>
        </div>

        {{-- Header --}}
        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Backups do Site</h3>
                    <p class="text-xs text-gray-400 mt-1">Os backups são excluídos automaticamente após <strong>3 dias</strong>. Faça download antes do prazo.</p>
                </div>
                <button @click="bkCreate()" :disabled="bkLoading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg disabled:opacity-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span x-text="bkLoading ? 'Criando backup...' : 'Criar Backup'"></span>
                </button>
            </div>
        </div>

        {{-- Lista --}}
        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 overflow-hidden">
            <template x-if="bkList.length === 0">
                <div class="p-10 text-center">
                    <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <p class="text-sm text-gray-400">Nenhum backup criado ainda.</p>
                </div>
            </template>
            <template x-if="bkList.length > 0">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Arquivo</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Tamanho</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Criado em</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Expira em</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        <template x-for="bk in bkList" :key="bk.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-5 py-3 text-sm font-mono text-gray-700 dark:text-gray-300" x-text="bk.filename"></td>
                                <td class="px-5 py-3 text-sm text-gray-500" x-text="bk.formatted_size"></td>
                                <td class="px-5 py-3 text-sm text-gray-500" x-text="bk.created_at"></td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span x-text="bk.expires_in"></span>
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a :href="`/api/sites/{{ $site->domain }}/backups/${bk.id}/download`"
                                           class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-brand-100 hover:text-brand-700 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Download
                                        </a>
                                        <button @click="bkDelete(bk)"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-red-100 hover:text-red-700 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Remover
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
        </div>
    </div>

</div>{{-- /siteManager --}}

@push('scripts')
<script>
function siteManager() {
    return {
        tab: 'info',
        msg: '', msgType: 'success',
        actionLoading: false,

        // ── Terminal
        term: null, termWs: null, fitAddon: null,

        // ── Logs
        logWs: null, logType: 'var/www/sites/{{ $site->domain }}/logs/access.log',

        // ── File Manager state
        fmRootPath: '{{ addslashes("/var/www/sites/".$site->domain) }}/',
        fmPath:     '{{ addslashes("/var/www/sites/".$site->domain) }}/',
        fmItems:    [],
        fmLoading:  false,
        fmSelected: null,
        fmTree:     [],
        fmTreeRootPath: '{{ addslashes("/var/www/sites/".$site->domain) }}/',

        fmCtx:     { open: false, x: 0, y: 0, item: null },
        fmEditor:  { open: false, path: '', name: '', content: '', saving: false },
        fmDelModal:{ open: false, item: null, loading: false },
        fmRenameM: { open: false, item: null, newName: '' },
        fmNewM:    { open: false, type: 'file', name: '', creating: false },
        fmUploadM: { open: false, files: null, uploading: false, dragging: false },
        fmChmodM:  { open: false, item: null, mode: '644' },

        // ── Cron Jobs
        cronJobs:    @json($cronJobs),
        cronM: '*', cronH: '*', cronD: '*', cronMo: '*', cronDw: '*',
        cronCommand: '', cronUser: 'www-data', cronTemplate: 'every_minute',
        cronLoading: false,

        // ── Security
        secIpMode: '{{ $securityConfig['ip_mode'] ?? 'off' }}',
        secIps:    @json(implode("\n", $securityConfig['ips'] ?? [])),
        secAuth:    {{ !empty($securityConfig['auth_enabled']) ? 'true' : 'false' }},
        secUser:   '{{ $securityConfig['auth_user'] ?? '' }}',
        secPass:   '',
        secLoading: false,

        // ── Vhost
        vhostContent: '', vhostPath: '', vhostExists: false,
        vhostLoading: false, vhostSaving: false,

        // ── Backups
        bkList: @json($siteBackups),
        bkLoading: false, bkMsg: '', bkMsgType: 'success',

        // ── DB inline create
        dbCreateLoading: false,

        // ── Init
        init() {
            const hash  = window.location.hash.slice(1);
            const valid = ['info','terminal','logs','files','databases','cron','vhost','security','backups'];
            if (hash && valid.includes(hash)) {
                this.tab = hash;
                if (hash === 'terminal') this.$nextTick(() => this.initTerminal());
                if (hash === 'logs')     this.$nextTick(() => this.startLogs());
                if (hash === 'files')    this.$nextTick(() => { this.fmLoad(this.fmPath); this.fmLoadTree(); });
                if (hash === 'vhost')    this.$nextTick(() => this.loadVhost());
            }
        },

        setTab(t) {
            this.tab = t;
            window.location.hash = t;
            if (t === 'terminal') this.$nextTick(() => this.initTerminal());
            if (t === 'logs')     this.$nextTick(() => this.startLogs());
            if (t === 'files')    this.$nextTick(() => { this.fmLoad(this.fmPath); this.fmLoadTree(); });
            if (t === 'vhost')    this.$nextTick(() => this.loadVhost());
        },

        async doAction(url) {
            this.actionLoading = true; this.msg = '';
            try {
                const r = await fetch(url, { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} });
                const d = await r.json();
                this.msg = d.message || d.error || 'Erro';
                this.msgType = r.ok ? 'success' : 'error';
            } catch(e) { this.msg='Falha na requisição.'; this.msgType='error'; }
            this.actionLoading = false;
        },

        // ════════════════════════════════════════════════════════════
        // FILE MANAGER
        // ════════════════════════════════════════════════════════════

        async fmLoad(path) {
            if (!path) return;
            path = path.replace(/\/+/g, '/');
            if (!path.endsWith('/')) path += '/';
            this.fmPath = path;
            this.fmItems = [];
            this.fmSelected = null;
            this.fmLoading = true;
            this.fmCtxClose();
            try {
                const r = await fetch('/api/fm/list?path=' + encodeURIComponent(path), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await r.json();
                if (d.items) this.fmItems = d.items;
                else { this.msg = d.error || 'Erro ao listar.'; this.msgType = 'error'; }
            } catch(e) { this.msg = 'Erro de conexão.'; this.msgType = 'error'; }
            this.fmLoading = false;
        },

        async fmLoadTree() {
            try {
                const r = await fetch('/api/fm/list?path=' + encodeURIComponent(this.fmRootPath), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await r.json();
                this.fmTree = (d.items || [])
                    .filter(i => i.type === 'dir')
                    .map(i => ({ name: i.name, path: this.fmRootPath + i.name + '/', expanded: false, children: [] }));
            } catch(e) {}
        },

        async fmTreeExpand(node) {
            if (node.expanded) { node.expanded = false; return; }
            try {
                const r = await fetch('/api/fm/list?path=' + encodeURIComponent(node.path), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await r.json();
                node.children = (d.items || [])
                    .filter(i => i.type === 'dir')
                    .map(i => ({ name: i.name, path: node.path + i.name + '/', expanded: false, children: [] }));
                node.expanded = true;
            } catch(e) {}
        },

        fmGoUp() {
            const parts = this.fmPath.replace(/\/$/, '').split('/');
            parts.pop();
            const up = (parts.join('/') || '/') + '/';
            if (up.startsWith(this.fmRootPath) || up === this.fmRootPath) {
                this.fmLoad(up);
            }
        },

        fmBreadcrumbs() {
            const base = this.fmRootPath.replace(/\/$/, '');
            const cur  = this.fmPath.replace(/\/$/, '');
            if (cur === base) return [];
            const rel  = cur.slice(base.length + 1);
            const parts = rel.split('/');
            const crumbs = [];
            let acc = base;
            for (const p of parts) {
                acc += '/' + p;
                crumbs.push({ name: p, path: acc + '/' });
            }
            return crumbs;
        },

        // ── Context Menu
        fmCtxOpen(e, item) {
            e.preventDefault();
            const margin = 10;
            const menuW  = 176, menuH = 200;
            const x = Math.min(e.clientX, window.innerWidth  - menuW - margin);
            const y = Math.min(e.clientY, window.innerHeight - menuH - margin);
            this.fmCtx = { open: true, x, y, item };
            this.fmSelected = item.name;
        },
        fmCtxClose() { this.fmCtx.open = false; },

        // ── File Edit
        async fmEdit(item) {
            const path = this.fmPath + item.name;
            const r = await fetch('/api/fm/view?path=' + encodeURIComponent(path), {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const d = await r.json();
            if (d.error) { this.msg = d.error; this.msgType = 'error'; return; }
            this.fmEditor = { open: true, path, name: item.name, content: d.content || '', saving: false };
        },

        async fmSaveFile() {
            this.fmEditor.saving = true;
            try {
                const r = await fetch('/api/fm/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ path: this.fmEditor.path, content: this.fmEditor.content })
                });
                const d = await r.json();
                this.msg = d.message || d.error;
                this.msgType = r.ok ? 'success' : 'error';
                if (r.ok) this.fmEditor.open = false;
            } catch(e) { this.msg = 'Erro ao salvar.'; this.msgType = 'error'; }
            this.fmEditor.saving = false;
        },

        // ── Download
        fmDownload(item) {
            const path = this.fmPath + item.name;
            window.open('/api/fm/download?path=' + encodeURIComponent(path), '_blank');
        },

        // ── Delete modal
        fmDeleteModal(item) { this.fmDelModal = { open: true, item, loading: false }; },
        async fmDelete() {
            this.fmDelModal.loading = true;
            try {
                const path = this.fmPath + this.fmDelModal.item.name;
                const r = await fetch('/api/fm/delete', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ path })
                });
                const d = await r.json();
                this.msg = d.message || d.error;
                this.msgType = r.ok ? 'success' : 'error';
                if (r.ok) { this.fmDelModal.open = false; this.fmLoad(this.fmPath); }
            } catch(e) { this.msg = 'Erro ao excluir.'; this.msgType = 'error'; }
            this.fmDelModal.loading = false;
        },

        // ── Rename modal
        fmRenameModal(item) { this.fmRenameM = { open: true, item, newName: item.name }; },
        async fmRename() {
            if (!this.fmRenameM.newName.trim()) return;
            try {
                const r = await fetch('/api/fm/rename', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ path: this.fmPath + this.fmRenameM.item.name, new_name: this.fmRenameM.newName })
                });
                const d = await r.json();
                this.msg = d.message || d.error;
                this.msgType = r.ok ? 'success' : 'error';
                if (r.ok) { this.fmRenameM.open = false; this.fmLoad(this.fmPath); }
            } catch(e) { this.msg = 'Erro ao renomear.'; this.msgType = 'error'; }
        },

        // ── New file / folder modal
        fmNewModal(type) { this.fmNewM = { open: true, type, name: '', creating: false }; },
        async fmCreate() {
            if (!this.fmNewM.name.trim()) return;
            this.fmNewM.creating = true;
            const path = this.fmPath + this.fmNewM.name.trim();
            const endpoint = this.fmNewM.type === 'dir' ? '/api/fm/mkdir' : '/api/fm/touch';
            try {
                const r = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ path })
                });
                const d = await r.json();
                this.msg = d.message || d.error;
                this.msgType = r.ok ? 'success' : 'error';
                if (r.ok) { this.fmNewM.open = false; this.fmLoad(this.fmPath); }
            } catch(e) { this.msg = 'Erro ao criar.'; this.msgType = 'error'; }
            this.fmNewM.creating = false;
        },

        // ── Upload modal
        fmUploadModal() { this.fmUploadM = { open: true, files: null, uploading: false, dragging: false }; },
        async fmUpload() {
            if (!this.fmUploadM.files || !this.fmUploadM.files.length) return;
            this.fmUploadM.uploading = true;
            let uploaded = 0, lastError = '';
            for (const file of Array.from(this.fmUploadM.files)) {
                const fd = new FormData();
                fd.append('path', this.fmPath);
                fd.append('file', file);
                try {
                    const r = await fetch('/api/fm/upload', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: fd
                    });
                    if (r.ok) {
                        uploaded++;
                    } else {
                        const d = await r.json().catch(() => ({}));
                        lastError = d.error || d.message || `Erro HTTP ${r.status}`;
                    }
                } catch(e) { lastError = 'Falha de conexão'; }
            }
            if (lastError) {
                this.msg = lastError;
                this.msgType = 'error';
            } else {
                this.msg = `${uploaded} arquivo(s) enviado(s) com sucesso.`;
                this.msgType = 'success';
            }
            this.fmUploadM.open = false;
            this.fmLoad(this.fmPath);
            this.fmUploadM.uploading = false;
        },

        // ── Chmod modal
        fmChmodModal(item) { this.fmChmodM = { open: true, item, mode: item.perms || '644' }; },
        async fmChmod() {
            try {
                const r = await fetch('/api/fm/chmod', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ path: this.fmPath + this.fmChmodM.item.name, mode: this.fmChmodM.mode })
                });
                const d = await r.json();
                this.msg = d.message || d.error;
                this.msgType = r.ok ? 'success' : 'error';
                if (r.ok) { this.fmChmodM.open = false; this.fmLoad(this.fmPath); }
            } catch(e) { this.msg = 'Erro ao alterar permissões.'; this.msgType = 'error'; }
        },

        // ── File type color badge
        fmExtColor(ext) {
            const map = {
                php:['bg-purple-600'],  html:['bg-blue-500'],  htm:['bg-blue-500'],
                css:['bg-sky-500'],     js:['bg-yellow-500'],  ts:['bg-blue-600'],
                json:['bg-yellow-400'], xml:['bg-orange-400'], txt:['bg-gray-400'],
                md:['bg-gray-500'],     sql:['bg-orange-600'], env:['bg-red-600'],
                png:['bg-green-500'],   jpg:['bg-green-500'],  jpeg:['bg-green-500'],
                gif:['bg-green-400'],   svg:['bg-green-400'],  webp:['bg-green-400'],
                zip:['bg-red-400'],     tar:['bg-red-400'],    gz:['bg-red-400'],
                sh:['bg-gray-700'],     log:['bg-gray-400'],
            };
            return (map[ext] || ['bg-gray-400'])[0];
        },

        // ════════════════════════════════════════════════════════════
        // CRON JOBS
        // ════════════════════════════════════════════════════════════
        applyCronTemplate() {
            const t = {
                every_minute:  { m:'*',    h:'*', d:'*', mo:'*', dw:'*' },
                every_5min:    { m:'*/5',  h:'*', d:'*', mo:'*', dw:'*' },
                every_15min:   { m:'*/15', h:'*', d:'*', mo:'*', dw:'*' },
                every_30min:   { m:'*/30', h:'*', d:'*', mo:'*', dw:'*' },
                every_hour:    { m:'0',    h:'*', d:'*', mo:'*', dw:'*' },
                every_day:     { m:'0',    h:'0', d:'*', mo:'*', dw:'*' },
                every_week:    { m:'0',    h:'0', d:'*', mo:'*', dw:'0' },
                every_month:   { m:'0',    h:'0', d:'1', mo:'*', dw:'*' },
            }[this.cronTemplate];
            if (t) { this.cronM=t.m; this.cronH=t.h; this.cronD=t.d; this.cronMo=t.mo; this.cronDw=t.dw; }
        },
        async cronAdd() {
            if (!this.cronCommand.trim()) return;
            this.cronLoading = true;
            try {
                const r = await fetch('/api/sites/{{ $site->domain }}/cron', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' },
                    body: JSON.stringify({ cron_m:this.cronM, cron_h:this.cronH, cron_d:this.cronD, cron_mo:this.cronMo, cron_dw:this.cronDw, command:this.cronCommand, run_as:this.cronUser||'www-data' })
                });
                const d = await r.json();
                if (r.ok) { this.cronJobs.push(d.job); this.cronCommand=''; this.msg=d.message; this.msgType='success'; }
                else { this.msg=d.message||d.error||'Erro'; this.msgType='error'; }
            } catch(e) { this.msg='Erro de conexão.'; this.msgType='error'; }
            this.cronLoading = false;
        },
        async cronDelete(id) {
            if (!confirm('Remover este cron job?')) return;
            try {
                const r = await fetch(`/api/sites/{{ $site->domain }}/cron/${id}`, {
                    method:'DELETE', headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' }
                });
                const d = await r.json();
                if (r.ok) { this.cronJobs=this.cronJobs.filter(j=>j.id!==id); this.msg=d.message; this.msgType='success'; }
                else { this.msg=d.error||'Erro'; this.msgType='error'; }
            } catch(e) { this.msg='Erro.'; this.msgType='error'; }
        },

        // ════════════════════════════════════════════════════════════
        // BACKUPS
        // ════════════════════════════════════════════════════════════
        async bkCreate() {
            if (!confirm('Criar um backup completo do site agora? Será excluído automaticamente em 3 dias.')) return;
            this.bkLoading = true; this.bkMsg = '';
            try {
                const r = await fetch('/api/sites/{{ $site->domain }}/backups', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                });
                const d = await r.json();
                if (r.ok) {
                    this.bkList.unshift(d.backup);
                    this.bkMsg = d.message; this.bkMsgType = 'success';
                } else {
                    this.bkMsg = d.error || 'Erro ao criar backup.'; this.bkMsgType = 'error';
                }
            } catch(e) { this.bkMsg = 'Erro de conexão.'; this.bkMsgType = 'error'; }
            this.bkLoading = false;
        },

        async bkDelete(bk) {
            if (!confirm(`Remover o backup ${bk.filename}?`)) return;
            try {
                const r = await fetch(`/api/sites/{{ $site->domain }}/backups/${bk.id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                });
                if (r.ok) {
                    this.bkList = this.bkList.filter(b => b.id !== bk.id);
                    this.bkMsg = 'Backup removido.'; this.bkMsgType = 'success';
                }
            } catch(e) { this.bkMsg = 'Erro de conexão.'; this.bkMsgType = 'error'; }
        },

        // DATABASES — inline create
        // ════════════════════════════════════════════════════════════
        async dbCreate() {
            const name     = document.getElementById('db_name')?.value.trim();
            const username = document.getElementById('db_username')?.value.trim();
            const password = document.getElementById('db_password')?.value;
            const driver   = document.getElementById('db_driver')?.value;

            if (!name || !username || !password) {
                this.dbMsg = 'Preencha todos os campos.'; this.dbMsgType = 'error'; return;
            }
            this.dbCreateLoading = true; this.dbMsg = '';
            try {
                const r = await fetch('/api/sites/{{ $site->domain }}/databases', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ name, username, password, driver }),
                });
                const d = await r.json();
                if (r.ok) {
                    this.dbMsg = d.message; this.dbMsgType = 'success';
                    // Reload page to refresh the table
                    setTimeout(() => window.location.href = window.location.pathname + '#databases', 800);
                } else {
                    this.dbMsg = d.error || 'Erro ao criar banco.'; this.dbMsgType = 'error';
                }
            } catch(e) { this.dbMsg = 'Erro de conexão.'; this.dbMsgType = 'error'; }
            this.dbCreateLoading = false;
        },

        async siteDbResetPass() {
            if (!this.dbResetItem || this.dbResetPass.length < 8) return;
            this.dbResetLoading = true;
            try {
                const r = await fetch(`/databases/${this.dbResetItem.id}/reset-password`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ password: this.dbResetPass })
                });
                const d = await r.json();
                this.dbMsg = d.message || d.error;
                this.dbMsgType = r.ok ? 'success' : 'error';
                if (r.ok) this.dbResetModal = false;
            } catch(e) { this.dbMsg = 'Erro de conexão.'; this.dbMsgType = 'error'; }
            this.dbResetLoading = false;
        },

        // SECURITY
        // ════════════════════════════════════════════════════════════
        async saveSecurity() {
            this.secLoading = true; this.msg = '';
            try {
                const r = await fetch('/api/sites/{{ $site->domain }}/security', {
                    method:'POST',
                    headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' },
                    body: JSON.stringify({ ip_mode:this.secIpMode, ips:this.secIps, auth_enabled:this.secAuth, auth_user:this.secUser, auth_pass:this.secPass })
                });
                const d = await r.json();
                this.msg = d.message || d.error || 'Erro';
                this.msgType = r.ok ? 'success' : 'error';
                if (r.ok) this.secPass = '';
            } catch(e) { this.msg='Erro de conexão.'; this.msgType='error'; }
            this.secLoading = false;
        },

        // ════════════════════════════════════════════════════════════
        // TERMINAL
        // ════════════════════════════════════════════════════════════
        initTerminal() {
            if (this.term) return;
            const el = document.getElementById('terminal');
            if (!el) return;
            this.term = new Terminal({
                fontFamily: 'Menlo, Monaco, "Courier New", monospace',
                fontSize: 13,
                theme: { background: '#030712', foreground: '#4ade80', cursor: '#4ade80' },
                cursorBlink: true,
            });
            this.fitAddon = new FitAddon.FitAddon();
            this.term.loadAddon(this.fitAddon);
            this.term.open(el);
            this.fitAddon.fit();
            window.addEventListener('resize', () => this.fitAddon?.fit());
            this.connectTermWs();
            this.term.onData(data => {
                if (this.termWs?.readyState === WebSocket.OPEN)
                    this.termWs.send(JSON.stringify({ type:'input', data }));
            });
            this.term.onResize(({ cols, rows }) => {
                if (this.termWs?.readyState === WebSocket.OPEN)
                    this.termWs.send(JSON.stringify({ type:'resize', cols, rows }));
            });
        },
        connectTermWs() {
            clearTimeout(this._termReconnect);
            const proto = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const cols  = this.term?.cols ?? 120;
            const rows  = this.term?.rows ?? 30;
            const path  = encodeURIComponent('{{ addslashes($site->root_path) }}');
            const url   = `${proto}//${window.location.host}/ws/terminal?cols=${cols}&rows=${rows}&path=${path}`;
            this.termWs = new WebSocket(url);
            this.termWs.onopen = () => {
                this.term?.writeln('\r\n\x1b[32m● Conectado\x1b[0m\r\n');
                this._termPing = setInterval(() => {
                    if (this.termWs?.readyState === WebSocket.OPEN)
                        this.termWs.send(JSON.stringify({ type: 'ping' }));
                }, 25000);
            };
            this.termWs.onmessage = (e) => {
                try {
                    const p = JSON.parse(e.data);
                    if (p.type==='output') this.term?.write(p.data);
                    if (p.type==='exit')   this.term?.writeln('\r\n\x1b[33m[Sessão encerrada]\x1b[0m');
                } catch {}
            };
            this.termWs.onclose = () => {
                clearInterval(this._termPing);
                if (!this._termManualClose) {
                    this.term?.writeln('\r\n\x1b[33m[Desconectado — reconectando...]\x1b[0m');
                    this._termReconnect = setTimeout(() => this.connectTermWs(), 2000);
                } else {
                    this._termManualClose = false;
                }
            };
            this.termWs.onerror = () => {
                clearInterval(this._termPing);
                this.term?.writeln('\r\n\x1b[31m[Erro WebSocket]\x1b[0m');
            };
        },
        reconnectTerminal() {
            clearInterval(this._termPing);
            clearTimeout(this._termReconnect);
            this._termManualClose = true;
            this.termWs?.close();
            this.term?.clear();
            this.connectTermWs();
        },

        // ════════════════════════════════════════════════════════════
        // LOGS
        // ════════════════════════════════════════════════════════════
        startLogs() {
            this.logWs?.close();
            const out = document.getElementById('log-output');
            if (!out) return;
            out.textContent = '';
            const proto = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            this.logWs = new WebSocket(`${proto}//${window.location.host}/ws/logs/${this.logType}`);
            this.logWs.onmessage = (e) => {
                try {
                    const p = JSON.parse(e.data);
                    if (p.type==='log')   { out.textContent += p.data; out.scrollTop = out.scrollHeight; }
                    if (p.type==='error') out.textContent += '\n[Erro: ' + p.message + ']\n';
                } catch {}
            };
        },
        clearLogs() { const o = document.getElementById('log-output'); if(o) o.textContent=''; },

        // ════════════════════════════════════════════════════════════
        // VHOST
        // ════════════════════════════════════════════════════════════
        async loadVhost() {
            if (this.vhostContent !== '' || this.vhostLoading) return;
            this.vhostLoading = true;
            try {
                const r = await fetch('/api/sites/{{ $site->domain }}/vhost', {
                    headers: { 'Accept':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' }
                });
                const d = await r.json();
                this.vhostContent = d.content || '';
                this.vhostPath    = d.path    || '';
                this.vhostExists  = d.exists  || false;
            } catch(e) { this.msg='Erro ao carregar configuração Nginx.'; this.msgType='error'; }
            this.vhostLoading = false;
        },
        async saveVhost() {
            this.vhostSaving = true; this.msg = '';
            try {
                const r = await fetch('/api/sites/{{ $site->domain }}/vhost', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}' },
                    body: JSON.stringify({ content: this.vhostContent })
                });
                const d = await r.json();
                this.msg = d.message || d.error || 'Erro';
                this.msgType = r.ok ? 'success' : 'error';
            } catch(e) { this.msg='Erro ao salvar.'; this.msgType='error'; }
            this.vhostSaving = false;
        },
    };
}
</script>
@endpush
@endsection
