import { StorageSerializers, useStorage } from '@vueuse/core';
import { defineStore } from 'pinia';
import type { AuthUser } from '@/core/types/auth.types';

export const useAuth = defineStore('auth', {
    state: () => ({
        token: useStorage<string | null>('auth_token', null),
        user: useStorage<AuthUser | null>('auth_user', null, undefined, {
            serializer: StorageSerializers.object,
        }),
        sessionValidated: false,
    }),
    getters: {
        isAuthenticated: (state): boolean => Boolean(state.token),
    },
    actions: {
        setSession(token: string, user: AuthUser): void {
            this.token = token;
            this.user = user;
            this.sessionValidated = true;
        },
        setUser(user: AuthUser): void {
            this.user = user;
            this.sessionValidated = true;
        },
        clearSession(): void {
            this.token = null;
            this.user = null;
            this.sessionValidated = false;

            if (typeof localStorage !== 'undefined') {
                localStorage.removeItem('company_id');
            }
        },
    },
});
