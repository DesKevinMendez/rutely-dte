<script setup lang="ts">
import { BaseButton, Card, FormInput } from 'ornito';
import { Form } from 'vee-validate';
import useLogin from '../composables/useLogin';

const { email, password, rules, isLoading, error, login } = useLogin();
</script>

<template>
    <Card class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <img src="/favicon.svg" alt="Rutely" class="h-9 w-9 object-contain" />
            </div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Iniciar Sesión</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Ingresá tus credenciales autorizadas de administrador
            </p>
        </div>

        <Form class="space-y-6" @submit="login">
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

            <div class="text-right">
                <RouterLink
                    :to="{ name: 'recovery' }"
                    class="text-sm text-gray-600 transition-colors hover:text-primary-600 hover:underline dark:text-gray-400 dark:hover:text-primary-400"
                >
                    ¿Olvidaste tu contraseña?
                </RouterLink>
            </div>

            <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/60 dark:text-red-400">
                {{ error }}
            </div>

            <BaseButton type="submit" variant="primary" size="auto" :loading="isLoading" class="w-full justify-center">
                Acceder al Sistema
            </BaseButton>
        </Form>

        <p class="mt-5 text-center text-sm text-gray-500 dark:text-gray-400">
            ¿No tenés una cuenta?
            <RouterLink :to="{ name: 'onboarding' }" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">
                Registrarse
            </RouterLink>
        </p>
    </Card>
</template>
