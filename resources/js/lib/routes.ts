// A placeholder for route utilities, potentially wrapping ziggy-js
export function route(name: string, params?: unknown) {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    return (window as any).route(name, params);
}
