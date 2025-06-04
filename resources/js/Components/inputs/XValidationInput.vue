<script setup>
import { SquareCheckBig } from 'lucide-vue-next';
import usePopover from "@/Composables/popover/popover.js";
import TextInput from "@/Components/inputs/TextInput.vue";
import Button from "@/Components/Button.vue";
import XSelect from "@/Components/inputs/XSelect.vue";
import { ref, computed } from "vue";

const props = defineProps({
    modelValue: String,
    placeholder: String,
    type: Object,
});

const emit = defineEmits([
    'update:modelValue',
]);

const params = ref([
    {
        value: 'required',
        label: 'Обовʼязкове',
        types: ['value', 'file', 'relation']
    },
    {
        value: 'nullable',
        label: 'Може бути порожнім',
        types: ['value', 'file', 'relation']
    },
    {
        value: 'string',
        label: 'Рядок',
        types: ['value']
    },
    {
        value: 'numeric',
        label: 'Число',
        types: ['value']
    },
    {
        value: 'integer',
        label: 'Ціле число',
        types: ['value', 'relation']
    },
    {
        value: 'boolean',
        label: 'Булеве значення',
        types: ['value']
    },
    {
        value: 'min',
        label: 'Мінімум',
        result: (param) => `min:${param}`,
        types: ['value', 'file', 'relation']
    },
    {
        value: 'max',
        label: 'Максимум',
        result: (param) => `max:${param}`,
        types: ['value', 'file', 'relation']
    },
    {
        value: 'between',
        label: 'Між значеннями',
        result: (param) => `between:${param}`,
        types: ['value', 'relation']
    },
    {
        value: 'in',
        label: 'Одне з',
        result: (param) => `in:${param}`,
        types: ['value', 'relation']
    },
    {
        value: 'not_in',
        label: 'Не з',
        result: (param) => `not_in:${param}`,
        types: ['value']
    },
    {
        value: 'regex',
        label: 'Регулярний вираз',
        result: (param) => `regex:${param}`,
        types: ['value']
    },
    {
        value: 'email',
        label: 'Email адреса',
        types: ['value']
    },
    {
        value: 'url',
        label: 'URL адреса',
        types: ['value']
    },
    {
        value: 'date',
        label: 'Дата',
        types: ['value']
    },
    {
        value: 'after',
        label: 'Після дати',
        result: (param) => `after:${param}`,
        types: ['value']
    },
    {
        value: 'before',
        label: 'До дати',
        result: (param) => `before:${param}`,
        types: ['value']
    },
    {
        value: 'file',
        label: 'Файл',
        types: ['file']
    },
    {
        value: 'image',
        label: 'Зображення',
        types: ['file']
    },
    {
        value: 'mimes',
        label: 'Типи файлів (розширення)',
        result: (param) => `mimes:${param}`,
        types: ['file']
    },
    {
        value: 'mimetypes',
        label: 'MIME-типи',
        result: (param) => `mimetypes:${param}`,
        types: ['file']
    },
    {
        value: 'json',
        label: 'JSON формат',
        types: ['value']
    },
    {
        value: 'array',
        label: 'Масив',
        types: ['relation']
    },
]);

const filteredParams = computed(() => {
    if (!props.type || !props.type.value) {
        return [];
    }

    emit('update:modelValue', '');

    return params.value.filter((item) => item.types.includes(props.type.value));
});

const paramOption = ref({});
const parameter = ref('');

const pushParam = () => {
    if (!paramOption.value.value) {
        return false;
    }

    let withParam;
    if (paramOption.value.result) {
        if (!parameter.value) {
            return false;
        }
        const value = paramOption.value.result(parameter.value);
        withParam = `${props.modelValue ? `${props.modelValue}|` : ''}${value}`;
    }
    const withoutParam = `${props.modelValue ? `${props.modelValue}|` : ''}${paramOption.value.value}`;

    if (props.modelValue?.includes(paramOption.value.value)) {
        return false;
    }

    emit('update:modelValue', paramOption.value.result ? withParam : withoutParam);

    parameter.value = '';
    paramOption.value = {};
};

const { opened, toggle } = usePopover();
</script>

<template>
    <div class="relative">
        <div :class="['input-field cursor-pointer text-light flex items-center px-md py-sm gap-1.5 bg-muted transition hover:opacity-90', {
            'rounded-t-none': opened,
        }]"
             @click="toggle"
        >
            <div class="flex items-center h-4 transition hover:text-light">{{ placeholder }}</div>
            <SquareCheckBig class="h-4 w-4 text-light" />
        </div>
        <div class="popover" v-if="opened">
            <div class="flex flex-col gap-2">
                <x-select
                    v-model="paramOption"
                    data-testid="select-rule"
                    class="w-full"
                    placeholder="Обрати правило"
                    :options="filteredParams"
                />
                <div class="flex items-center gap-2">
                    <text-input v-model="parameter" :disabled="!paramOption.result" placeholder="Параметр" />
                    <Button
                        :disabled="!paramOption.value"
                        type="button"
                        @click="pushParam"
                    >Додати +</Button>
                </div>
                <text-input
                    :model-value="modelValue"
                    placeholder="Немає"
                    label="Правила"
                    @update:modelValue="$emit('update:modelValue', $event)"
                />
            </div>
        </div>
    </div>
</template>
