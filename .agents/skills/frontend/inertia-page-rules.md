# Inertia Page Rules

## Rules
1. Export pages as default: `export default function PageName() { ... }`
2. Wrap pages in `AuthenticatedLayout` or `ERPLayout`.
3. Define TypeScript interfaces for `props`.
4. Flash messages are handled globally; do not render raw flash alerts inside the page body.
