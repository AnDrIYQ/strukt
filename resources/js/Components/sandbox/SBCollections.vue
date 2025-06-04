<script setup>
import useSandbox from "@/Composables/sandbox.js";
import { computed, nextTick, onMounted, ref, watch } from "vue";

import TextInput from "@/Components/inputs/TextInput.vue";
import FileInput from "@/Components/inputs/FileInput.vue";
import XSelect from "@/Components/inputs/XSelect.vue";
import XPreloader from "@/Components/XPreloader.vue";
import Button from "@/Components/Button.vue";
import FormErrors from "@/Components/FormErrors.vue";

const { strukt } = useSandbox();

const structure = ref([]);
const loading = ref(true);
const itemsLoading = ref(true);
const fetchedItems = ref([]);

const collection = ref({});
const endpoint = ref({});

const selectedCollection = computed(() => structure.value.find((item) => collection.value.value === item.name));
const actualEndpoints = computed(() => selectedCollection.value?.endpoints || []);

const selectedEndpoint = computed(() => actualEndpoints?.value.find((item) => item.path === endpoint.value.value) || []);
const collectionsOptions = computed(() => structure.value.map((item) => ({ label: item.label, value: item.name })));
const actualEndpointsOptions = computed(() => actualEndpoints.value.map((item) => ({ label: item.path, value: item.path })));

const getFieldSchema = (field) => {
    return selectedCollection.value.schema.find((schemaFiled) => schemaFiled.name === field);
};
const getComponentByType = (type) => {
    switch(type) {
        case 'value': return TextInput;
        case 'file': return FileInput;
        case 'relation': return TextInput;
    }
};

const createFormData = ref({});

const searchFieldQuery = ref({});

const resetAction = () => {
    strukt.unsubscribe(collection.value.value);

    collection.value = { value: null };
    endpoint.value = { value: null };
    createUpdateErrors.value = {};
    createFormData.value = {};
    idsToUpdate.value = '';
};

const socketEventJSON = ref('');
watch(collection, (value) => {
    strukt.subscribe(value.value, (event) => {
        socketEventJSON.value = JSON.stringify(event, null, 2);
    });
});

const doSearch = async () => {
    itemsLoading.value = true;

    let filters = { filters: { ...searchFieldQuery.value,  }, populate: selectedEndpoint.value.fields.join(','), per_page: 15 };

    if (selectedCollection.value.singleton) {
        const item = await strukt.call(collection.value.value, endpoint.value.value, filters);
        fetchedItems.value = [item];
    } else {
        const response = await strukt.call(collection.value.value, endpoint.value.value, filters);
        fetchedItems.value = response.data;
    }

    nextTick(() => {
        itemsLoading.value = false;
    });
};

watch(endpoint, async () => {
    if (['read', 'search'].includes(selectedEndpoint.value.type)) {
        if (selectedCollection.value.singleton) {
            const item = await strukt.call(collection.value.value, endpoint.value.value, { per_page: 15, populate: selectedEndpoint.value.fields.join(',') });
            fetchedItems.value = [item];
        } else {
            const response = await strukt.call(collection.value.value, endpoint.value.value, {
                per_page: 15,
                populate: selectedEndpoint.value.fields.join(','),
            });
            fetchedItems.value = response.data;
        }
        itemsLoading.value = false;
    }
    if (['create', 'update'].includes(selectedEndpoint.value.type)) {
        createFormData.value = selectedEndpoint.value.fields.reduce((accumulator, currentField) => {
            accumulator[currentField] = '';
            if (getFieldSchema(currentField).type === 'file') {
                accumulator[currentField] = null;
            }
            if (getFieldSchema(currentField).multiple) {
                accumulator[currentField] = [null];
            }
            return accumulator;
        }, {});
    }
});

const createUpdateErrors = ref([]);
const doCreateUpdate = async () => {
    try {
        let data = {};
        if (selectedEndpoint.value.type === 'update' && !selectedCollection.value.singleton) {
            data = { ids: [idsToUpdate.value], data: { [idsToUpdate.value.split(',')]: { ...createFormData.value } } };
        } else {
            data = { ...createFormData.value };
        }
        const response = await strukt.call(collection.value.value, endpoint.value.value, data);
        if (response.errors) {
            createUpdateErrors.value = response.errors;
            return false;
        }
        resetAction();
    } catch ({ response }) {
        if (response.status !== 200) {
            createUpdateErrors.value = response.data.errors;
        }
    }
};

const idsToDelete = ref('');
const idsToUpdate = ref('');
const doDelete = async () => {
    const ids = idsToDelete.value.split(',');
    const response = await strukt.call(collection.value.value, endpoint.value.value, { ids });
    if (!response.success) {
        alert('Сталась помилка')
        return false;
    }
    resetAction();
};

