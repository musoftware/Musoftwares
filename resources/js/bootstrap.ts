import axios from 'axios';

window.axios = axios;

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

        if (!error.response) {
            // Network error
            window.dispatchEvent(new Event('app:network-error'));
        } else {
            const status = error.response.status;
            if (status === 500 || status === 503) {
                console.error("Server Error:", error.response);
                window.dispatchEvent(new Event('app:server-error'));
            } else if (status === 401 || status === 419) {
                window.dispatchEvent(new Event('app:session-expired'));
            }
        }
        return Promise.reject(error);
    }
);

import './echo';
