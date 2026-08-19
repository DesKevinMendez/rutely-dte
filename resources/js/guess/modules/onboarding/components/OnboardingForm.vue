<script setup lang="ts">
import { BaseButton, Card, FormInput, SearchableSelect } from 'ornito';
import { Form } from 'vee-validate';
import useOnboarding from '../composables/useOnboarding';

const {
    form,
    validationSchema,
    municipalityUrl,
    districtUrl,
    isAuthenticated,
    isLoading,
    continueFlow,
    goToLogin,
} = useOnboarding();
</script>

<template>
    <Card class="border-gray-200 dark:border-gray-800">
        <Form
            id="onboarding-form"
            class="space-y-8"
            :validation-schema="validationSchema"
            @submit="continueFlow"
        >
            <section v-if="!isAuthenticated" class="space-y-4">
                <div>
                    <h2 class="text-base font-bold">Cuenta de administrador</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Creá la cuenta que utilizarás para administrar Rutely DTE.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormInput
                        v-model="form.userName"
                        id="userName"
                        name="userName"
                        label="Nombre"
                        placeholder="Ej. Kevin Mendez"
                        autocomplete="name"
                    />

                    <FormInput
                        v-model="form.userEmail"
                        id="userEmail"
                        name="userEmail"
                        type="email"
                        label="Correo Electrónico"
                        placeholder="admin@rutely.biz"
                        autocomplete="email"
                    />

                    <FormInput
                        v-model="form.password"
                        id="password"
                        name="password"
                        type="password"
                        label="Contraseña"
                        placeholder="••••••••"
                        autocomplete="new-password"
                    />

                    <FormInput
                        v-model="form.passwordConfirmation"
                        id="passwordConfirmation"
                        name="passwordConfirmation"
                        type="password"
                        label="Confirmar Contraseña"
                        placeholder="••••••••"
                        autocomplete="new-password"
                    />
                </div>
            </section>

            <hr v-if="!isAuthenticated" class="border-gray-100 dark:border-gray-800" />

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
                        id="name"
                        name="name"
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
                        id="email"
                        name="email"
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

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                <BaseButton type="button" variant="outline" size="auto" @click="goToLogin">
                    Volver al login
                </BaseButton>

                <BaseButton
                    type="submit"
                    variant="primary"
                    size="auto"
                    :loading="isLoading"
                >
                    Continuar
                </BaseButton>
            </div>
        </Form>
    </Card>
</template>
