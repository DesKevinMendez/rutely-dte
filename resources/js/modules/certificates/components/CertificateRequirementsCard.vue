<script setup lang="ts">
import { Card } from 'ornito';
</script>

<template>
    <Card title="Requisitos del Certificado">
        <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
            <div class="flex items-start gap-2">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-100 text-[10px] font-bold text-primary-600 dark:bg-primary-900/60 dark:text-primary-400">1</span>
                <p>El certificado debe corresponder al NIT del emisor registrado en la empresa.</p>
            </div>
            <div class="flex items-start gap-2">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-100 text-[10px] font-bold text-primary-600 dark:bg-primary-900/60 dark:text-primary-400">2</span>
                <p>Formato válido XML de clave pública y privada entregado por el Ministerio de Hacienda.</p>
            </div>
            <div class="flex items-start gap-2">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-100 text-[10px] font-bold text-primary-600 dark:bg-primary-900/60 dark:text-primary-400">3</span>
                <p>Si el certificado requiere contraseña para la clave privada, asegúrese de ingresarla correctamente.</p>
            </div>
        </div>
    </Card>
</template>
