<script setup lang="ts">
import { Form } from 'vee-validate';
import * as yup from 'yup';
import { BaseButton, Card, FormInput } from 'ornito';
import useLogin from '../composables/useLogin';

const { email, password, isLoading, error, login } = useLogin();

const emailRules = yup
    .string()
    .required('El correo electrónico es requerido.')
    .email('Ingresá un correo electrónico válido.');

const passwordRules = yup
    .string()
    .required('La contraseña es requerida.');
</script>

<template>
    <Card class="w-full max-w-md">
        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold">Iniciar Sesión</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Ingresá tus credenciales para acceder a Rutely DTE
            </p>
        </div>

        <Form class="space-y-5" @submit="login">
            <FormInput
                v-model="email"
                id="email"
                name="email"
                type="email"
                label="Correo Electrónico"
                placeholder="admin@rutely.biz"
                autocomplete="email"
                :rules="emailRules"
            />

            <FormInput
                v-model="password"
                id="password"
                name="password"
                type="password"
                label="Contraseña"
                placeholder="••••••••"
                autocomplete="current-password"
                :rules="passwordRules"
            />

            <div
                v-if="error"
                class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/50 dark:text-red-300"
            >
                {{ error }}
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

            <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                ¿No tenés una cuenta?
                <RouterLink
                    :to="{ name: 'onboarding' }"
                    class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    Registrarse
                </RouterLink>
            </p>
        </Form>
    </Card>
</template>
