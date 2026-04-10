@extends('layouts.app')

@section('title', 'Usuários')
@section('header', 'Usuários')

@section('content')
<div x-data="{ showForm: false, editUser: null, editSites: [], confirmDelete: null }">

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

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $users->count() }} usuário(s) cadastrado(s)</p>
    <button @click="showForm = !showForm; editUser = null"
            class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 shadow-sm shadow-brand-500/20 hover:bg-brand-600 hover:-translate-y-0.5 transition-all text-white text-sm font-semibold rounded-full transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Novo Usuário
    </button>
</div>

{{-- Formulário criar/editar --}}
<div x-show="showForm" x-cloak x-transition
     class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 p-6 mb-6">

    <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-5"
        x-text="editUser ? 'Editar usuário' : 'Criar usuário'"></h2>

    {{-- Create form --}}
    <template x-if="!editUser">
        <form method="POST" action="{{ route('users.store') }}" x-data="{ newRole: 'user' }">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nome</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Senha</label>
                    <input type="password" name="password" required placeholder="Mínimo 8 caracteres"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tipo</label>
                    <select name="role" x-model="newRole"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="user">Usuário — acesso a sites específicos</option>
                        <option value="admin">Administrador — acesso total</option>
                    </select>
                </div>
            </div>

            {{-- Limite de bancos (somente user) --}}
            <div x-show="newRole === 'user'" class="sm:col-span-2 mt-0">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Limite de bancos de dados por site
                </label>
                <input type="number" name="db_limit" value="3" min="1" max="50"
                       class="w-32 px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                <p class="text-xs text-gray-400 mt-1">Máximo de bancos que este usuário pode criar por site.</p>
            </div>

            {{-- Sites (visível para role=user, que é o default) --}}
            <div x-show="newRole === 'user'" class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sites com acesso</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach(\App\Models\Site::orderBy('domain')->get() as $site)
                    <label class="flex items-center gap-2.5 p-2.5 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                        <input type="checkbox" name="site_ids[]" value="{{ $site->id }}"
                               class="rounded text-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $site->domain }}</span>
                    </label>
                    @endforeach
                </div>
                @if(\App\Models\Site::count() === 0)
                    <p class="text-sm text-gray-400">Nenhum site cadastrado ainda.</p>
                @endif
            </div>

            <div class="flex gap-3 mt-5">
                <button type="button" @click="showForm = false"
                        class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Cancelar</button>
                <button type="submit"
                        class="px-5 py-2 bg-brand-500 shadow-sm shadow-brand-500/20 hover:bg-brand-600 hover:-translate-y-0.5 transition-all text-white text-sm font-semibold rounded-full">Criar usuário</button>
            </div>
        </form>
    </template>

    {{-- Edit form (rendered dynamically) --}}
    <template x-if="editUser">
        <form method="POST" :action="`/users/${editUser.id}`">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nome</label>
                    <input type="text" name="name" :value="editUser.name" required
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">E-mail</label>
                    <input type="email" name="email" :value="editUser.email" required
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nova senha <span class="text-gray-400">(deixe em branco para manter)</span></label>
                    <input type="password" name="password" placeholder="Mínimo 8 caracteres"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tipo</label>
                    <select name="role" x-model="editUser.role"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="user">Usuário — acesso a sites específicos</option>
                        <option value="admin">Administrador — acesso total</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="edit_is_active"
                           :checked="editUser.is_active" class="rounded text-brand-500">
                    <label for="edit_is_active" class="text-sm text-gray-700 dark:text-gray-300">Usuário ativo</label>
                </div>
            </div>

            {{-- Sites --}}
            <div x-show="editUser.role === 'user'" class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sites com acesso</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach(\App\Models\Site::orderBy('domain')->get() as $site)
                    <label class="flex items-center gap-2.5 p-2.5 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                        <input type="checkbox" name="site_ids[]" value="{{ $site->id }}"
                               :checked="editSites.includes({{ $site->id }})"
                               class="rounded text-brand-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $site->domain }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3 mt-5">
                <button type="button" @click="showForm = false; editUser = null"
                        class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Cancelar</button>
                <button type="submit"
                        class="px-5 py-2 bg-brand-500 shadow-sm shadow-brand-500/20 hover:bg-brand-600 hover:-translate-y-0.5 transition-all text-white text-sm font-semibold rounded-full">Salvar</button>
            </div>
        </form>
    </template>
</div>

{{-- Lista de usuários --}}
<div class="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-[0px_4px_24px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Usuário</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Tipo</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Sites</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
            @foreach($users as $user)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                <td class="px-5 py-4">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                </td>
                <td class="px-5 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                        {{ $user->role === 'admin' ? 'Admin' : 'Usuário' }}
                    </span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                    @if($user->role === 'admin')
                        <span class="text-xs text-gray-400">Acesso total</span>
                    @else
                        {{ $user->allowed_sites_count }} site(s)
                    @endif
                </td>
                <td class="px-5 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $user->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                        {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                    </span>
                </td>
                <td class="px-5 py-4">
                    <div class="flex items-center justify-end gap-1">
                        {{-- Editar --}}
                        @php $siteIds = $user->allowedSites->pluck('id')->toArray(); @endphp
                        <button @click="editUser = {{ json_encode(['id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'role'=>$user->role,'is_active'=>$user->is_active]) }}; editSites = {{ json_encode($siteIds) }}; showForm = true"
                                class="p-1.5 text-gray-400 hover:text-brand-600 rounded" title="Editar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>

                        {{-- Deletar --}}
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                              onsubmit="return confirm('Remover o usuário {{ $user->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 rounded" title="Remover">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('userForm', () => ({
        newRole: 'user',
    }));
});
</script>
@endpush
@endsection
