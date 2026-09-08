# Rule: Never Remove SoftDeletes or deleted_at Columns

## Strict Invariant
1. **Never Remove SoftDeletes**: Never remove `use SoftDeletes;` or the `Illuminate\Database\Eloquent\SoftDeletes` trait from any Eloquent model in this codebase under any circumstances.
2. **Never Drop deleted_at Columns**: Never remove or drop `deleted_at` columns from the database schema.
3. **Missing Column Resolution**: If an Eloquent model uses `SoftDeletes` and queries fail because `deleted_at` is missing from the database table (`SQLSTATE[42S22]: Column not found: 1054 Unknown column '...deleted_at'`), you MUST create and execute a database migration that adds `$table->softDeletes()` to the table. DO NOT remove `SoftDeletes` from the model to work around the error.
