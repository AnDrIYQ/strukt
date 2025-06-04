<template>
    <div class="space-y-xs flex-grow">
        <label v-if="label" :for="id" class="text-sm font-medium text-muted">{{ label }}</label>
        <input
            :id="id"
            :type="type"
            :value="modelValue"
            name="fake-name"
            :disabled="disabled"
            autocomplete="new-name"
            @input="$emit('update:modelValue', $event.target.value)"
            :placeholder="placeholder"
            :class="['w-full mb-0 input-field', { 'bg-neutral-200': disabled }]"
        />
        <slot name="error" />
    </div>
</template>

<script setup>
defineProps({
    modelValue: String,
    label: String,
    placeholder: String,
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
