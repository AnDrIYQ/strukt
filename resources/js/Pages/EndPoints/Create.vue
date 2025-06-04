<script setup>
import Button from "@/Components/Button.vue";
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
    collection: Object,
    fields: Object,
});

import AdminLayout from "@/Layouts/AdminLayout.vue";
import FormLayout from "@/Layouts/FormLayout.vue";
import { ref, computed } from "vue";
import { Plus } from "lucide-vue-next";
import TextInput from "@/Components/inputs/TextInput.vue";
import XSelect from "@/Components/inputs/XSelect.vue";
import CheckboxInput from "@/Components/inputs/CheckboxInput.vue";

// Data
const formData = ref({
    path: '',
    type: { value: 'read', label: 'Читання' },
    role: [],
    own_only: true,
    fields: [],
    trigger_event: false,
});

const typeOptions = computed(() => {
    return [
        { value: 'create', label: 'Створення' },
        { value: 'read', label: 'Читання' },
        { value: 'update', label: 'Оновлення' },
        { value: 'delete', label: 'Видалення' },
        { value: 'search', label: 'Пошук' },
    ].filter((item) => ['create', 'search'].includes(item.value) ? !props.collection.singleton : true);
});

const rolesOption = ref({
    admin: false,
    public: false,
    user: false,
});

const fieldOption = ref([
    ...Object.entries(props.fields).map(([name, label]) => ({
        name,
        label,
        value: true,
    })),
]);

const isShowFieldsConfig = (type) => [
    'read',
    'update',
    'search',
].includes(type);

// Callbacks
const beforeSubmitCallback = (data) => {
    if (!data.fields.length) {
        delete data.fields;
    }
    data.type = data.type.value;
    data.role = Object.keys(rolesOption.value).filter((role) => !!rolesOption.value[role]);
    data.fields = fieldOption.value
        .filter((f) => f.value)
        .map((f) => f.name);
    if (!isShowFieldsConfig(data.type)) {
        delete data.fields;
    }
    if (data.role.includes('public')) {
        data.own_only = false;
    }
    return data;
};
</script>

<template>
    <admin-layout>
        <template #title>
            <div class="flex items-center gap-sm">
                <div class="w-10">
                    <Link :href="`/collections/${collection.name}/endpoints`">
                        <Button>
                            <ArrowLeft />
                        </Button>
                    </Link>
                </div>
                <span>Створити запит</span>
            </div>
        </template>
        <template #subtitle>
            За допомогою запитів, можна виконувати операції над колекціями
        </template>

        <form-layout
            :model-value="formData"
            :action="`/collections/${collection.name}/endpoints`"
            method="post"
            :show-common-errors="true"
            :before-submit="beforeSubmitCallback"
        >
            <template #header>
                <p class="text-md font-bold text-accent">Створення нового запиту для колекції "{{ collection.label }}"</p>
            </template>

            <template #default="{ form }">
                <div class="flex items-center gap-sm">
                    <text-input v-model="form.path" label="Шлях" placeholder="Введіть шлях запиту" />
                    <x-select
                        v-model="form.type"
                        label="Тип"
                        placeholder="Обрати"
                        :options="typeOptions"
                    />
                </div>
                <label class="text-sm font-medium text-primary mb-4">Доступний: </label>
                <div class="flex items-center gap-lg flex-grow-0 mt-2">
                    <checkbox-input class="grow-0" label="Адміністратору" v-model="rolesOption.admin" />
                    <checkbox-input class="grow-0" label="Користувачам" v-model="rolesOption.user" />
                    <checkbox-input class="grow-0" label="Публічний" v-model="rolesOption.public" />
                </div>
                <template v-if="isShowFieldsConfig(form.type.value)">
                    <label class="text-sm font-medium text-primary mb-4">Дозволені поля: </label>
                    <div class="flex items-center gap-lg flex-grow-0 mt-2">
                        <checkbox-input
                            v-for="option of fieldOption"
                            v-model="option.value"
                            class="grow-0"
                            :label="option.label"
                        />
                    </div>
                </template>
                <checkbox-input
                    v-if="form.type.value !== 'create' && !rolesOption.public"
                    class="grow-0 mt-4"
                    label="Доступ лише до власних записів"
                    v-model="form.own_only"
                />
                <checkbox-input
                    v-if="!['read', 'search'].includes(form.type?.value || 'read')"
                    v-model="form.trigger_event"
                    class="grow-0 mt-4"
                    label="Створювати веб-сокет подію"
                />
            </template>

            <template #buttons="{ form }">
                <div class="flex gap-lg">
                    <div class="flex items-center gap-sm">
                        <Link class="text-muted" :href="`/collections/${collection.name}/endpoints`">
                            Назад
                        </Link>
                    </div>
                    <Button type="submit" :disabled="form.processing" class="w-full gap-2">
                        <span>Створити</span>
                        <Plus class="h-5 w-5" />
                    </Button>
                </div>
            </template>
        </form-layout>
    </admin-layout>
</template>
