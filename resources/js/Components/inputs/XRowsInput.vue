<script setup>
import { ref, watch, toRaw } from 'vue';
import { ArrowDown, X, ArrowUp } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    columns: {
        type: Array,
        required: true
    },
    label: String,
})

const emit = defineEmits(['update:modelValue'])

const rows = ref(props.modelValue.length ? [...props.modelValue] : [{}])

watch(
    () => props.modelValue,
    (newVal) => {
        if (JSON.stringify(newVal) !== JSON.stringify(rows.value)) {
            rows.value = newVal.length ? [...newVal] : [{}]
        }
    },
    { deep: true }
)

watch(
    rows,
    () => emit('update:modelValue', toRaw(rows.value)),
    { deep: true }
)

function addRow() {
    rows.value.push({})
}

function removeRow(index) {
    rows.value.splice(index, 1)
    if (rows.value.length === 0) {
        rows.value.push({})
    }
}

function moveUp(index) {
    if (index > 0) {
        const temp = rows.value[index - 1]
        rows.value[index - 1] = rows.value[index]
        rows.value[index] = temp
    }
}

function moveDown(index) {
    if (index < rows.value.length - 1) {
        const temp = rows.value[index + 1]
        rows.value[index + 1] = rows.value[index]
        rows.value[index] = temp
    }
}
</script>

<template>
    <label v-if="label" class="text-sm font-medium text-muted">{{ label }}</label>
    <div class="flex flex-col p-sm">
        <div class="mb-sm"><slot name="error" /></div>
        <div
            v-for="(row, index) in rows"
            :key="index"
            data-testid="row"
            class="flex gap-sm mb-sm items-start flex-col sm:flex-row"
        >
            <div class="flex items-center gap-sm mt-2.5">
                <button
                    type="button"
                    @click="removeRow(index)"
                    :data-testid="`remove-row-${index}`"
                    class="text-danger cursor-pointer hover:text-red-600"
                >
                    <X />
                </button>
                <button
                    type="button"
                    @click="moveUp(index)"
                    :data-testid="`move-up-${index}`"
                    class="text-xs cursor-pointer text-muted hover:text-primary"
                >
                    <ArrowUp />
                </button>
                <button
                    type="button"
                    @click="moveDown(index)"
                    :data-testid="`move-down-${index}`"
                    class="text-xs cursor-pointer text-muted hover:text-primary"
                >
                    <ArrowDown />
                </button>
            </div>
            <div class="flex items-center gap-sm flex-wrap">
                <div v-for="(col, colIndex) in columns" class="flex">
                    <div class="flex flex-col">
                        <slot
                            :name="col"
                            :row="row"
                            :index="index"
                            :key="col"
                            v-bind="{ modelValue: row[col], 'onUpdate:modelValue': val => row[col] = val }"
                        />
                        <slot name="row-error" :row="index" :column="col" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div>
        <button
            type="button"
            @click="addRow"
            data-testid="add-row"
            class="text-sm text-primary font-medium hover:font-bold cursor-pointer"
        >
            + Додати рядок
        </button>
    </div>
</template>

<style scoped>
.grid {
    display: grid;
    gap: 0.5rem;
}
</style>
