<script setup>
import { Trash2, Plus, Edit } from 'lucide-vue-next';
import { router, Link } from '@inertiajs/vue3'

import AdminLayout from "@/Layouts/AdminLayout.vue";
import DataTable from "@/Components/DataTable.vue";
import Button from "@/Components/Button.vue";
import { toRaw } from "vue";

const props = defineProps({
    endpoints: Object,
    collection: Object,
})

const doSearch = (query) => {
    router.get(`/collections/${props.collection.name}/endpoints`, { search: query }, {
        preserveState: true,
        preserveScroll: true
    });
};

function massDelete(ids) {
    if (confirm('Ви впевнені, що хочете видалити виділені записи?')) {
        router.post(`/collections/${props.collection.name}/endpoints/mass-delete`, {
            ids: toRaw(ids),
        }, {
            preserveScroll: true,
        });
    }
}

function editItem(id) {
    router.get(`/collections/${props.collection.name}/endpoints/edit/${id}`);
}

function deleteItem(id) {
    if (confirm('Ви впевнені, що хочете видалити цей запис?')) {
        router.delete(`/collections/${props.collection.name}/endpoints/${id}`, {
            preserveScroll: true,
        })
    }
}
</script>

<template>
    <admin-layout>
        <template #title>Запити {{ collection.singleton ? '(S)' : '' }}</template>
        <template #subtitle>Список запитів колекції "{{ collection.label }}". Сінглтон</template>

        <div class="w-full md:w-2/3 overflow-auto">
            <data-table
                :columns="[
                    { id: 'path', header: 'Шлях' }
                ]"
                :links="endpoints.links"
                :items="endpoints.data"
                @search="doSearch"
            >
                <template #mass-actions="{ selected, clear }">
                    <Link :href="`/collections/${collection.name}/endpoints/create`" class="w-50 items-center gap-sm">
                        <Button class="gap-xs px-1 py-2">
                            <span class="whitespace-nowrap">Створити запит</span>
                            <Plus class="h-5 w-5" />
                        </Button>
                    </Link>
                    <button
                        v-if="selected.length"
                        class="text-sm text-danger cursor-pointer"
                        @click="massDelete(selected, clear)"
                    >
                        <Trash2 />
                    </button>
                </template>
                <template #actions="{ row }">
                    <button class="text-sm text-secondary cursor-pointer" @click="editItem(row.id)">
                        <Edit />
                    </button>
                    <button class="text-sm text-danger cursor-pointer" @click="deleteItem(row.id)">
                        <Trash2 />
                    </button>
                </template>
            </data-table>
        </div>
    </admin-layout>
</template>
