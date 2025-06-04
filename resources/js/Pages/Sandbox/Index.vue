<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import SBAuth from "@/Components/sandbox/SBAuth.vue";
import SBConfig from "@/Components/sandbox/SBConfig.vue";
import useSandbox from "@/Composables/sandbox.js";
import SBPublic from "@/Components/sandbox/SBPublic.vue";
import SBCollections from "@/Components/sandbox/SBCollections.vue";
import { nextTick, onMounted, ref, watch } from 'vue';

const { me, checkMe } = useSandbox();

const renderCollections = ref(false);

onMounted(() => {
    checkMe();
});

watch(() => me, () => {
    renderCollections.value = false;
    nextTick(() => {
        renderCollections.value = true;
    })
}, { deep: true });
</script>

<template>
    <admin-layout>
        <template #title>SandBox</template>
        <template #subtitle>Тут можна тестувати функціональність API</template>

        <div>
            <p class="text-md font-bold text-accent mb-2">Авторизація</p>
            <s-b-auth />
        </div>

        <div class="mb-lg border-b border-border pb-md">
            <p class="text-md font-bold text-accent mb-2">Колекції</p>
            <s-b-collections v-if="renderCollections" />
        </div>

        <div class="flex gap-4">
            <div v-if="me" class="w-full">
                <p class="text-md font-bold text-accent mb-2">Конфігурація</p>
                <s-b-config class="w-full" />
            </div>
            <div class="w-full">
                <p class="text-md font-bold text-accent mb-2">Видимі користувачі</p>
                <s-b-public class="w-full" />
            </div>
        </div>
    </admin-layout>
</template>
