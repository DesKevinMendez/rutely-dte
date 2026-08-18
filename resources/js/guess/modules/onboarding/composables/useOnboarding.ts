import { computed, reactive, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthUser } from '@/core/composables/useAuthUser';
import type { OnboardingForm } from '../types/Onboarding';

export default function useOnboarding() {
    const router = useRouter();
    const { clearSession } = useAuthUser();

    const form = reactive<OnboardingForm>({
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
        await router.push({ name: 'dashboard' });
    };

    const logout = async (): Promise<void> => {
        clearSession();
        await router.push({ name: 'login' });
    };

    return {
        form,
        municipalityUrl,
        districtUrl,
        continueFlow,
        logout,
    };
}
