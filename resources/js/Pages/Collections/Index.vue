<script setup>
import { Trash2, icons as IconsList } from 'lucide-vue-next';
import { router, Link } from '@inertiajs/vue3'

import AdminLayout from "@/Layouts/AdminLayout.vue";
import DataTable from "@/Components/DataTable.vue";
import { ref, toRaw } from "vue";
import Button from "@/Components/Button.vue";

function deleteItem(name) {
    if (confirm('Ви впевнені, що хочете видалити цей запис?')) {
        router.delete(`/collections/${name}`, {
            preserveScroll: true,
        })
    }
}

const doSearch = (query) => {
    router.get('/collections', { search: query }, {
        preserveState: true,
        preserveScroll: true
    })
};

defineProps({
    collections: Object,
})

const icons = ref({ ...IconsList });

function massDelete(ids) {
    if (confirm('Ви впевнені, що хочете видалити виділені записи?')) {
        router.post(`/collections/mass-delete`, {
            ids: toRaw(ids),
        }, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <admin-layout>
        <template #title>Колекції</template>
        <template #subtitle>Список колекцій, на яких базується система</template>

        <div class="w-full md:w-2/3 overflow-auto">
            <data-table
                :columns="[
                    { id: 'icon', header: 'Іконка' },
                    { id: 'label', header: 'Назва' },
                    { id: 'schema', header: 'Поля' },
                    { id: 'name', header: 'Ідентифікатор' }
                ]"
                :items="collections.data"
                :links="collections.links"
                @search="doSearch"
            >
                <template #mass-actions="{ selected }">
                    <button
                        v-if="selected.length"
                        class="text-sm text-danger cursor-pointer"
                        @click="massDelete(selected)"
                    >
                        <Trash2 />
                    </button>
                </template>
                <template #cell-icon="{ value }">
                    <component :is="icons[value]" />
                </template>
                <template #cell-schema="{ value }">
                    <div v-html="value.map((item) => item.name).join(', <br />')"></div>
                </template>
                <template #cell-label="{ value, row }">
                    <Link :href="`/collections/${row.name}/endpoints`">{{ value }}</Link>
                </template>
                <template #actions="{ row }">
                    <button class="text-sm text-danger cursor-pointer" @click="deleteItem(row.name)">
                        <Trash2 />
                    </button>
                </template>
            </data-table>
        </div>
    </admin-layout>
</template>
