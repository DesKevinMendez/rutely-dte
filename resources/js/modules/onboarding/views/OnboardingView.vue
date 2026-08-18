<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { BaseButton, Card, FormInput, SearchableSelect } from 'ornito';
import { useAuthUser } from '@/core/composables/useAuthUser';

const router = useRouter();
const { clearSession } = useAuthUser();
const formError = ref<string | null>(null);

const form = reactive({
    name: '',
    commercialName: '',
    nit: '',
    nrc: '',
    phone: '',
    email: '',
    address: '',
    economicActivityCode: '',
    establishmentType: '',
    departmentId: '',
    municipalityId: '',
    districtId: '',
    ownEstablishmentCode: '',
    ownPosCode: '',
});

const municipalityUrl = computed(() => {
    if (!form.departmentId) {
        return '/api/v1/data/municipalities?per_page=100';
    }

    return `/api/v1/data/municipalities?per_page=100&filter[department_id]=${form.departmentId}`;
});

const districtUrl = computed(() => {
    if (!form.municipalityId) {
        return '/api/v1/data/districts?per_page=100';
    }

    return `/api/v1/data/districts?per_page=100&filter[municipality_id]=${form.municipalityId}`;
});

watch(
    () => form.departmentId,
    () => {
        form.municipalityId = '';
        form.districtId = '';
    },
);

watch(
    () => form.municipalityId,
    () => {
        form.districtId = '';
    },
);

const continueFlow = async (): Promise<void> => {
    formError.value = null;

    const requiredValues = [
        form.name,
        form.commercialName,
        form.nit,
        form.phone,
        form.email,
        form.address,
        form.economicActivityCode,
        form.establishmentType,
        form.departmentId,
        form.municipalityId,
        form.districtId,
        form.ownEstablishmentCode,
        form.ownPosCode,
    ];

    if (requiredValues.some((value) => !value)) {
        formError.value = 'Completá todos los campos requeridos antes de continuar.';
        return;
    }

    if (!/^\S+@\S+\.\S+$/.test(form.email)) {
        formError.value = 'Ingresá un correo electrónico válido.';
        return;
    }

    await router.push({ name: 'dashboard' });
};

