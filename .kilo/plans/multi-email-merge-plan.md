# Multi-Email Aliases + Multi-Duplicate Account Merge

## Goal
Admins can:
1. Add **multiple email aliases** to any user account so the user can log in with any of those emails.
2. **Merge multiple duplicate accounts at once** into a primary (existing flow currently supports only ONE duplicate per run).
3. After merge, the duplicate's email is **automatically preserved as an alias** on the survivor — no need for admins to add it manually.

## Decisions (locked)
| Decision | Value |
|---|---|
| Aliases storage | New `user_emails` table, FK `users.id`, unique by email (lowercase) |
| Login alias lookup | At authenticate-time: look up `users.email` first, then `user_emails.email`; both must have `verified_at` populated when `MustVerifyEmail` is enforced |
| Merge UI | Multi-select checkbox list (existing prompt() is replaced) |
| Merge batch size | No hard limit; tested with 5+ duplicates |
| Post-merge duplicate email | Auto-promoted to verified alias on survivor |
| Audit | Existing `AdminAuditLog` action `users.merged` extended with `duplicate_ids[]` array |

## Affected files

### NEW
- `database/migrations/2026_07_08_120000_create_user_emails_table.php`
- `app/Models/UserEmail.php`
- `app/Http/Controllers/Admin/UserEmailController.php`
- `app/Http/Requests/Admin/User/StoreUserEmailRequest.php`
- `app/Http/Requests/Admin/User/DestroyUserEmailRequest.php`
- `resources/js/Pages/Admin/Users/UserEmails.jsx` (admin aliases section)
- `resources/js/Pages/Admin/Users/Merge.jsx` (rewrite — multi-select)
- `tests/Feature/UserEmailAliasTest.php`
- `tests/Feature/UserMergeMultiTest.php` (extends existing UserMergeTest)

### MODIFIED
- `app/Models/User.php` — add `emails()` relation + `findForLogin()` helper
- `app/Http/Requests/Auth/LoginRequest.php` — support alias lookup
- `app/Services/UserMergeService.php` — accept array of duplicate ids; auto-add alias; method aliases: `merge(int $survivorId, array $duplicateIds, array $resolutions, int $adminId)` (keep old signature as wrapper)
- `app/Http/Controllers/Admin/UserMergeController.php` — accept `duplicate_ids[]`
- `app/Http/Controllers/Admin/ClientActionsSheet.jsx` — replace `window.prompt` flow with multi-select modal route
- `routes/web.php` — alias routes under admin prefix
- `app/Console/Commands/MergeUsersCommand.php` — accept comma-separated duplicates

## Steps

### 1. Schema
```php
Schema::create('user_emails', function (Blueprint $t) {
    $t->id();
    $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $t->string('email')->unique();                 // unique across the whole table
    $t->timestamp('verified_at')->nullable();
    $t->string('source', 32)->default('admin');    // admin|merge|self
    $t->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $t->timestamps();
});
```
We keep `users.email` as the canonical primary column. Aliases are extras.

### 2. Models
- `UserEmail::user()` BelongsTo
- `User::emails()` HasMany
- `User::findForLogin(string $email): ?User` — queries `users` then `user_emails` (returning the owner). Cached for the duration of the request only.

### 3. Authentication flow (`LoginRequest::authenticate`)
Replace `Auth::attempt($this->only('email','password'), ...)` with:
```php
$user = User::findForLogin($this->input('email'));
if (!$user || !Hash::check($this->input('password'), $user->password)) {
    // throw same ValidationException as before
}
Auth::login($user, $this->boolean('remember'));
```
Rate-limit key stays the typed email string (not the resolved user), so attackers can't enumerate which aliases exist.

### 4. Merge service — multi-duplicate
```php
public function merge(int $survivorId, array $duplicateIds, array $resolutions, int $adminId): array
```
Returns a per-duplicate outcome (one row per duplicate). Internals loop over old logic per duplicate, all wrapped in ONE outer transaction. After each duplicate soft-delete, add its `email` as a `UserEmail` row on the survivor (skip if survivor already has that email as primary or alias).

Backward compatibility: add `mergeOne(int $sId, int $dId, ...)` thin wrapper calling the new method with `[$dId]`.

### 5. Routes
```php
Route::prefix('users/{user}')->group(function () {
    Route::get   ('emails',            [UserEmailController::class, 'index'])->name('users.emails.index');
    Route::post  ('emails',            [UserEmailController::class, 'store'])->name('users.emails.store');
    Route::delete('emails/{email}',    [UserEmailController::class, 'destroy'])->name('users.emails.destroy');
    Route::post  ('emails/{email}/resend-verification', [UserEmailController::class, 'resendVerification'])->name('users.emails.resend');
});
```
Update existing merge routes to use `duplicate_ids[]`.

### 6. UI
- New `UserEmails.jsx` accessible from admin users show page (link in the email card showing primary + N aliases).
- `Merge.jsx` rebuild: list of duplicate users, multi-select, single submit.

### 7. Tests
- `test_login_works_with_alias_email`
- `test_login_throttles_typed_email_not_resolved_user`
- `test_admin_can_add_alias`
- `test_alias_email_must_be_unique`
- `test_merge_with_multiple_duplicates_reassigns_all_and_creates_aliases`
- `test_merge_auto_promotes_duplicate_email_to_alias_on_survivor`
- Update existing `UserMergeTest` if old signature changed.

## Risks
- **Existing users with shared emails**: `user_emails.email` UNIQUE may collide with `users.email` from different users. Mitigated by storing the alias lowercased and the migration already de-dups via exception path during seed (manual operator step noted in ops runbook).
- **Auth change**: every login path now goes through `User::findForLogin`. We must keep `auth()->user()` working in tests by setting the email correctly.
- **Existing single-duplicate merge callers**: `MergeUsersCommand` keeps `--duplicate` as comma-separated values.
