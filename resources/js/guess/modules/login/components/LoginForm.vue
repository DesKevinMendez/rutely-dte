<script setup lang="ts">
import { ErrorMessage } from 'vee-validate';
import { BaseButton, Card, FormInput } from 'ornito';
import useLogin from '../composables/useLogin';

const { email, password, rules, isLoading, error, login } = useLogin();

const errorClass = 'mt-1 text-sm text-danger-600 dark:text-danger-400';
</script>

<template>
    <Card class="w-full max-w-md">
        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold">Iniciar Sesión</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Ingresá tus credenciales para acceder a Rutely DTE
            </p>
        </div>

        <form id="login-form" class="space-y-5" novalidate @submit.prevent="login">
            <div>
                <FormInput
                    v-model="email"
                    id="email"
                    name="email"
                    type="email"
                    label="Correo Electrónico"
                    placeholder="admin@rutely.biz"
                    autocomplete="email"
                    :rules="rules.email"
                />
                <ErrorMessage name="email" :class="errorClass" />
            </div>

            <div>
                <FormInput
                    v-model="password"
                    id="password"
                    name="password"
                    type="password"
                    label="Contraseña"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    :rules="rules.password"
                />
                <ErrorMessage name="password" :class="errorClass" />
            </div>

            <div
                v-if="error"
                class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/50 dark:text-red-300"
            >
                {{ error }}
            </div>

            <BaseButton
                form="login-form"
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
        </form>
    </Card>
</template>
