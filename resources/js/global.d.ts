import { Config, RouteParam, RouteParamsWithQueryOverload } from 'ziggy-js';

declare global {
    interface Window {
        Pusher: any;
        Echo: any;
    }

    function route(
        name?: string,
        params?: RouteParamsWithQueryOverload | RouteParam,
        absolute?: boolean,
        config?: Config
    ): any;
}

export {};
