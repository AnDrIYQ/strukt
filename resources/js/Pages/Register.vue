<script setup>
import Button from '@/Components/Button.vue';
import AuthLayout from "@/Layouts/AuthLayout.vue";
import TextInput from "@/Components/inputs/TextInput.vue";
import PasswordInput from "@/Components/inputs/PasswordInput.vue";
import { useForm, Link } from '@inertiajs/vue3'
import FormErrors from "@/Components/FormErrors.vue";

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

</script>

<template>
    <auth-layout>
        <template #title>Реєстрація</template>
        <template #subtitle>Введіть необхідні дані, для реєстрації</template>
        <form
            class="flex flex-col w-full gap-4 mt-2"
            @submit.prevent="form.post('/register')"
        >
            <text-input
                v-model="form.name"
                label="Ім'я"
                placeholder="Введіть ім'я"
            >
                <template #error>
                    <form-errors :items="form.errors" field="name" />
                </template>
            </text-input>

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

            <password-input
                v-model="form.password_confirmation"
                placeholder="Підтвердіть пароль"
            >
                <template #error>
                    <form-errors :items="form.errors" field="password_confirmation" />
                </template>
            </password-input>

            <Button type="submit">
                Підтвердити
            </Button>
            <Link href="/login">Вже маєте аккаунт? Увійти</Link>
        </form>
    </auth-layout>
</template>
