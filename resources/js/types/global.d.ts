import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import { route as ziggyRoute } from 'ziggy-js';
import { PageProps as AppPageProps } from './';

declare global {
    /** Currency row shared from the backend through Inertia page props. */
    interface SharedCurrency {
        id: number | string;
        currency: string;
        [key: string]: unknown;
    }

    interface Window {
        axios: AxiosInstance;
        /** Set by app.tsx from page props; read by money formatting helpers. */
        currencies?: SharedCurrency[];
        /** Set by app.tsx from the wallet or base currency page props. */
        defaultCurrency?: string;
    }

    /* eslint-disable no-var */
    var route: typeof ziggyRoute;
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}
