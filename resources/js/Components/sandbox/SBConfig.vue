<script setup>
import { ref } from "vue";
import useSandbox from "@/Composables/sandbox.js";
import TextInput from "@/Components/inputs/TextInput.vue";
import FormErrors from "@/Components/FormErrors.vue";
import Button from "@/Components/Button.vue";
import PasswordInput from "@/Components/inputs/PasswordInput.vue";
import CheckboxInput from "@/Components/inputs/CheckboxInput.vue";
import FileInput from "@/Components/inputs/FileInput.vue";

const { strukt, checkMe, me } = useSandbox();

const updateForm = ref({
    name: me.value.name,
    password: '',
    password_confirmation: '',
    is_public: me.value.is_public === 1,
    avatar: null,
});
const errors = ref([]);
const updateHandle = async () => {
    try {
        await strukt.updateSettings({
            name: updateForm.value.name,
            password: updateForm.value.password,
            password_confirmation: updateForm.value.password_confirmation,
            is_public: updateForm.value.is_public ? '1' : '0',
            avatar: updateForm.value.avatar,
        });
        await checkMe();
    } catch ({ response }) {
        errors.value = response.data.errors;
    }
};
</script>

<template>
    <div class="w-1/2 bg-white p-4 shadow-md rounded-md flex flex-col gap-sm">
        <text-input v-model="updateForm.name" placeholder="Ім'я">
            <template #error>
                <form-errors :items="errors" field="name" />
            </template>
        </text-input>
        <password-input v-model="updateForm.password" placeholder="Пароль">
            <template #error>
                <form-errors :items="errors" field="password" />
            </template>
        </password-input>
        <password-input v-model="updateForm.password_confirmation" placeholder="Підтвердити пароль">
            <template #error>
                <form-errors :items="errors" field="password_confirmation" />
            </template>
        </password-input>
        <checkbox-input v-model="updateForm.is_public" label="Видимий іншим">
            <template #error>
                <form-errors :items="errors" field="is_public" />
            </template>
        </checkbox-input>
        <file-input :existing="me.avatar" v-model="updateForm.avatar" label="Зображення">
            <template #error>
                <form-errors :items="errors" field="avatar" />
            </template>
        </file-input>
        <Button @click="updateHandle" class="w-20 self-end">Оновити</Button>
    </div>
</template>
