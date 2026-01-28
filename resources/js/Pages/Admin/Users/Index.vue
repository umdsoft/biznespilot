<template>
    <AdminLayout :title="t('admin.users.title')">
        <div class="min-h-screen">
            <!-- Clean Header -->
            <div class="border-b border-gray-200 dark:border-gray-700/50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm sticky top-0 z-10">
                <div class="max-w-[1600px] mx-auto px-6 py-5">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ t('admin.users.title') }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ stats.total }} {{ t('admin.users.total_users').toLowerCase() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-6 py-6">
                <!-- Compact Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-500/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.users.total_users') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-50 dark:bg-red-500/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.admins }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.users.admins') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.owners }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.users.business_owners') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-violet-50 dark:bg-violet-500/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.this_month }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('admin.users.new_this_month') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inline Filters -->
                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="filters.search"
                            type="text"
                            :placeholder="t('admin.users.search_placeholder')"
                            class="w-full pl-10 pr-4 py-2.5 text-sm bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg focus:ring-2 focus:ring-gray-900/10 dark:focus:ring-white/10 focus:border-gray-300 dark:focus:border-gray-600 text-gray-900 dark:text-white placeholder-gray-400"
                        />
                    </div>
                    <select
                        v-model="filters.role"
                        class="px-4 py-2.5 text-sm bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg focus:ring-2 focus:ring-gray-900/10 dark:focus:ring-white/10 text-gray-900 dark:text-white min-w-[140px]"
                    >
                        <option value="">{{ t('admin.users.role') }}: {{ t('admin.common.all') }}</option>
                        <option value="super_admin">{{ t('admin.users.roles.super_admin') }}</option>
                        <option value="admin">{{ t('admin.users.roles.admin') }}</option>
                        <option value="owner">{{ t('admin.users.roles.owner') }}</option>
                        <option value="user">{{ t('admin.users.roles.user') }}</option>
                    </select>
                    <select
                        v-model="filters.sort"
                        class="px-4 py-2.5 text-sm bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg focus:ring-2 focus:ring-gray-900/10 dark:focus:ring-white/10 text-gray-900 dark:text-white min-w-[140px]"
                    >
                        <option value="newest">{{ t('admin.common.newest') }}</option>
                        <option value="oldest">{{ t('admin.common.oldest') }}</option>
                        <option value="name">{{ t('admin.common.by_name') }}</option>
                        <option value="last_login">{{ t('admin.users.last_login') }}</option>
                    </select>
                </div>

                <!-- Clean Table -->
                <div class="bg-white dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">
                                    {{ t('admin.users.user') }}
                                </th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden md:table-cell">
                                    {{ t('admin.users.contact') }}
                                </th>
                                <th class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">
                                    {{ t('admin.users.role') }}
                                </th>
                                <th class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">
                                    {{ t('admin.users.businesses') }}
                                </th>
                                <th class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">
                                    2FA
                                </th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden sm:table-cell">
                                    {{ t('admin.users.last_login') }}
                                </th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
                            <tr
                                v-for="user in filteredUsers"
                                :key="user.id"
                                class="group hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center text-sm font-medium text-gray-600 dark:text-gray-300">
                                            {{ user.name?.charAt(0).toUpperCase() || 'U' }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">@{{ user.login }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 hidden md:table-cell">
                                    <p class="text-sm text-gray-900 dark:text-white">{{ user.email }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ user.phone || '—' }}</p>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="getRoleBadgeClass(user.role)" class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md">
                                        {{ getRoleLabel(user.role) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center hidden lg:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ user.businesses_count }}</span>
                                </td>
                                <td class="px-5 py-4 text-center hidden lg:table-cell">
                                    <span v-if="user.two_factor_enabled" class="inline-flex items-center justify-center w-5 h-5 bg-emerald-100 dark:bg-emerald-500/20 rounded-full">
                                        <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <span v-else class="inline-flex items-center justify-center w-5 h-5 bg-gray-100 dark:bg-gray-700 rounded-full">
                                        <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </td>
                                <td class="px-5 py-4 hidden sm:table-cell">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ user.last_login_at || t('admin.users.never') }}
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="relative" v-click-outside="() => closeMenu(user.id)">
                                        <button
                                            @click="toggleMenu(user.id)"
                                            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                            </svg>
                                        </button>
                                        <Transition
                                            enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95"
                                        >
                                            <div
                                                v-if="openMenuId === user.id"
                                                class="absolute right-0 mt-1 w-36 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-20"
                                            >
                                                <button
                                                    @click="viewUser(user)"
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    {{ t('common.view') }}
                                                </button>
                                                <button
                                                    @click="editUser(user)"
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                    </svg>
                                                    {{ t('common.edit') }}
                                                </button>
                                                <button
                                                    v-if="user.role !== 'super_admin'"
                                                    @click="confirmDelete(user)"
                                                    class="w-full px-3 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                    {{ t('common.delete') }}
                                                </button>
                                            </div>
                                        </Transition>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="filteredUsers.length === 0" class="py-12 text-center">
                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('admin.users.not_found') }}</p>
                    </div>

                    <!-- Footer -->
                    <div v-if="filteredUsers.length > 0" class="px-5 py-3 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-800/30">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ filteredUsers.length }} / {{ users.length }} {{ t('admin.common.results') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Delete Modal -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition-opacity duration-150"
                    leave-active-class="transition-opacity duration-100"
                    enter-from-class="opacity-0"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showDeleteModal" @click="showDeleteModal = false" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                        <Transition
                            enter-active-class="transition-all duration-150"
                            leave-active-class="transition-all duration-100"
                            enter-from-class="opacity-0 scale-95"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="showDeleteModal" @click.stop class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-5 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 bg-red-100 dark:bg-red-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ t('admin.users.delete_user') }}</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ t('admin.users.delete_confirm') }} <strong class="text-gray-900 dark:text-white">{{ userToDelete?.name }}</strong>?
                                        </p>
                                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">
                                            {{ t('admin.common.action_irreversible') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-5">
                                    <button
                                        @click="showDeleteModal = false"
                                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                    >
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button
                                        @click="deleteUser"
                                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
                                    >
                                        {{ t('common.delete') }}
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            admins: 0,
            owners: 0,
            this_month: 0
        })
    },
})

