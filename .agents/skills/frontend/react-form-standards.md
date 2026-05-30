# React Form Standards

## Rules
1. Always use `@inertiajs/react` `useForm`.
2. Handle loading states by disabling the submit button (`processing` flag).
3. Display validation errors inline below fields using `<InputError message={errors.field} />`.
