import { Config, RouteParams } from 'ziggy-js';

export interface ZiggyRouter {
    current(): string | undefined;
    current(name?: string, params?: any): boolean;
    has(name: string): boolean;
    get params(): Record<string, string>;
    get routeParams(): Record<string, string>;
}

declare global {
    function route(): ZiggyRouter;
    function route(
        name: string,
        params?: RouteParams<string> | number | string | Record<string, any> | undefined,
        absolute?: boolean,
        customConfig?: Config,
    ): string;
}

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        route: typeof route;
    }
}
