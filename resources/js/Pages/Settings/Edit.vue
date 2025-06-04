<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import FormLayout from '@/Layouts/FormLayout.vue';
import Button from "@/Components/Button.vue";
import { ref } from 'vue'
import TextInput from "@/Components/inputs/TextInput.vue";
import PasswordInput from "@/Components/inputs/PasswordInput.vue";
import FileInput from "@/Components/inputs/FileInput.vue";
import { usePage } from "@inertiajs/vue3";
import CheckboxInput from "@/Components/inputs/CheckboxInput.vue";
import { Info } from "lucide-vue-next";

const page = usePage();

const formData = ref({
    name: page.props.user.name,
    password: '',
    password_confirmation: '',
    is_public: !!page.props.user.is_public,
    avatar: null,
});
</script>

<template>
    <admin-layout>
        <template #title>Налаштування</template>
        <template #subtitle>Тут можна налаштувати профіль</template>
        <form-layout
            v-model="formData"
            action="/settings"
            :trim-empty="true"
        >
            <template #header>
                <p class="text-md font-bold text-accent">Дані користувача</p>
            </template>

            <template #default="{ form }">
                <text-input label="Ім'я" v-model="form.name" placeholder="Ім’я" />
                <password-input label="Пароль" v-model="form.password" placeholder="Новий пароль" />
                <password-input label="Підтвердити пароль" v-model="form.password_confirmation" placeholder="Підтвердження" />
                <checkbox-input label="Видимий іншим" v-model="form.is_public">
                    <template #error>
                        <div class="flex items-center gap-1 mt-2 text-secondary">
                            <Info class="h-4 w-4" />
                            <span class="text-secondary text-xs">
                                Якщо обрано цю опцію, користувач буде видимим для інших,
                                а також наступні поля цього користувача будуть доступні іншим
                                (Ел. пошта, Імя, Ідентифікатор)
                            </span>
                        </div>
                    </template>
                </checkbox-input>
                <file-input
                    v-model="form.avatar"
                    label="Оновити зображення"
                    :existing="`/user/avatar?ts=${page.props.user.updated_at}`"
                />
            </template>

            <template #buttons="{ form }">
                <Button type="submit" :disabled="form.processing" class="w-full">
                    Зберегти
                </Button>
            </template>
        </form-layout>
    </admin-layout>
</template>
