<template>
    <button
        type="submit"
        :class="['btn', { 'disabled': disabled }]"
    >
        <slot />
    </button>
</template>

<script setup>
defineProps(['disabled']);
</script>
