<template>
    <div class="space-y-xs flex-grow">
        <label v-if="label" :for="id" class="text-sm font-medium text-muted">{{ label }}</label>
        <div class="relative">
            <select
                :id="id"
                :value="modelValue.value"
                name="fake-name"
                autocomplete="new-name"
                :class="['w-full mb-0 select-field input-field', { 'text-muted': !modelValue.value }]"
                @change="$emit('update:modelValue', {
                    ...options.find(option => option.value === $event.target.value),
                })"
            >
                <slot name="option" :options="options">
                    <option value="" disabled selected>{{ placeholder }}</option>
                    <option
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </slot>
            </select>
            <ChevronDown class="absolute right-2 top-1/5 w-5" />
        </div>
        <slot name="error" />
    </div>
</template>

<script setup>
import { ChevronDown } from 'lucide-vue-next';

defineProps({
    modelValue: {
        type: Object,
        default: () => ({}),
    },
    label: String,
    placeholder: String,
    options: Array,
    id: {
        type: String,
        default: () => `input-${Math.random().toString(36).slice(2)}`
    }
})
defineEmits(['update:modelValue'])
</script>
