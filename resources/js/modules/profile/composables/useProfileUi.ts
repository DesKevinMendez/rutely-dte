import { computed } from 'vue';
import { formatSalvadoranPhone } from '@/core/composables/formatSalvadoranPhone';
import { useAuth } from '@/core/stores/auth';
import type { ProfileSummary } from '../types/profile.types';

export function useProfileUi() {
    const auth = useAuth();
    const isAdmin = computed(
        () => auth.user?.roles.some((role) => role.name === 'admin') ?? false,
    );
    const companyName = computed(() => auth.user?.company?.name ?? null);

    const profile = computed<ProfileSummary>(() => {
        const name = auth.user?.name ?? 'Usuario';
        const words = name.trim().split(/\s+/).filter(Boolean);
        const initials =
            words
                .slice(0, 2)
                .map((word) => word.charAt(0).toUpperCase())
                .join('') || 'U';
        const roleName = auth.user?.roles[0]?.name;
        const role =
            roleName === 'admin'
                ? 'Admin'
                : (roleName ?? 'Usuario').replace(/^./, (letter) =>
                      letter.toUpperCase(),
                  );

        return {
            name,
            initials,
            role,
            email: auth.user?.email ?? '—',
            phone: auth.user?.phone
                ? formatSalvadoranPhone(auth.user.phone)
                : '—',
        };
    });

    return { profile, isAdmin, companyName };
}
