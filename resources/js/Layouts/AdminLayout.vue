<template>
    <div class="min-h-screen flex font-sans bg-neutral-50 text-neutral-900">
        <aside
            :class="[
        'fixed lg:sticky top-0 left-0 h-screen w-72 bg-neutral-800 text-white flex flex-col shadow-xl z-30 transition-transform duration-300',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
        >
            <div class="px-xl pt-xl pb-md flex items-center gap-2 border-b border-neutral-700">
                <Logo />
                <h1 class="text-xl font-semibold tracking-tight">Strukt</h1>
                <button class="ml-auto lg:hidden" @click="sidebarOpen = false">
                    ✕
                </button>
            </div>

            <nav class="flex-1 flex flex-col space-y-sm overflow-y-auto">
                <a
                    v-if="page.props.auth.user.role === 'admin'"
                    href="/" class="flex px-lg items-center sidebar-item gap-3 py-sm mb-0 px-3 hover:bg-neutral-700 transition">
                    <House class="h-5 w-5" /> <span>Головна</span>
                </a>
                <a
                    v-if="page.props.auth.user.role === 'admin'"
                    href="/collections/create" class="flex px-lg items-center sidebar-item gap-3 py-sm mb-0 px-3 hover:bg-neutral-700 transition">
                    <BadgePlus class="h-5 w-5" /> <span>Створити</span>
                </a>
                <div v-if="page.props.auth.user.role === 'admin'" class="border-b border-neutral-700 mb-2">
                    <a
                        href="/collections" class="flex px-lg items-center sidebar-item gap-3 px-3 py-sm hover:bg-neutral-700 transition">
                        <Waypoints class="h-5 w-5" /> <span>Колекції</span>
                    </a>
                </div>
                <div
                    class="mb-0"
                    v-if="page.props.auth.user.role === 'admin' && page.props.collectionsForSidebar.length"
                >
                    <a
                        v-for="collection of page.props.collectionsForSidebar"
                        :key="collection.name"
                        :href="`/collections/${collection.name}/endpoints`" class="flex px-lg items-center sidebar-item gap-3 px-3 py-sm hover:bg-neutral-700 transition"
                    >
                        <component :is="icons[collection.icon]" class="h-5 w-5" />
                        <span v-if="!collection.singleton">{{ collection.label }}</span>
                        <span v-else>{{ collection.label }} (S)</span>
                    </a>
                </div>
                <a
                    v-if="page.props.auth.user.role === 'admin'"
                    href="/translations" :class="[{ 'border-t border-neutral-700': page.props.collectionsForSidebar.length },
                         'flex px-lg items-center sidebar-item gap-3 mb-0 mt-2 px-3 py-sm hover:bg-neutral-700 transition'
                    ]">
                    <Languages class="h-5 w-5" /> <span>Переклади</span>
                </a>
                <a
                    href="/settings" class="flex px-lg items-center sidebar-item gap-3 px-3 py-sm hover:bg-neutral-700 transition">
                    <UserPen class="h-5 w-5" /> <span>Налаштування</span>
                </a>
                <a
                    v-if="page.props.auth.user.role === 'admin'"
                    href="/sandbox" class="flex px-lg items-center sidebar-item gap-3 mb-0 px-3 py-sm bg-neutral-900 transition">
                    <Codesandbox class="h-5 w-5" /> <span>SandBox</span>
                </a>
            </nav>

            <div class="px-lg pb-lg flex justify-between w-full">
                <a href="/settings" class="flex gap-sm items-center cursor-pointer text-white">
                    <img
                        v-if="profileImage"
                        class="w-10 h-10 object-cover rounded-full shadow border-white border-1"
                        :src="`/user/avatar?ts=${page.props.auth.user.updated_at}`"
                        alt=""
                        @error="profileImage = null"
                    />
                    <span>{{ page.props.auth.user.name }}</span>
                </a>
                <button @click="router.post('/logout')" class="w-1/5 cursor-pointer flex items-center justify-center gap-2 text-sm text-white bg-neutral-700 hover:bg-neutral-600 px-sm py-sm rounded-md transition">
                    <LogOut class="w-5 h-5" />
                    <span class="sr-only">Вийти</span>
                </button>
            </div>
        </aside>

        <main class="flex-1 px-xl py-xl space-y-lg overflow-y-auto relative">
            <button class="lg:hidden top-md cursor-pointer left-md z-20 bg-white/80 backdrop-blur px-md py-sm rounded shadow-card" @click="sidebarOpen = true">
                <Menu />
            </button>

            <header class="mb-lg border-b border-border pb-md">
                <h2 class="text-3xl font-bold tracking-tight mb-1">
                    <slot name="title"></slot>
                </h2>
                <p class="text-sm text-muted">
                    <slot name="subtitle"></slot>
                </p>
            </header>

            <slot />
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { UserPen, Menu, LogOut, Waypoints, BadgePlus, House, Languages, Codesandbox } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import { usePage } from '@inertiajs/vue3';
import { icons as IconsList } from 'lucide-vue-next';

const page = usePage();
const icons = ref({ ...IconsList });

const sidebarOpen = ref(false)
const profileImage = ref(true);
</script>
