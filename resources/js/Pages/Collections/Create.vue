<template>
    <admin-layout>
        <template #title>
            Створити колекцію
        </template>
        <template #subtitle>На основі колекцій, функціонує система</template>

        <form-layout
            v-model="formData"
            :show-common-errors="false"
            :before-submit="beforeSubmitCallback"
            action="/collections"
            method="post"
        >
            <template #header>
                <p class="text-md font-bold text-accent">Введіть дані нової колекції</p>
            </template>

            <template #default="{ form }">
                <text-input label="Назва" v-model="form.label" placeholder="Введіть назву колекції">
                    <template #error>
                        <form-errors :items="form.errors" field="label" />
                    </template>
                </text-input>
                <text-input label="Ідентифікатор" v-model="form.name" placeholder="Введіть ідентифікатор колекції">
                    <template #error>
                        <form-errors :items="form.errors" field="name" />
                    </template>
                </text-input>
                <div class="flex items-center gap-sm">
                    <x-select :options="iconsOptions" v-model="form.icon" label="Іконка">
                        <template #error>
                            <div v-if="form.icon.value" class="w-10 h-10 mt-2">
                                <component class="w-full h-full" :is="loadAsyncIcon(form.icon.value)" />
                            </div>
                            <a class="text-xs" href="https://lucide.dev/icons/" target="_blank">Сайт з набором іконок</a>
                        </template>
                    </x-select>
                </div>
                <checkbox-input class="my-sm" label="Сінглтон" v-model="form.singleton">
                    <template #error>
                        <div class="flex items-center gap-1 mt-2 text-secondary">
                            <Info class="h-4 w-4" />
                            <span class="text-secondary text-xs">Якщо обрано цю опцію, колекція буде здатна міститити лише один запис</span>
                        </div>
                    </template>
                </checkbox-input>
                <x-rows-input label="Схема" v-model="form.schema" :columns="['title', 'name', 'type', 'rules', 'multiple']">
                    <template #row-error="{ row, column }">
                        <form-errors field="schema" :items="form.errors" :index="row" :subfield="column" />
                    </template>
                    <template #error>
                        <form-errors :items="form.errors" field="schema" />
                    </template>

                    <template #title="{ modelValue, 'onUpdate:modelValue': update }">
                        <text-input :model-value="modelValue" placeholder="Назва" @update:modelValue="update" />
                    </template>
                    <template #name="{ modelValue, 'onUpdate:modelValue': update }">
                        <text-input :model-value="modelValue" placeholder="Поле" @update:modelValue="update" />
                    </template>
                    <template #type="{ modelValue, row, index, 'onUpdate:modelValue': update }">
                        <div class="relative">
                            <x-select placeholder="Тип" :model-value="modelValue" :options="typesOptions" @update:modelValue="update" />
                            <div v-if="modelValue && modelValue.value === 'relation' && !row.collection" class="popover">
                                <checkbox-input
                                    :model-value="row.collection && row.collection.value === -1"
                                    label="Користувач"
                                    @update:modelValue="row.collection = $event ? { value: -1, label: 'Користувач' } : null"
                                />
                                <x-select
                                    v-if="!row.user_relation"
                                    v-model="row.collection"
                                    label="Відношення до колекції"
                                    :options="relatedCollectionsOptions"
                                    placeholder="Обрати"
                                >
                                    <template #error>
                                        <form-errors :items="form.errors" field="schema" :index="index" subfield="collection" />
                                    </template>
                                </x-select>
                            </div>
                        </div>
                    </template>
                    <template #multiple="{ modelValue, 'onUpdate:modelValue': update }">
                        <checkbox-input class="mx-2" :model-value="modelValue" label="Багатозначне" @update:modelValue="update" />
                    </template>
                    <template #rules="{ modelValue, row, 'onUpdate:modelValue': update }">
                        <x-validation-input :type="row.type" :model-value="modelValue" placeholder="Правила" @update:modelValue="update" />
                    </template>
                </x-rows-input>
            </template>

            <template #buttons="{ form }">
                <Button type="submit" :disabled="form.processing" class="w-full gap-2">
                    <span>Створити</span>
                    <Plus class="h-5 w-5" />
                </Button>
            </template>
        </form-layout>
    </admin-layout>
</template>

<script setup>
import { Plus } from 'lucide-vue-next';
import AdminLayout from "@/Layouts/AdminLayout.vue";
import FormLayout from "@/Layouts/FormLayout.vue";
import Button from "@/Components/Button.vue";
import { ref, computed } from "vue";
import TextInput from "@/Components/inputs/TextInput.vue";
import CheckboxInput from "@/Components/inputs/CheckboxInput.vue";
import FormErrors from "@/Components/FormErrors.vue";
import XRowsInput from "@/Components/inputs/XRowsInput.vue";
import XValidationInput from "@/Components/inputs/XValidationInput.vue";
import XSelect from "@/Components/inputs/XSelect.vue";

import { icons as IconsList, Info } from 'lucide-vue-next';
import useCollections from "@/Composables/collections.js";

const { options: relatedCollectionsOptions } = useCollections();

const icons = ref({ ...IconsList });
const iconsOptions = computed(() => {
    return Object.keys(icons.value).map((iconComponent) => ({
        label: iconComponent,
        value: iconComponent,
    }));
});

const typesOptions = ref([
    { label: 'Значення', value: 'value' },
    { label: 'Файл', value: 'file' },
    { label: 'Посилання', value: 'relation' },
]);

const loadAsyncIcon = (name) => {
    return icons.value[name];
};

const formData = ref({
    name: '',
    label: '',
    icon: { "label": "Boxes", "value": "Boxes" },
    singleton: false,
    schema: [],
});

const beforeSubmitCallback = (data) => {
    data.icon = data.icon.value;
    data.schema = data.schema.map((item) => ({
        ...item,
        type: item.type?.value,
        collection: item.collection?.value
    }));

    return data;
};
</script>
