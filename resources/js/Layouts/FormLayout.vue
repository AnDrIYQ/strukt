<script setup>
import { watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const page = usePage();

const props = defineProps({
    modelValue: Object,
    action: String,
    showCommonErrors: {
        type: Boolean,
        default: false,
    },
    method: {
        type: String,
        default: 'post',
    },
    beforeSubmit: {
        type: Function,
        default: (data) => data,
    },
    trimEmpty: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'success'])

const form = useForm({ ...props.modelValue })

watch(
    () => form.data(),
    (val) => emit('update:modelValue', val),
    { deep: true }
)

function clean(data) {
    return Object.fromEntries(
        Object.entries(data).filter(([_, v]) =>
            v !== null &&
            v !== '' &&
            !(v instanceof File && v.size === 0)
        )
    )
}

function submit() {
    form.transform((data) => {
        data = props.beforeSubmit(data);
        return props.trimEmpty
            ? clean(data)
            : data;
        }
    )[props.method](props.action, {
        onSuccess: () => emit('success'),
    });
}
</script>

<template>
    <div class="rounded-lg bg-white shadow-card p-lg w-full sm:w-2/3 md:w-9/10">
        <form
            @submit.prevent="submit"
            autocomplete="off"
            class="flex flex-col gap-md"
            enctype="multipart/form-data"
        >
            <div v-if="$slots.header">
                <slot name="header" />
            </div>

            <div class="space-y-sm">
                <slot :form="form" />
            </div>

            <div v-if="form.hasErrors && showCommonErrors" class="text-md text-danger">
                <div v-for="(messages, field) in form.errors" :key="field">
                    <template v-for="msg of messages">
                        {{ msg }}<br />
                    </template>
                </div>
            </div>
            <div class="text-md text-success" v-if="page.props.flash">{{ page.props.flash.success }}</div>

            <div v-if="$slots.buttons" class="flex flex-col pt-md border-t border-border mt-md">
                <div class="w-1/2 lg:w-1/4 self-end">
                    <slot name="buttons" :form="form" />
                </div>
            </div>
        </form>
    </div>
</template>
