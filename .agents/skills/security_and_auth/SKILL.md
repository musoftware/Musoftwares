---
name: security_and_auth
description: Enforces strict route, middleware, and component-level authorization using spatie/laravel-permission, along with standard Auth scaffolding (Breeze, Sanctum). Includes strict git workflow rules for AI agents.
---

# Security and Authentication Guidelines

This skill defines the standard practices for implementing security, authentication, and authorization within the Musoftwares project. It also enforces a strict Git workflow that all AI agents must follow when contributing to the codebase.

## 1. Authentication Scaffolding

- **Standard Auth:** Use Laravel Breeze for standard session-based authentication workflows.
- **API/Token Auth:** Use Laravel Sanctum for API token authentication or when interacting with external services requiring stateless auth.
- **Social Auth:** Use Laravel Socialite for third-party logins (e.g., Google, GitHub).

## 2. Authorization (spatie/laravel-permission)

We use `spatie/laravel-permission` for all role and permission management.

### Route & Middleware Level
- All protected routes must be secured using middleware.
- Use the `role`, `permission`, or `role_or_permission` middlewares provided by Spatie to protect routes.
- Group routes by permission or role where applicable.

```php
Route::group(['middleware' => ['role:admin']], function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index']);
});

Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:view reports');
```

### Component Level (Frontend - React/Inertia)
- Authorization checks must be passed down to the frontend via Inertia shared data or specific controller props.
- UI elements (buttons, links, sections) must be hidden or disabled if the user lacks the required roles or permissions.
- **Do not rely solely on frontend checks.** Always enforce the same check on the backend route or controller.

```tsx
// Example passing permissions via Inertia
const { auth } = usePage().props;
const canEdit = auth.user.permissions.includes('edit post');

{canEdit && <Button>Edit Post</Button>}
```

### Controller Level
- For fine-grained access control within controllers, use the `authorize` method or gate checks.

```php
public function update(Request $request, Post $post)
{
    $this->authorize('edit', $post);
    // ...
}
```

## 3. General Security Practices

- **Mass Assignment:** Protect models against mass assignment vulnerabilities by defining `$fillable` or `$guarded` properties.
- **Validation:** Always validate incoming request data using Form Requests. Never trust user input.
- **XSS & CSRF:** Leverage Laravel's built-in CSRF protection for web routes. Ensure React properly escapes output (which it does by default) to prevent XSS.

---

## 🛑 STRICT GIT WORKFLOW RULES FOR AI AGENTS 🛑

All future AI agents working on this project MUST adhere to the following Git workflow:

1. **Branching:** Never work directly on `main` or `master`. Every new feature, bug fix, or task MUST be developed in its own isolated branch.
   - Example: `feature/user-onboarding`, `fix/login-bug`, `chore/update-deps`
2. **Commit Frequency:** Commit your changes after *every* meaningful modification. Do not bundle massive changes into a single commit.
3. **Commit Messages:** Use clear, descriptive commit messages following the Conventional Commits specification (e.g., `feat: added role-based route protection`, `fix: resolved token mismatch issue`).
4. **No Direct Pushes to Main:** Ensure that all changes go through a Pull Request or are reviewed before merging into the main branch.
5. **Context Isolation:** Keep the scope of your branch strictly to the feature or fix assigned to you. Do not mix unrelated changes.

*Note: For the creation of this specific skill file, the direct commit exception was applied as instructed.*
