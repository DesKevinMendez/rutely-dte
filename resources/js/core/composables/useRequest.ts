import { ref } from 'vue';
import type { Ref } from 'vue';

interface RequestOptions<B = unknown> {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
    body?: B;
    headers?: Record<string, string>;
}

const getBaseUrl = (): string => {
    const envServer = import.meta.env.APP_SERVER;

    if (envServer) {
        return envServer.startsWith('http') ? envServer : `http://${envServer}`;
    }

    return '';
};

export function useRequest() {
    const data: Ref<unknown> = ref(null);
    const statusCode: Ref<number | null> = ref(null);
    const isLoading: Ref<boolean> = ref(false);
    const error: Ref<string | null> = ref(null);

    const makeRequest = async <T = unknown, B = unknown>(
        url: string,
        options: RequestOptions<B> = {}
    ): Promise<{ data: Ref<T | null>; statusCode: Ref<number | null>; isLoading: Ref<boolean>; error: Ref<string | null> }> => {
        const { method = 'GET', body, headers: customHeaders = {} } = options;
        const isFormData = typeof FormData !== 'undefined' && body instanceof FormData;
        const fullUrl = url.startsWith('http') ? url : `${getBaseUrl()}${url}`;

        isLoading.value = true;
        error.value = null;

        const headers: Record<string, string> = {
            'Accept': 'application/json',
            ...customHeaders,
        };

        const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;

        if (token && !headers['Authorization']) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const companyId = typeof localStorage !== 'undefined' ? localStorage.getItem('company_id') : null;

        if (companyId && !headers['X-Company-Id']) {
            headers['X-Company-Id'] = companyId;
        }

        if (!isFormData && body) {
            headers['Content-Type'] = 'application/json';
        }

        const requestOptions: RequestInit = {
            method,
            headers,
        };

        if (body && method !== 'GET') {
            requestOptions.body = isFormData ? (body as FormData) : JSON.stringify(body);
        }

        try {
            const response = await fetch(fullUrl, requestOptions);
            statusCode.value = response.status;

            let result: any = null;
            const contentType = response.headers.get('content-type');

            if (contentType && contentType.includes('application/json')) {
                result = await response.json();
            } else {
                result = await response.text();
            }

            if (!response.ok) {
                const errorMsg =
                    (typeof result === 'object' && result?.error) ||
                    (typeof result === 'object' && result?.message) ||
                    `HTTP Error ${response.status}: ${response.statusText}`;
                error.value = errorMsg;
                data.value = null;

                if (response.status === 401 && typeof window !== 'undefined') {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('company_id');
                }
            } else {
                data.value = result;
            }
        } catch (err: unknown) {
            error.value = err instanceof Error ? err.message : 'Error de conexión de red';
            data.value = null;
        } finally {
            isLoading.value = false;
        }

        return {
            data: data as Ref<T | null>,
            statusCode,
            isLoading,
            error,
        };
    };

    const get = <T = unknown>(url: string, headers?: Record<string, string>) =>
        makeRequest<T>(url, { method: 'GET', headers });

    const post = <T = unknown, B = unknown>(url: string, body?: B, headers?: Record<string, string>) =>
        makeRequest<T, B>(url, { method: 'POST', body, headers });

    const put = <T = unknown, B = unknown>(url: string, body?: B, headers?: Record<string, string>) =>
        makeRequest<T, B>(url, { method: 'PUT', body, headers });

    const del = <T = unknown>(url: string, headers?: Record<string, string>) =>
        makeRequest<T>(url, { method: 'DELETE', headers });

    return {
        data,
        statusCode,
        isLoading,
        error,
        makeRequest,
        get,
        post,
        put,
        del,
    };
}
