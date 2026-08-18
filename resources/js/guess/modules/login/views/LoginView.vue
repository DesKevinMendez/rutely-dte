<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { BaseButton, Card, FormInput } from 'ornito';
import { useAuthUser } from '@/core/composables/useAuthUser';
import { useRequest } from '@/core/composables/useRequest';
import type { LoginResponse } from '@/core/types/auth.types';

const router = useRouter();
const { post, isLoading, error } = useRequest();
const { setUser } = useAuthUser();

const email = ref('');
const password = ref('');
const validationError = ref<string | null>(null);

const login = async (): Promise<void> => {
    validationError.value = null;

    if (!email.value || !password.value) {
        validationError.value = 'Ingresá tu correo electrónico y contraseña.';
        return;
    }

    const response = await post<LoginResponse>('/api/v1/login', {
        email: email.value,
        password: password.value,
        device_name: 'rutely-dte-web',
    });

    const session = response.data.value?.data;

    if (!session?.token || !session.user) {
        return;
    }

    localStorage.setItem('auth_token', session.token);
    setUser(session.user);

    await router.push({
        name: session.user.company_id ? 'dashboard' : 'onboarding',
    });
};
</script>

<template>
    <div class="min-h-screen bg-white text-gray-900 dark:bg-gray-900 dark:text-white lg:flex">
        <section class="flex min-h-[36vh] items-center justify-center bg-gray-50 px-6 py-12 dark:bg-gray-800/40 lg:min-h-screen lg:w-2/3">
            <div class="max-w-2xl text-center">
                <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-200 bg-white text-xl font-bold text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-primary-400">
                    R
                </div>
                <h1 class="text-balance text-4xl font-bold leading-tight lg:text-6xl">
                    Facturación Electrónica sin fricción
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-balance text-lg text-gray-600 dark:text-gray-300 lg:text-xl">
                    Generá, firmá y transmití tus DTE al Ministerio de Hacienda desde un solo lugar.
                </p>
            </div>
        </section>

        <section class="flex w-full items-center justify-center p-6 sm:p-8 lg:min-h-screen lg:w-1/3">
            <Card class="w-full max-w-md">
                <div class="mb-8 text-center">
                    <h2 class="text-3xl font-bold">Iniciar Sesión</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Ingresá tus credenciales para acceder a Rutely DTE
                    </p>
                </div>

                <form class="space-y-5" @submit.prevent="login">
                    <FormInput
                        v-model="email"
                        id="email"
                        name="email"
                        type="email"
                        label="Correo Electrónico"
                        placeholder="admin@rutely.biz"
                        autocomplete="email"
                    />

                    <FormInput
                        v-model="password"
                        id="password"
                        name="password"
                        type="password"
                        label="Contraseña"
                        placeholder="••••••••"
                        autocomplete="current-password"
                    />

                    <div
                        v-if="validationError || error"
                        class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/50 dark:text-red-300"
                    >
                        {{ validationError ?? error }}
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
                </form>
            </Card>
        </section>
    </div>
</template>
