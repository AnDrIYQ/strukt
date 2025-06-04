<script setup>
import DataTable from "@/Components/DataTable.vue";
import useSandbox from "@/Composables/sandbox.js";
import { watch, ref } from "vue";
import XPreloader from "@/Components/XPreloader.vue";

const users = ref([]);
const loading = ref(true);

const { me, strukt } = useSandbox();
watch(() => me, async () => {
    users.value = await strukt.getPublicUsers();
    loading.value = false;
}, { immediate: true });
</script>

<template>
    <div class="relative min-h-40 mb-lg border-b border-border pb-md">
        <x-preloader :loading="loading">
            <data-table
                :search="false"
                :actions="false"
                :columns="[
                    { id: 'id', header: 'ІD' },
                    { id: 'name', header: 'Ім\'я' },
                    { id: 'email', header: 'Email' },
                    { id: 'avatar', header: 'Зображення' },
                ]"
                :items="users.data || []"
            >
                <template #cell-avatar="{ value }">
                    {{ !value ? 'Немає' : '' }}
                    <img v-if="value" :src="value" class="w-10 h-10 object-cover cursor-pointer rounded-full" alt="">
                </template>
            </data-table>
        </x-preloader>
    </div>
</template>
