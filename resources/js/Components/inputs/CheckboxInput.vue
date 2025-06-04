<template>
    <div class="flex flex-col space-y-xs flex-grow">
        <label v-if="label" :for="id" class="text-sm font-medium text-muted">{{ label }}</label>
        <label :class="['form-switch', { checked: modelValue }]">
            <input
                :id="id"
                type="checkbox"
                :checked="modelValue"
                name="fake-name"
                :disabled="disabled"
                autocomplete="new-name"
                @change="$emit('update:modelValue', $event.target.checked)"
                :class="['mb-0', { '': disabled }]"
            />
            <span class="form-switch-thumb"></span>
        </label>
        <slot name="error" />
    </div>
</template>

<script setup>
defineProps({
    modelValue: Boolean,
    label: String,
    disabled: Boolean,
    type: {
        type: String,
        default: 'text'
    },
    id: {
        type: String,
        default: () => `input-${Math.random().toString(36).slice(2)}`
    }
})
defineEmits(['update:modelValue'])
</script>
