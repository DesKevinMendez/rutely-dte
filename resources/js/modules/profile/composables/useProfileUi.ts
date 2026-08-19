import { computed } from 'vue';
import { useAuth } from '@/core/stores/auth';
import type { ProfileSummary } from '../types/profile.types';

export function useProfileUi() {
    const auth = useAuth();

    const profile = computed<ProfileSummary>(() => {
        const name = auth.user?.name ?? 'Usuario';
        const words = name.trim().split(/\s+/).filter(Boolean);
        const initials =
            words
                .slice(0, 2)
                .map((word) => word.charAt(0).toUpperCase())
                .join('') || 'U';
        const role =
            auth.user?.role === 'admin'
                ? 'Admin'
                : (auth.user?.role ?? 'Usuario').replace(/^./, (letter) =>
                      letter.toUpperCase(),
                  );

        return {
            name,
            initials,
            role,
            email: auth.user?.email ?? '—',
            phone: auth.user?.phone ?? '—',
        };
    });

    return { profile };
}
