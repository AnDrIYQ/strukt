<template>
    <a :href="link" class="flex items-center gap-1 text-black">
        <img src="/public/logo.svg" alt="Logo" class="w-15 h-15" />
        <span class="font-semibold text-xl">{{ name }}</span>
    </a>
</template>
<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

defineProps({
    name: {
        type: String,
        default: '',
    }
});

const link = computed(() => {
    if (!page.props.auth.user) {
        return '/';
    }
    return page.props.auth.user.role === 'admin' ? '/' : '/settings';
});
</script>
