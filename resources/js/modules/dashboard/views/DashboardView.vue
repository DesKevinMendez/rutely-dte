<script setup lang="ts">
import { computed } from 'vue';
import { Card, StatCard } from 'ornito';
import { useAuth } from '@/core/stores/auth';

const auth = useAuth();
const greetingName = computed(() => auth.user?.name ?? 'Usuario');
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">Rutely DTE</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">
                    Resumen de Facturación Electrónica
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Bienvenido, {{ greetingName }}. Tu sesión está activa.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                title="Total Emitidos"
                :value="0"
                description="Documentos procesados"
                change-type="info"
            />
            <StatCard
                title="Monto Total ($ USD)"
                value="$0.00"
                description="Facturación bruta acumulada"
                change-type="info"
            />
            <StatCard
                title="Exitosos (MH)"
                :value="0"
                description="Aprobados con sello de recibido"
                change-type="positive"
            />
            <StatCard
                title="Rechazados / Contingencia"
                :value="0"
                description="Requieren atención o reintento"
                change-type="negative"
            />
        </div>

        <Card>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold">Sesión autenticada</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        El dashboard ya está protegido por el token Sanctum obtenido en el login.
                    </p>
                </div>

                <div class="text-sm sm:text-right">
                    <p class="font-medium">{{ auth.user?.email }}</p>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">{{ auth.user?.role }}</p>
                </div>
            </div>
        </Card>
    </div>
</template>