const filters = ref({
    search: '',
    role: '',
    sort: 'newest',
})

const showDeleteModal = ref(false)
const userToDelete = ref(null)
const openMenuId = ref(null)

const filteredUsers = computed(() => {
    let result = [...props.users]

    if (filters.value.search) {
        const searchLower = filters.value.search.toLowerCase()
        result = result.filter(user =>
            user.name?.toLowerCase().includes(searchLower) ||
            user.email?.toLowerCase().includes(searchLower) ||
            user.login?.toLowerCase().includes(searchLower)
        )
    }

    if (filters.value.role) {
        result = result.filter(user => user.role === filters.value.role)
    }

    switch (filters.value.sort) {
        case 'newest':
            result.sort((a, b) => new Date(b.created_at_raw) - new Date(a.created_at_raw))
            break
        case 'oldest':
            result.sort((a, b) => new Date(a.created_at_raw) - new Date(b.created_at_raw))
            break
        case 'name':
            result.sort((a, b) => (a.name || '').localeCompare(b.name || ''))
            break
        case 'last_login':
            result.sort((a, b) => new Date(b.last_login_at_raw || 0) - new Date(a.last_login_at_raw || 0))
            break
    }

    return result
})

const getRoleBadgeClass = (role) => {
    const classes = {
        super_admin: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 ring-1 ring-red-600/10 dark:ring-red-400/20',
        admin: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-1 ring-amber-600/10 dark:ring-amber-400/20',
        owner: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-600/10 dark:ring-emerald-400/20',
        user: 'bg-gray-50 dark:bg-gray-500/10 text-gray-700 dark:text-gray-400 ring-1 ring-gray-600/10 dark:ring-gray-400/20',
    }
    return classes[role] || classes.user
}

const getRoleLabel = (role) => {
    const labels = {
        super_admin: t('admin.users.roles.super_admin'),
        admin: t('admin.users.roles.admin'),
        owner: t('admin.users.roles.owner'),
        user: t('admin.users.roles.user'),
    }
    return labels[role] || role
}

const toggleMenu = (userId) => {
    openMenuId.value = openMenuId.value === userId ? null : userId
}

const closeMenu = (userId) => {
    if (openMenuId.value === userId) {
        openMenuId.value = null
    }
}

const viewUser = (user) => {
    openMenuId.value = null
    router.visit(`/dashboard/users/${user.id}`)
}

const editUser = (user) => {
    openMenuId.value = null
    router.visit(`/dashboard/users/${user.id}/edit`)
}

const confirmDelete = (user) => {
    openMenuId.value = null
    userToDelete.value = user
    showDeleteModal.value = true
}

const deleteUser = () => {
    if (!userToDelete.value) return

    router.delete(`/dashboard/users/${userToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            userToDelete.value = null
        }
    })
}

// Custom directive for click outside
const vClickOutside = {
    mounted(el, binding) {
        el._clickOutside = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value(event)
            }
        }
        document.addEventListener('click', el._clickOutside)
    },
    unmounted(el) {
        document.removeEventListener('click', el._clickOutside)
    }
}
</script>
