<template>
    <div class="min-h-screen flex items-center justify-center bg-dark text-neutral-900 font-sans">
        <div class="w-18/20 sm:w-14/20 md:w-12/20 lg:w-7/20 xl:w-5/20 p-lg bg-white rounded-xl shadow-card space-y-md">
            <div class="flex flex-col items-center text-center space-y-sm">
                <logo name="Strukt" />
                <h1 class="text-2xl font-semibold">
                    <slot name="title" />
                </h1>
                <p class="text-sm text-muted">
                    <slot name="subtitle" />
                </p>
            </div>

            <slot />

            <footer class="text-center pt-md border-t border-border text-xs text-muted">
                &copy; {{ new Date().getFullYear() }} Strukt. Усі права захищені.
            </footer>
        </div>
    </div>
</template>
<script setup>
import Logo from "@/Components/Logo.vue";
</script>
