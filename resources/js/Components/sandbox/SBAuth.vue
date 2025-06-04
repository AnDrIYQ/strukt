<script setup>
import useSandbox from "@/Composables/sandbox.js";
import XPreloader from "@/Components/XPreloader.vue";
import TextInput from "@/Components/inputs/TextInput.vue";
import { reactive, ref } from "vue";
import PasswordInput from "@/Components/inputs/PasswordInput.vue";
import Button from "@/Components/Button.vue";
import FormErrors from "@/Components/FormErrors.vue";
import DataTable from "@/Components/DataTable.vue";

const { strukt, me, loading, checkMe } = useSandbox();

const logout = async () => {
    await strukt.logout();
    await checkMe();
};

const loginForm = reactive({
    email: '',
    password: '',
});
const loginErrors = ref([]);
const loginHandle = async () => {
    try {
        const response = await strukt.login(loginForm.email, loginForm.password);
        if (response.user && response.token) {
            await checkMe();
        }
    } catch ({ response }) {
        if (response.data.errors) {
            loginErrors.value = response.data.errors;
        }
    }
};

const registerForm = reactive({
    email: '',
    name: '',
    password: '',
    password_confirmation: '',
});
const registerErrors = ref([]);
const registerHandle = async () => {
    try {
        const response = await strukt.register({
            email: registerForm.email,
            name: registerForm.name,
            password: registerForm.password,
            password_confirmation: registerForm.password_confirmation,
        });
        if (response.user && response.token) {
            await checkMe();
        }
    } catch ({ response }) {
        if (response.data.errors) {
            registerErrors.value = response.data.errors;
        }
    }
};
</script>

<template>
    <div class="relative min-h-40 mb-lg border-b border-border pb-md">
        <x-preloader :loading="loading">
            <div v-if="me" class="w-3/4 flex flex-col p-1">
                <Button @click="logout" class="w-20 mt-2">Вийти</Button>
                <data-table
                    :items="[{
                        name: me.name,
                        email: me.email,
                        role: me.role,
                        is_public: me.is_public ? 'Так' : 'Ні',
                        avatar: me.avatar,
                    }]"
                    :search="false"
                    :actions="false"
                    :columns="[
                        { id: 'name', header: 'Ім\'я' },
                        { id: 'email', header: 'Email' },
                        { id: 'role', header: 'Роль' },
                        { id: 'is_public', header: 'Видимий для всіх' },
                        { id: 'avatar', header: 'Зображення' },
                    ]"
                >
                    <template #cell-avatar="{ value }">
                        {{ !value ? 'Немає' : '' }}
                        <img v-if="value" :src="value" class="w-10 h-10 object-cover cursor-pointer rounded-full" alt="">
                    </template>
                </data-table>
            </div>
            <div v-else class="flex gap-4 items-start">
                <div class="bg-white p-4 shadow-md rounded-md flex flex-col gap-sm">
                    <span class="text-sm font-medium text-muted">Логін</span>
                    <text-input v-model="loginForm.email" placeholder="Email">
                        <template #error>
                            <form-errors :items="loginErrors" field="email" />
                        </template>
                    </text-input>
                    <password-input v-model="loginForm.password" placeholder="Пароль">
                        <template #error>
                            <form-errors :items="loginErrors" field="password" />
                        </template>
                    </password-input>
                    <Button @click="loginHandle">Увійти</Button>
                </div>
                <div class="bg-white p-4 shadow-md rounded-md flex flex-col gap-sm">
                    <span class="text-sm font-medium text-muted">Реєстрація</span>
                    <text-input v-model="registerForm.email" placeholder="Email">
                        <template #error>
                            <form-errors :items="registerErrors" field="email" />
                        </template>
                    </text-input>
                    <text-input v-model="registerForm.name" placeholder="Ім'я">
                        <template #error>
                            <form-errors :items="registerErrors" field="name" />
                        </template>
                    </text-input>
                    <password-input v-model="registerForm.password" placeholder="Пароль">
                        <template #error>
                            <form-errors :items="registerErrors" field="password" />
                        </template>
                    </password-input>
                    <password-input v-model="registerForm.password_confirmation" placeholder="Підтвердити пароль">
                        <template #error>
                            <form-errors :items="registerErrors" field="password_confirmation" />
                        </template>
                    </password-input>
                    <Button @click="registerHandle">Реєстрація</Button>
                </div>
            </div>
        </x-preloader>
    </div>
</template>
