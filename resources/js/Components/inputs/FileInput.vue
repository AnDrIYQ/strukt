<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    modelValue: File,
    label: String,
    accept: String,
    existing: String,
});

const emit = defineEmits(['update:modelValue']);

const fileName = ref('');
const previewUrl = ref(null);

onMounted(() => {
    previewUrl.value = props.existing;
});

const onFileChange = (e) => {
    const file = e.target.files[0]
    if (!file) return

    fileName.value = file.name
    emit('update:modelValue', file)

    if (file.type.startsWith('image/')) {
        previewUrl.value = URL.createObjectURL(file)
    } else {
        previewUrl.value = null
    }
};

const onError = () => {
    previewUrl.value = '';
};
</script>

<template>
    <div class="flex flex-col gap-2">
        <label v-if="label" class="text-sm font-medium text-muted">{{ label }}</label>

        <div class="relative border-2 border-dashed border-border rounded-lg p-4 bg-white hover:bg-neutral-50 transition cursor-pointer group">
            <input
                type="file"
                class="absolute inset-0 opacity-0 cursor-pointer"
                @change="onFileChange"
                :accept="accept"
            />

            <div class="flex flex-col items-center justify-center text-center pointer-events-none">
                <div v-if="previewUrl" class="mb-2">
                    <img alt="" :src="previewUrl" class="w-20 h-20 object-cover rounded-full shadow" @error="onError" />
                </div>

                <p class="text-sm text-muted">
                    {{ modelValue?.name || 'Натисніть або перетягніть файл сюди' }}
                </p>
            </div>
        </div>
        <slot name="error" />
    </div>
</template>
