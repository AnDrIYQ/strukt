<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FormLayout from '@/Layouts/FormLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Button from '@/Components/Button.vue';
import TextInput from '@/Components/inputs/TextInput.vue';
import { Edit, Trash2, Plus } from 'lucide-vue-next';

const props = defineProps({
    translations: Object,
});

const search = ref('');

const formData = ref({
    key: '',
    message: ''
});

const beforeSubmit = (data) => {
    return {
        key: data.key,
        message: data.message,
    };
};

function updateItem(id, value) {
    router.put(`/translations/${id}`, { message: value }, {
        preserveScroll: true,
    });
}

function deleteItem(id) {
    if (confirm('Ви впевнені, що хочете видалити цей переклад?')) {
        router.delete(`/translations/${id}`);
    }
}

function doSearch(q) {
    search.value = q;
    router.get('/translations', { search: q }, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <admin-layout>
        <template #title>Переклади</template>
        <template #subtitle>Переклади повідомлень від API</template>

        <form-layout
            :model-value="formData"
            :action="'/translations'"
            method="post"
            :before-submit="beforeSubmit"
            :show-common-errors="true"
        >
            <template #header>
                <p class="text-md font-bold text-accent">Новий ключ</p>
            </template>

            <template #default="{ form }">
                <div class="flex flex-col gap-sm">
                    <text-input v-model="form.key" placeholder="Ключ (наприклад: validation.required)" />
                    <text-input v-model="form.message" placeholder="Переклад" />
                </div>
            </template>

            <template #buttons="{ form }">
                <Button type="submit" class="w-full gap-2" :disabled="form.processing">
                    <span>Додати</span>
                    <Plus class="h-5 w-5" />
                </Button>
            </template>
        </form-layout>

        <div class="overflow-auto">
            <data-table
                :items="translations.data"
                :columns="[
                { id: 'key', header: 'Ключ' },
                { id: 'message', header: 'Переклад' }
            ]"
                :links="translations.links"
                @search="doSearch"
            >
                <template #cell-message="{ row }">
                    <input
                        type="text"
                        :value="row.message"
                        class="w-full bg-white px-2 py-1 text-sm border rounded-md border-border"
                        @change="e => updateItem(row.id, e.target.value)"
                    />
                </template>

                <template #actions="{ row }">
                    <button
                        class="text-sm text-danger cursor-pointer"
                        @click="deleteItem(row.id)"
                    >
                        <Trash2 />
                    </button>
                </template>
            </data-table>
        </div>
    </admin-layout>
</template>
