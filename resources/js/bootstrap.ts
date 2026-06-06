import axios from 'axios';
import { __ } from '@/lib/i18n';

window.axios = axios;
window.__ = __;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Request Interceptor
axios.interceptors.request.use((config) => {
    // Set up a timeout for long requests (10s)
    const source = axios.CancelToken.source();
    config.cancelToken = source.token;

    (config as any).__timeoutId = setTimeout(() => {
        window.dispatchEvent(new Event('app:long-request'));
    }, 10000);

    return config;
}, (error) => {
    return Promise.reject(error);
});

// Response Interceptor
axios.interceptors.response.use(
    (response) => {
        if ((response.config as any).__timeoutId) {
            clearTimeout((response.config as any).__timeoutId);
        }
        return response;
    },
    (error) => {
        if (error.config && (error.config as any).__timeoutId) {
            clearTimeout((error.config as any).__timeoutId);
        }

        if (axios.isCancel(error) || error.code === 'ERR_CANCELED' || error.name === 'AbortError' || error.message === 'canceled') {
            return Promise.reject(error);
        }

        if (!(error as any).response) {
            // Network error or intercepted request
            if (!navigator.onLine) {
                window.dispatchEvent(new Event('app:network-error'));
            } else if (error.message !== 'Network Error') {
                // If there's no response and it's not a generic Network Error (e.g., timeout), 
                // we might not want to falsely blame the internet.
                console.error("Request failed without response:", error.message);
            }
        } else {
            const status = (error as any).response.status;
            if (status === 500 || status === 503) {
                console.error("Server Error:", (error as any).response);
                window.dispatchEvent(new Event('app:server-error'));
            } else if (status === 401 || status === 419) {
                window.dispatchEvent(new Event('app:session-expired'));
            }
        }
        return Promise.reject(error);
    }
);

import './echo';
