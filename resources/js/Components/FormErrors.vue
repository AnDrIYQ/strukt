<template>
  <span
      v-for="(error, key) in filteredErrors"
      :key="key"
      class="text-danger text-sm"
  >
    {{ error }}.<br />
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    items: {
        type: Object,
        default: () => ({}),
    },
    field: String,
    index: Number,
    subfield: String,
});

const targetKey = computed(() => {
    if (props.index !== undefined && props.subfield) {
        return `${props.field}.${props.index}.${props.subfield}`;
    }
    if (props.index !== undefined) {
        return `${props.field}.${props.index}`;
    }
    return props.field;
});

const filteredErrors = computed(() => {
    return props.items?.[targetKey.value] || [];
});
</script>
