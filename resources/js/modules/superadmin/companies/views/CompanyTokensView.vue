<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import ApiTokensContent from '@/modules/tokens/components/ApiTokensContent.vue';

const route = useRoute();
const companyId = computed(() => String(route.params.id ?? ''));
</script>

<template>
    <ApiTokensContent :company-id="companyId" />
</template>