function removeSubField(item, index) {
    if (createFormData.value[item].length > 1) {
        createFormData.value[item].splice(index, 1);
    }
}

function addSubField(item) {
    createFormData.value[item].push(null);
}

onMounted(() => {
    strukt.getStructure().then((data) => {
        structure.value = data;
        loading.value = false;
    });
});
</script>

<template>
    <div class="relative min-h-40">
        <x-preloader :loading="loading">
            <div class="flex gap-sm items-end w-1/2">
                <x-select v-model="collection" :options="collectionsOptions" placeholder="Оберіть колекцію" />
                <x-select v-model="endpoint" :options="actualEndpointsOptions" placeholder="Оберіть запит" />
            </div>
            <div v-if="selectedEndpoint.type === 'search'" class="mt-2 flex gap-sm w-1/2 flex-wrap">
                <text-input
                    placeholder="ID"
                    v-model="searchFieldQuery.id"
                />
                <text-input
                    v-for="(parameter, idx) of selectedEndpoint.fields"
                    :key="idx"
                    :placeholder="getFieldSchema(parameter).title"
                    v-model="searchFieldQuery[parameter]"
                />
                <Button class="w-20" v-text="'Знайти'" @click="doSearch" />
            </div>
            <pre
                v-if="socketEventJSON"
                @click="socketEventJSON = ''"
                class="bg-gray-200 mt-4 rounded-sm p-2 overflow-x-auto"
            >{{ socketEventJSON }}</pre>
            <div class="mt-2" v-if="endpoint.value">
                <span v-if="selectedEndpoint.trigger_event" class="text-secondary">Операція викличе сокет-подію</span>
                <div class="relative" v-if="['read', 'search'].includes(selectedEndpoint.type)">
                    <x-preloader :loading="itemsLoading">
                        <pre class="bg-gray-200 mt-4 rounded-sm p-2 overflow-x-auto">{{ fetchedItems }}</pre>
                    </x-preloader>
                </div>
                <div v-if="selectedEndpoint.type === 'update' && !selectedCollection.singleton" class="w-1/2 mt-2">
                    <text-input v-model="idsToUpdate" placeholder="Ідентифікатор" />
                </div>
                <div v-if="selectedEndpoint.type === 'delete'" class="w-1/2 mt-2">
                    <text-input v-if="!selectedCollection.singleton" v-model="idsToDelete" label="Ідентифікатори" placeholder="1,2,3..." />
                    <Button class="w-20 mt-4 self-end" @click="doDelete">Видалити</Button>
                </div>
                <div v-if="['create', 'update'].includes(selectedEndpoint.type)">
                    <div class="w-1/2 flex flex-col bg-white p-4 mt-2 shadow-md rounded-md gap-sm">
                        <template v-for="item in selectedEndpoint.fields">
                            <div v-if="getFieldSchema(item).multiple" :key="item">
                                <span class="text-sm font-medium text-muted">Кілька значень: ↓</span>
                                <div class="ps-4 items-end flex flex-col">
                                    <template v-for="(subValue, subIndex) in createFormData[item]" :key="subIndex">
                                        <component
                                            v-model="createFormData[item][subIndex]"
                                            :is="getComponentByType(getFieldSchema(item).type)"
                                            class="w-full"
                                            :label="getFieldSchema(item).title + ` (${subIndex + 1})`"
                                        >
                                            <template #error>
                                                <form-errors :items="createUpdateErrors" :field="item" :index="subIndex" />
                                                <div class="flex items-center gap-2">
                                                    <Button
                                                        class="w-8 h-8 text-xl mt-2 self-end"
                                                        @click="removeSubField(item, subIndex)"
                                                    >-</Button>
                                                    <Button
                                                        class="w-8 h-8 text-xl mt-2 self-end"
                                                        @click="addSubField(item)"
                                                    >+</Button>
                                                </div>
                                            </template>
                                        </component>
                                    </template>
                                </div>
                            </div>
                            <component
                                v-else
                                :key="item + '_single'"
                                v-model="createFormData[item]"
                                :is="getComponentByType(getFieldSchema(item).type)"
                                :label="getFieldSchema(item).title"
                            >
                                <template #error>
                                    <form-errors :items="createUpdateErrors" :field="item" />
                                </template>
                            </component>
                        </template>

                        <Button class="w-40 mt-4 self-end" @click="doCreateUpdate">Створити/Оновити</Button>
                    </div>
                </div>
            </div>
        </x-preloader>
    </div>
</template>
