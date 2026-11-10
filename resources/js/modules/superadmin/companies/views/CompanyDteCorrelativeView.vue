<script setup lang="ts">
import { Card } from 'ornito';
import DteCorrelativeForm from '@/modules/superadmin/companies/components/DteCorrelativeForm.vue';
import useCompanyShow from '@/modules/superadmin/companies/composables/useCompanyShow';

const { company } = useCompanyShow();
</script>

<template>
    <Card
        v-if="company"
        title="Correlativo DTE"
        subtitle="Configura el número que se asignará al próximo documento electrónico."
    >
        <p class="text-sm text-gray-600 dark:text-gray-400">
            El correlativo es el número consecutivo que aparece al final del
            número de control de cada DTE. Por ejemplo, si el próximo
            correlativo es 125, el número de control terminará en
            000000000000125.
        </p>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
            La secuencia se comparte entre los tipos de DTE del establecimiento,
            aumenta con cada documento y comienza de nuevo cada año. Al guardar
            un valor, ese será el número del próximo DTE que se emita.
        </p>
        <DteCorrelativeForm :company-id="company.id" />
    </Card>
</template>
