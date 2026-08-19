<script setup lang="ts">
import { BaseButton, Card, FormInput } from 'ornito';
import { Form } from 'vee-validate';
import useLogin from '../composables/useLogin';
const { email, password, rules, isLoading, login } = useLogin();
</script>

<template>
    <Card class="w-full max-w-md">
        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold">Iniciar Sesión</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Ingresá tus credenciales para acceder a Rutely DTE
            </p>
        </div>

        <Form @submit="login" class="space-y-6">
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
        <p
            class="mt-4 block text-center text-base text-gray-500 dark:text-gray-400"
        >
            ¿No tenés una cuenta?
            <RouterLink
                :to="{ name: 'onboarding' }"
                class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                Registrarse
            </RouterLink>
        </p>
    </Card>
</template>
