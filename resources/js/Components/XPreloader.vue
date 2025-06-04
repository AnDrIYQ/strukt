<script setup>
defineProps({
    loading: Boolean,
});
</script>

<template>
    <div v-if="loading" class="flex w-full min-h-40 items-center justify-center p-10 absolute bg-white z-100">
        <div class="w-10 h-10 border-3 border-primary border-t-transparent rounded-full animate-spin"></div>
    </div>
    <slot v-else />
</template>
