<script setup lang="ts">
import { BaseButton, Card, FormInput } from 'ornito';
import { Form } from 'vee-validate';
import useRecovery from '../composables/useRecovery';

const { email, emailRule, successMessage, submit } = useRecovery();
</script>

<template>
    <Card class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <img src="/favicon.svg" alt="Rutely" class="h-9 w-9 object-contain" />
            </div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Recuperar Contraseña</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Ingresá tu correo electrónico para recibir las instrucciones de recuperación
            </p>
        </div>

        <Form class="space-y-6" @submit="submit">
            <FormInput
                v-model="email"
                id="recovery-email"
                name="email"
                type="email"
                label="Correo Electrónico"
                placeholder="admin@rutely.biz"
                autocomplete="email"
                :rules="emailRule"
            />

            <div v-if="successMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                {{ successMessage }}
            </div>

            <BaseButton type="submit" variant="primary" size="auto" class="w-full justify-center">
                Enviar Instrucciones
            </BaseButton>

            <div class="text-center">
                <RouterLink :to="{ name: 'login' }" class="text-sm font-semibold text-primary-600 hover:underline dark:text-primary-400">
                    Volver al Iniciar Sesión
                </RouterLink>
            </div>
        </Form>
    </Card>
</template>
