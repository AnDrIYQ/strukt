<template>
    <auth-layout>
        <template #title>Вхід до системи</template>
        <template #subtitle>Будь ласка, введіть ваші дані для входу</template>
        <form class="flex flex-col w-full gap-4 mt-2" @submit.prevent="form.post('/login')">
            <text-input
                v-model="form.email"
                label="Електронна пошта"
                placeholder="Введіть email"
            >
                <template #error>
                    <form-errors :items="form.errors" field="email" />
                </template>
            </text-input>
            <password-input
                v-model="form.password"
                label="Пароль"
                placeholder="Введіть пароль"
            >
                <template #error>
                    <form-errors :items="form.errors" field="password" />
                </template>
            </password-input>

            <Button type="submit">
                Увійти
            </Button>
            <Link href="/register">Немає облікового запису? Увійти</Link>
        </form>
    </auth-layout>
</template>

<script setup>
import { ref } from 'vue';
import Button from '@/Components/Button.vue';
import AuthLayout from "@/Layouts/AuthLayout.vue";
import PasswordInput from "@/Components/inputs/PasswordInput.vue";
import TextInput from "@/Components/inputs/TextInput.vue";
import FormErrors from "@/Components/FormErrors.vue";
import { Link, useForm } from "@inertiajs/vue3";

const form = useForm({
    email: '',
    password: '',
});
</script>
