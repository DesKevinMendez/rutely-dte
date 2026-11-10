<script setup lang="ts">
import { IconLock, IconMail } from '@tabler/icons-vue';
import { Alert, BaseButton, Card, FormInput } from 'ornito';
import { Form } from 'vee-validate';
import useLogin from '../composables/useLogin';

const { email, password, rules, isLoading, error, login } = useLogin();
</script>

<template>
    <Card class="w-full max-w-md">
        <div class="mb-8 text-center">
            <h1 class="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                Iniciar sesión
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Accedé a tu cuenta
            </p>
        </div>

        <Form class="space-y-6" @submit="login">
            <Alert v-if="error" type="danger">{{ error }}</Alert>

            <FormInput
                v-model="email"
                id="email"
                name="email"
                type="email"
                label="Correo Electrónico"
                placeholder="admin@rutely.biz"
                autocomplete="email"
                :left-icon="IconMail"
                :rules="rules.email"
            />
            <FormInput
                v-model="password"
                id="password"
                name="password"
                type="password"
                label="Contraseña"
                placeholder="••••••••"
                autocomplete="current-password"
                :left-icon="IconLock"
                :rules="rules.password"
            />

            <div class="text-right">
                <RouterLink
                    :to="{ name: 'recovery' }"
                    class="text-sm text-gray-600 transition-colors hover:text-primary-600 hover:underline dark:text-gray-400 dark:hover:text-primary-400"
                >
                    ¿Olvidaste tu contraseña?
                </RouterLink>
            </div>

            <BaseButton
                type="submit"
                variant="primary"
                size="auto"
                :loading="isLoading"
                class="w-full justify-center"
            >
                Acceder al Sistema
            </BaseButton>
        </Form>
    </Card>
</template>
