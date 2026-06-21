<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        if (empty($tableNames)) {
            // Fallback defaults if config is not cached/loaded properly yet
            $tableNames = [
                'roles' => 'roles',
                'permissions' => 'permissions',
                'model_has_permissions' => 'model_has_permissions',
                'model_has_roles' => 'model_has_roles',
                'role_has_permissions' => 'role_has_permissions',
            ];
        }

        // 1. Upgrade 'permissions' table: add guard_name and make slug nullable if they exist
        if (Schema::hasTable($tableNames['permissions'])) {
            Schema::table($tableNames['permissions'], function (Blueprint $table) use ($tableNames) {
                if (!Schema::hasColumn($tableNames['permissions'], 'guard_name')) {
                    $table->string('guard_name')->default('web');
                }
                if (Schema::hasColumn($tableNames['permissions'], 'slug')) {
                    $table->string('slug')->nullable()->change();
                }
            });
            DB::table($tableNames['permissions'])->whereNull('guard_name')->orWhere('guard_name', '')->update(['guard_name' => 'web']);
        } else {
            Schema::create($tableNames['permissions'], function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->string('slug')->nullable();
                $table->timestamps();
            $table->softDeletes();
                $table->unique(['name', 'guard_name']);
            });
        }

        // 2. Upgrade 'roles' table: add guard_name and make slug nullable if they exist
        if (Schema::hasTable($tableNames['roles'])) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($tableNames) {
                if (!Schema::hasColumn($tableNames['roles'], 'guard_name')) {
                    $table->string('guard_name')->default('web');
                }
                if (Schema::hasColumn($tableNames['roles'], 'slug')) {
                    $table->string('slug')->nullable()->change();
                }
            });
            DB::table($tableNames['roles'])->whereNull('guard_name')->orWhere('guard_name', '')->update(['guard_name' => 'web']);
        } else {
            Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams, $columnNames) {
                $table->id();
                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key'] ?? 'team_id')->nullable();
                    $table->index($columnNames['team_foreign_key'] ?? 'team_id', 'roles_team_foreign_key_index');
                }
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->string('slug')->nullable();
                $table->timestamps();
            $table->softDeletes();
                if ($teams) {
                    $table->unique([$columnNames['team_foreign_key'] ?? 'team_id', 'name', 'guard_name']);
                } else {
                    $table->unique(['name', 'guard_name']);
                }
            });
        }

        // 3. Create 'model_has_permissions' if missing
        if (!Schema::hasTable($tableNames['model_has_permissions'])) {
            Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
                $table->unsignedBigInteger($pivotPermission);
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key'] ?? 'model_id');
                $table->index([$columnNames['model_morph_key'] ?? 'model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

                $table->foreign($pivotPermission)
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->cascadeOnDelete();

                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key'] ?? 'team_id');
                    $table->index($columnNames['team_foreign_key'] ?? 'team_id', 'model_has_permissions_team_foreign_key_index');
                    $table->primary([$columnNames['team_foreign_key'] ?? 'team_id', $pivotPermission, $columnNames['model_morph_key'] ?? 'model_id', 'model_type'],
                        'model_has_permissions_permission_model_type_primary');
                } else {
                    $table->primary([$pivotPermission, $columnNames['model_morph_key'] ?? 'model_id', 'model_type'],
                        'model_has_permissions_permission_model_type_primary');
                }
            });

            // Migrate data from legacy users_permissions
            if (Schema::hasTable('users_permissions')) {
                $legacyPermissions = DB::table('users_permissions')->get();
                foreach ($legacyPermissions as $legacy) {
                    DB::table($tableNames['model_has_permissions'])->insertOrIgnore([
                        $pivotPermission => $legacy->permission_id,
                        'model_type' => 'App\Models\User',
                        $columnNames['model_morph_key'] ?? 'model_id' => $legacy->user_id,
                    ]);
                }
            }
        }

        // 4. Create 'model_has_roles' if missing
        if (!Schema::hasTable($tableNames['model_has_roles'])) {
            Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
                $table->unsignedBigInteger($pivotRole);
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key'] ?? 'model_id');
                $table->index([$columnNames['model_morph_key'] ?? 'model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

                $table->foreign($pivotRole)
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->cascadeOnDelete();

                if ($teams) {
                    $table->unsignedBigInteger($columnNames['team_foreign_key'] ?? 'team_id');
                    $table->index($columnNames['team_foreign_key'] ?? 'team_id', 'model_has_roles_team_foreign_key_index');
                    $table->primary([$columnNames['team_foreign_key'] ?? 'team_id', $pivotRole, $columnNames['model_morph_key'] ?? 'model_id', 'model_type'],
                        'model_has_roles_role_model_type_primary');
                } else {
                    $table->primary([$pivotRole, $columnNames['model_morph_key'] ?? 'model_id', 'model_type'],
                        'model_has_roles_role_model_type_primary');
                }
            });

            // Migrate data from legacy users_roles
            if (Schema::hasTable('users_roles')) {
                $legacyRoles = DB::table('users_roles')->get();
                foreach ($legacyRoles as $legacy) {
                    DB::table($tableNames['model_has_roles'])->insertOrIgnore([
                        $pivotRole => $legacy->role_id,
                        'model_type' => 'App\Models\User',
                        $columnNames['model_morph_key'] ?? 'model_id' => $legacy->user_id,
                    ]);
                }
            }
        }

        // 5. Create 'role_has_permissions' if missing
        if (!Schema::hasTable($tableNames['role_has_permissions'])) {
            Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);

                $table->foreign($pivotPermission)
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->cascadeOnDelete();

                $table->foreign($pivotRole)
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->cascadeOnDelete();

                $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
            });

            // Migrate data from legacy roles_permissions
            if (Schema::hasTable('roles_permissions')) {
                $legacyRolePermissions = DB::table('roles_permissions')->get();
                foreach ($legacyRolePermissions as $legacy) {
                    DB::table($tableNames['role_has_permissions'])->insertOrIgnore([
                        $pivotPermission => $legacy->permission_id,
                        $pivotRole => $legacy->role_id,
                    ]);
                }
            }
        }

        // Drop legacy pivot tables to clean up
        Schema::dropIfExists('users_roles');
        Schema::dropIfExists('users_permissions');
        Schema::dropIfExists('roles_permissions');

        // Clear Spatie permissions cache safely
        try {
            app('cache')
                ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
                ->forget(config('permission.cache.key'));
        } catch (\Throwable $e) {
            // Ignore cache clearing errors during migration if cache driver is not configured
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        if (empty($tableNames)) {
            $tableNames = [
                'roles' => 'roles',
                'permissions' => 'permissions',
                'model_has_permissions' => 'model_has_permissions',
                'model_has_roles' => 'model_has_roles',
                'role_has_permissions' => 'role_has_permissions',
            ];
        }

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);

        // Recreate legacy tables if rolling back
        Schema::create('users_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['user_id','role_id']);
        });

        Schema::create('users_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['user_id','permission_id']);
        });

        Schema::create('roles_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['role_id','permission_id']);
        });

        if (Schema::hasTable($tableNames['roles'])) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($tableNames) {
                if (Schema::hasColumn($tableNames['roles'], 'guard_name')) {
                    $table->dropColumn('guard_name');
                }
            });
        }

        if (Schema::hasTable($tableNames['permissions'])) {
            Schema::table($tableNames['permissions'], function (Blueprint $table) use ($tableNames) {
                if (Schema::hasColumn($tableNames['permissions'], 'guard_name')) {
                    $table->dropColumn('guard_name');
                }
            });
        }
    }
};
