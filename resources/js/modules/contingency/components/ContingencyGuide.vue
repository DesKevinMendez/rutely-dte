<script setup lang="ts">
import { Card } from 'ornito';
</script>

<template>
    <Card title="Instrucciones del Modelo de Contingencia CAT-005">
        <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
            <p>
                Cuando el servicio del Ministerio de Hacienda no responda
                (errores 500, 502, 503, 504 o caída de conexión), el sistema
                activa automáticamente el
                <strong class="text-gray-900 dark:text-white"
                    >Circuit Breaker</strong
                >
                y almacena los comprobantes en estado
                <span class="font-bold text-amber-600 dark:text-amber-400"
                    >CONTINGENCIA</span
                >.
            </p>

            <div class="grid grid-cols-1 gap-4 pt-2 md:grid-cols-3">
                <Card title="1. Generación Local" class="h-full">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Se genera y firma el DTE en local entregando copia al
                        cliente.
                    </p>
                </Card>

                <Card title="2. Encolado Seguro" class="h-full">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        El DTE pasa a la cola de reintentos con backoff
                        exponencial y jitter.
                    </p>
                </Card>

                <Card title="3. Transmisión Lote" class="h-full">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Al restablecerse el MH, se envía el Evento de
                        Contingencia (CAT-005).
                    </p>
                </Card>
            </div>
        </div>
    </Card>
</template>