const logout = async (): Promise<void> => {
    clearSession();
    await router.push({ name: 'login' });
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 px-4 py-8 text-gray-900 dark:bg-gray-900 dark:text-white sm:py-12">
        <div class="mx-auto w-full max-w-4xl space-y-6">
            <header class="text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl border border-gray-200 bg-white text-lg font-bold text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-primary-400">
                    R
                </div>
                <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">Configuración inicial</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight">Datos de la empresa emisora</h1>
                <p class="mx-auto mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                    Completá la información que utilizará Rutely DTE para identificar al emisor y construir sus documentos tributarios electrónicos.
                </p>
            </header>

            <Card class="border-gray-200 dark:border-gray-800">
                <form class="space-y-8" @submit.prevent="continueFlow">
                    <section class="space-y-4">
                        <div>
                            <h2 class="text-base font-bold">Información fiscal y comercial</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Datos generales del contribuyente emisor.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormInput
                                v-model="form.name"
                                id="companyName"
                                name="companyName"
                                label="Razón Social"
                                placeholder="Ej. RUTELY S.A. DE C.V."
                            />

                            <FormInput
                                v-model="form.commercialName"
                                id="commercialName"
                                name="commercialName"
                                label="Nombre Comercial"
                                placeholder="Ej. Rutely"
                            />

                            <FormInput
                                v-model="form.nit"
                                id="nit"
                                name="nit"
                                label="NIT"
                                placeholder="0614-280390-112-1"
                                mask="####-######-###-#"
                            />

                            <FormInput
                                v-model="form.nrc"
                                id="nrc"
                                name="nrc"
                                label="NRC"
                                placeholder="Ej. 123456-7"
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <SearchableSelect
                                v-model="form.economicActivityCode"
                                id="economicActivityCode"
                                name="economicActivityCode"
                                label="Actividad Económica (CAT-019)"
                                placeholder="Buscá por descripción"
                                url="/api/v1/data/economic-activities?per_page=25"
                                search-by="filter[description]"
                                label-key="{code} - {description}"
                                value-key="code"
                                subtitle-key="code"
                            />

                            <SearchableSelect
                                v-model="form.establishmentType"
                                id="establishmentType"
                                name="establishmentType"
                                label="Tipo de Establecimiento (CAT-009)"
                                placeholder="Seleccioná un tipo"
                                url="/api/v1/data/establishment-types?per_page=100"
                                search-by="filter[description]"
                                label-key="description"
                                value-key="code"
                                subtitle-key="code"
                                local-search-first
                            />
                        </div>
                    </section>

                    <hr class="border-gray-100 dark:border-gray-800" />

                    <section class="space-y-4">
                        <div>
                            <h2 class="text-base font-bold">Contacto y ubicación</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Dirección fiscal y medios de contacto del emisor.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormInput
                                v-model="form.phone"
                                id="phone"
                                name="phone"
                                type="tel"
                                label="Teléfono"
                                placeholder="+503 7802 7600"
                                mask="+503 #### ####"
                            />

                            <FormInput
                                v-model="form.email"
                                id="companyEmail"
                                name="companyEmail"
                                type="email"
                                label="Correo Electrónico"
                                placeholder="facturacion@rutely.biz"
                                autocomplete="email"
                            />
                        </div>

                        <FormInput
                            v-model="form.address"
                            id="address"
                            name="address"
                            label="Complemento de Dirección"
                            placeholder="Calle, avenida, número de local y referencias"
                        />

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <SearchableSelect
                                v-model="form.departmentId"
                                id="departmentId"
                                name="departmentId"
                                label="Departamento (CAT-012)"
                                placeholder="Seleccioná"
                                url="/api/v1/data/departments?per_page=100"
                                search-by="filter[name]"
                                label-key="name"
                                value-key="id"
                                subtitle-key="code"
                                local-search-first
                            />

                            <SearchableSelect
                                :key="form.departmentId || 'municipality-empty'"
                                v-model="form.municipalityId"
                                id="municipalityId"
                                name="municipalityId"
                                label="Municipio (CAT-013)"
                                :placeholder="form.departmentId ? 'Seleccioná' : 'Elegí un departamento'"
                                :url="municipalityUrl"
                                search-by="filter[name]"
                                label-key="name"
                                value-key="id"
                                subtitle-key="code"
                                :disabled="!form.departmentId"
                                local-search-first
                            />

                            <SearchableSelect
                                :key="form.municipalityId || 'district-empty'"
                                v-model="form.districtId"
                                id="districtId"
                                name="districtId"
                                label="Distrito (CAT-008)"
                                :placeholder="form.municipalityId ? 'Seleccioná' : 'Elegí un municipio'"
                                :url="districtUrl"
                                search-by="filter[name]"
                                label-key="name"
                                value-key="id"
                                subtitle-key="code"
                                :disabled="!form.municipalityId"
                                local-search-first
                            />
                        </div>
                    </section>

                    <hr class="border-gray-100 dark:border-gray-800" />

                    <section class="space-y-4">
                        <div>
                            <h2 class="text-base font-bold">Códigos internos del establecimiento</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Estos códigos los define tu empresa y se utilizarán para construir el número de control de los DTE.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormInput
                                v-model="form.ownEstablishmentCode"
                                id="ownEstablishmentCode"
                                name="ownEstablishmentCode"
                                label="Código de Establecimiento"
                                placeholder="Ej. M001"
                            />

                            <FormInput
                                v-model="form.ownPosCode"
                                id="ownPosCode"
                                name="ownPosCode"
                                label="Código de Punto de Venta"
                                placeholder="Ej. P001"
                            />
                        </div>
                    </section>

                    <div
                        v-if="formError"
                        class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/50 dark:text-red-300"
                    >
                        {{ formError }}
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <BaseButton type="button" variant="outline" size="auto" @click="logout">
                            Cerrar sesión
                        </BaseButton>

                        <BaseButton type="submit" variant="primary" size="auto">
                            Continuar
                        </BaseButton>
                    </div>
                </form>
            </Card>
        </div>
    </div>
</template>
