import { Config, RouteParam, RouteParamsWithQueryOverload } from 'ziggy-js';
import { __ } from '@/lib/i18n';

declare global {
    interface Window {
        Pusher: any;
        Echo: any;
        __: (key: string, replacements?: Record<string, string | number>) => string;
    }

    function route(
        name?: string,
        params?: RouteParamsWithQueryOverload | RouteParam,
        absolute?: boolean,
        config?: Config
    ): any;

    function __(key: string, replacements?: Record<string, string | number>): string;
}

export {};
