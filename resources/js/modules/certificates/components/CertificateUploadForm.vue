<script setup lang="ts">
import { IconCloudUpload } from '@tabler/icons-vue';
import { BaseButton, Card, FormInput } from 'ornito';
import { ref } from 'vue';
import type {
    CertificateEnvironment,
    CertificateUploadPayload,
} from '../types/certificate.types';

const {
    selectedEnvironment,
    uploadSuccess,
    isSubmitting = false,
} = defineProps<{
    selectedEnvironment: CertificateEnvironment;
    uploadSuccess: boolean;
    isSubmitting?: boolean;
}>();

const emit = defineEmits<{
    environmentChange: [environment: CertificateEnvironment];
    upload: [payload: CertificateUploadPayload];
}>();

const selectedFile = ref<File | null>(null);
const fileName = ref('');
const password = ref('');
const isDragging = ref(false);

const setFile = (file?: File): void => {
    selectedFile.value = file ?? null;
    fileName.value = file?.name ?? '';
};

const handleFileChange = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    setFile(input.files?.[0]);
};

const handleDrop = (event: DragEvent): void => {
    isDragging.value = false;
    setFile(event.dataTransfer?.files?.[0]);
};

const submit = (): void => {
    if (!selectedFile.value || isSubmitting) {
        return;
    }

    emit('upload', {
        file: selectedFile.value,
        password: password.value,
    });
};
</script>

<template>
    <Card
        title="Cargar Certificado Digital (MH)"
        subtitle="Seleccione el archivo XML del certificado de firma entregado por el Ministerio de Hacienda."
    >
        <form class="space-y-5" @submit.prevent="submit">
            <div
                v-if="uploadSuccess"
                class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
            >
                Certificado guardado correctamente para el ambiente de
                <strong>{{ selectedEnvironment }}</strong
                >.
            </div>

            <div>
                <p
                    class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                >
                    Ambiente de Destino
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <BaseButton
                        type="button"
                        variant="outline"
                        size="auto"
                        class="h-auto justify-start py-3 text-left"
                        :disabled="isSubmitting"
                        :class="
                            selectedEnvironment === 'PRUEBAS'
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30'
                                : ''
                        "
                        @click="emit('environmentChange', 'PRUEBAS')"
                    >
                        <span class="flex flex-col items-start leading-tight">
                            <span class="font-semibold">Pruebas (Sandbox)</span>
                            <span
                                class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-400"
                                >Ambiente de pruebas MH</span
                            >
                        </span>
                    </BaseButton>
                    <BaseButton
                        type="button"
                        variant="outline"
                        size="auto"
                        class="h-auto justify-start py-3 text-left"
                        :disabled="isSubmitting"
                        :class="
                            selectedEnvironment === 'PRODUCCION'
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30'
                                : ''
                        "
                        @click="emit('environmentChange', 'PRODUCCION')"
                    >
                        <span class="flex flex-col items-start leading-tight">
                            <span class="font-semibold">Producción</span>
                            <span
                                class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-400"
                                >Emisión real de DTEs</span
                            >
                        </span>
                    </BaseButton>
                </div>
            </div>

            <div>
                <label
                    for="certificate-file"
                    class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                >
                    Archivo del Certificado XML / CRT
                </label>
                <label
                    for="certificate-file"
                    class="flex min-h-40 cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center transition-colors hover:border-primary-400 dark:border-gray-700 dark:bg-gray-900"
                    :class="
                        isDragging
                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/20'
                            : ''
                    "
                    @dragenter.prevent="isDragging = true"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                >
                    <span
                        class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-500 dark:bg-primary-950/40 dark:text-primary-400"
                    >
                        <IconCloudUpload :size="24" stroke="1.75" />
                    </span>
                    <span
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        {{
                            fileName ||
                            'Hacé clic para seleccionar o arrastrá el archivo'
                        }}
                    </span>
                    <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Archivos soportados: .xml, .crt o .txt
                    </span>
                </label>
                <input
                    id="certificate-file"
                    type="file"
                    class="sr-only"
                    accept=".xml,.crt,.txt"
                    :disabled="isSubmitting"
                    @change="handleFileChange"
                />
            </div>

            <FormInput
                v-model="password"
                id="certificate-password"
                name="password"
                type="password"
                label="Contraseña de la Clave Privada (Opcional)"
                placeholder="Ingrese la contraseña si el certificado requiere clave privada"
            />
            <p class="-mt-3 text-xs text-gray-500 dark:text-gray-400">
                La contraseña se valida contra la clave privada antes de
                guardar.
            </p>

            <div
                class="flex justify-end border-t border-gray-200 pt-3 dark:border-gray-700"
            >
                <BaseButton
                    type="submit"
                    variant="primary"
                    size="auto"
                    :disabled="!selectedFile || isSubmitting"
                >
                    {{
                        isSubmitting
                            ? 'Guardando…'
                            : 'Guardar y Encriptar Certificado'
                    }}
                </BaseButton>
            </div>
        </form>
    </Card>
</template>
