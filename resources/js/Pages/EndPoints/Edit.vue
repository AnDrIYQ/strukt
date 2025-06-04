<script setup>
import Button from "@/Components/Button.vue";
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import AdminLayout from "@/Layouts/AdminLayout.vue";
import FormLayout from "@/Layouts/FormLayout.vue";
import {computed, ref} from "vue";
import TextInput from "@/Components/inputs/TextInput.vue";
import XSelect from "@/Components/inputs/XSelect.vue";
import CheckboxInput from "@/Components/inputs/CheckboxInput.vue";

const props = defineProps({
    collection: Object,
    fields: Object,
    endpoint: Object,
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

function resolveType(value) {
    return typeOptions.value.find((opt) => opt.value === value) ?? { value, label: value };
}

const formData = ref({
    path: props.endpoint.path,
    type: resolveType(props.endpoint.type),
    role: props.endpoint.role || [],
    own_only: props.endpoint.own_only,
    trigger_event: props.endpoint.trigger_event,
    fields: props.endpoint.fields ?? [],
});

const rolesOption = ref({
    admin: formData.value.role.includes('admin'),
    public: formData.value.role.includes('public'),
    user: formData.value.role.includes('user'),
});

const fieldOption = ref(
    Object.entries(props.fields).map(([name, label]) => ({
        name,
        label,
        value: formData.value.fields?.includes(name),
    }))
);

const isShowFieldsConfig = (type) => ['read', 'update', 'search'].includes(type);

const beforeSubmitCallback = (data) => {
    data.type = data.type.value;
    data.role = Object.keys(rolesOption.value).filter((r) => rolesOption.value[r]);
    data.fields = isShowFieldsConfig(data.type)
        ? fieldOption.value.filter(f => f.value).map(f => f.name)
        : undefined;

    if (!data.fields?.length) delete data.fields;

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
                <span>Редагувати запит</span>
            </div>
        </template>
        <template #subtitle>
            Внесення змін до запиту колекції "{{ collection.label }}"
        </template>

        <form-layout
            :model-value="formData"
            :action="`/collections/${collection.name}/endpoints/${endpoint.id}`"
            method="put"
            :show-common-errors="true"
            :before-submit="beforeSubmitCallback"
        >
            <template #header>
                <p class="text-md font-bold text-accent">Редагування запиту "{{ endpoint.path }}"</p>
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

                <label class="text-sm font-medium text-primary mb-4">Доступний:</label>
                <div class="flex items-center gap-lg flex-grow-0 mt-2">
                    <checkbox-input class="grow-0" label="Адміністратор" v-model="rolesOption.admin" />
                    <checkbox-input class="grow-0" label="Користувачі" v-model="rolesOption.user" />
                    <checkbox-input class="grow-0" label="Публічний" v-model="rolesOption.public" />
                </div>

                <template v-if="isShowFieldsConfig(form.type.value)">
                    <label class="text-sm font-medium text-primary mb-4">Дозволені поля:</label>
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
                    class="grow-0 mt-4"
                    label="Створювати веб-сокет подію"
                    v-model="form.trigger_event"
                />
            </template>

            <template #buttons="{ form }">
                <div class="flex gap-lg items-center">
                    <Link class="text-muted" :href="`/collections/${collection.name}/endpoints`">Назад</Link>
                    <Button type="submit" :disabled="form.processing" class="w-full gap-2">
                        <span>Зберегти</span>
                        <Save class="h-5 w-5" />
                    </Button>
                </div>
            </template>
        </form-layout>
    </admin-layout>
</template>
