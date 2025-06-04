<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    items: Array,
    columns: Array,
    links: Array,
    actions: {
        type: Boolean,
        default: true,
    },
    search: {
        type: Boolean,
        default: true,
    }
});
defineEmits(['search']);

const selected = ref([])

const sortedBy = ref(null)
const sortDir = ref('asc')

const sorted = computed(() => {
    if (!sortedBy.value) return props.items;

    return [...props.items].sort((a, b) => {
        const v1 = a[sortedBy.value]
        const v2 = b[sortedBy.value]
        if (v1 === v2) return 0
        return sortDir.value === 'asc'
            ? v1 > v2 ? 1 : -1
            : v1 < v2 ? 1 : -1
    })
})

function toggleSort(column) {
    if (sortedBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortedBy.value = column
        sortDir.value = 'asc'
    }
}

function toggleRow(id) {
    if (selected.value.includes(id)) {
        selected.value = selected.value.filter((v) => v !== id)
    } else {
        selected.value.push(id)
    }
}

function clearSelection() {
    selected.value = []
}
</script>

<template>
    <div class="space-y-md min-w-[400px]">
        <div class="flex items-center justify-between gap-4">
            <input
                v-if="search"
                type="text"
                placeholder="Пошук..."
                class="w-full px-3 py-2 text-sm border rounded-md border-border bg-white"
                @input="$emit('search', $event.target.value)"
            />

            <slot name="mass-actions" :selected="selected" :clear="clearSelection" />
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-border">
            <!-- Header -->
            <div :class="[
                `grid-cols-[20px_repeat(${columns.length + 1},_1fr)]`,
                'grid items-center gap-2 p-1 bg-neutral-50 text-xs font-semibold text-muted uppercase border-b border-border'
            ]">
                <div class="w-5 p-3 grid items-center justify-center">
                    <input
                        v-if="actions"
                        type="checkbox"
                        :checked="selected.length === sorted.length"
                        @change="selected = selected.length === sorted.length ? [] : sorted.map(i => i.id)"
                    />
                </div>
                <div
                    v-for="col in columns"
                    :key="col.id"
                    class="cursor-pointer select-none flex items-center gap-1"
                    @click="toggleSort(col.id)"
                >
                    {{ col.header }}
                    <span v-if="sortedBy === col.id">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                </div>
                <div v-if="actions" class="px-2">Дії</div>
            </div>

            <div
                v-for="row in sorted"
                :key="row.id"
                :class="['grid items-center gap-2 p-1 border-t border-border/60 even:bg-neutral-100 hover:bg-neutral-50 transition', `grid-cols-[20px_repeat(${columns.length + 1},_1fr)]`,]"
            >
                <div class="w-5 flex items-center justify-center">
                    <input
                        v-if="actions"
                        type="checkbox"
                        :checked="selected.includes(row.id)"
                        @change="toggleRow(row.id)"
                    />
                </div>
                <div
                    v-for="col in columns"
                    :key="col.id"
                    class="text-sm"
                >
                    <slot :name="`cell-${col.id}`" :value="row[col.id]" :row="row">
                        {{ row[col.id] }}
                    </slot>
                </div>
                <div v-if="actions" class="flex items-center gap-sm text-right">
                    <slot name="actions" :row="row" />
                </div>
            </div>

            <div v-if="!sorted.length" class="text-center py-8 text-muted text-sm">
                Даних не знайдено
            </div>
        </div>

        <div v-if="links && links.length > 3" class="flex justify-center pt-md items-center">
            <nav class="flex items-center gap-1 w-full flex-wrap">
                <template v-for="link in links" :key="link.label">
                    <component
                        :is="link.url ? 'a' : 'span'"
                        class="px-3 flex py-1 text-sm rounded-md cursor-pointer border border-border"
                        :class="{
                            'bg-primary text-white': link.active,
                            'text-muted': !link.url,
                            'hover:bg-primary hover:text-white transition': link.url && !link.active
                        }"
                        v-html="link.label"
                        @click="router.get(link.url, { }, { preserveScroll: true })"
                    />
                </template>
            </nav>
        </div>
    </div>
</template>
