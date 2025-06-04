<template>
    <div class="space-y-xs flex-grow">
        <label v-if="label" :for="id" class="text-sm font-medium text-muted">{{ label }}</label>
        <div class="relative">
            <input
                :id="id"
                :type="show ? 'text' : 'password'"
                :value="modelValue"
                name="fake-password"
                autocomplete="new-password"
                @input="$emit('update:modelValue', $event.target.value)"
                :placeholder="placeholder"
                class="w-full px-md py-sm rounded-md border border-border bg-white text-base text-dark shadow-sm focus:outline-none focus:ring-2 focus:ring-primary transition pr-10"
            />
            <button type="button" @click="show = !show" class="absolute right-sm top-1/2 -translate-y-1/2 text-muted hover:text-dark transition">
                <span v-if="show"><EyeOff /></span>
                <span v-else><Eye /></span>
            </button>
        </div>
        <slot name="error" />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Eye, EyeOff } from 'lucide-vue-next';

defineProps({
    modelValue: String,
    label: String,
    placeholder: String,
    id: {
        type: String,
        default: () => `password-${Math.random().toString(36).slice(2)}`
    }
})
defineEmits(['update:modelValue'])

const show = ref(false)
</script>
