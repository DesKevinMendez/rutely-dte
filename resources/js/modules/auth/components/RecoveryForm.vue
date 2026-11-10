<script setup lang="ts">
import { Alert, BaseButton, Card, FormInput } from 'ornito';
import { Form } from 'vee-validate';
import useRecovery from '../composables/useRecovery';

const { email, emailRule, successMessage, submit } = useRecovery();
</script>

<template>
    <Card class="w-full max-w-md">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Recuperar Contraseña
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Ingresá tu correo electrónico para recibir las instrucciones de
                recuperación
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

            <Alert v-if="successMessage" type="success">
                {{ successMessage }}
            </Alert>

            <BaseButton
                type="submit"
                variant="primary"
                size="auto"
                class="w-full justify-center"
            >
                Enviar Instrucciones
            </BaseButton>

            <div class="text-center">
                <RouterLink
                    :to="{ name: 'login' }"
                    class="text-sm font-semibold text-primary-600 hover:underline dark:text-primary-400"
                >
                    Volver al Iniciar Sesión
                </RouterLink>
            </div>
        </Form>
    </Card>
</template>
