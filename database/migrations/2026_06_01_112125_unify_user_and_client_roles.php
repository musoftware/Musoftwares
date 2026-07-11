<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure a 'client' role exists
        $clientRole = DB::table('roles')
            ->whereIn('name', ['Client', 'client'])
            ->where('guard_name', 'web')
            ->first();

        if (! $clientRole) {
            $clientRoleId = DB::table('roles')->insertGetId([
                'name' => 'client',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $clientRoleId = $clientRole->id;
            // Optionally standardize to lowercase 'client'
            DB::table('roles')->where('id', $clientRoleId)->update(['name' => 'client']);
        }

        // 2. Find any 'user' roles
        $userRoles = DB::table('roles')
            ->whereIn('name', ['User', 'user'])
            ->where('guard_name', 'web')
            ->get();

        if ($userRoles->isNotEmpty()) {
            foreach ($userRoles as $userRole) {
                // Get all users who have the 'user' role
                $userRoleAssignments = DB::table('model_has_roles')
                    ->where('role_id', $userRole->id)
                    ->get();

                foreach ($userRoleAssignments as $assignment) {
                    // Check if they already have the 'client' role
                    $hasClientRole = DB::table('model_has_roles')
                        ->where('role_id', $clientRoleId)
                        ->where('model_id', $assignment->model_id)
                        ->where('model_type', $assignment->model_type)
                        ->exists();

                    if (! $hasClientRole) {
                        // Assign 'client' role
                        DB::table('model_has_roles')->insert([
                            'role_id' => $clientRoleId,
                            'model_id' => $assignment->model_id,
                            'model_type' => $assignment->model_type,
                        ]);
                    }

                    // Remove the 'user' role assignment
                    DB::table('model_has_roles')
                        ->where('role_id', $userRole->id)
                        ->where('model_id', $assignment->model_id)
                        ->where('model_type', $assignment->model_type)
                        ->delete();
                }

                // Finally delete the 'user' role itself
                DB::table('roles')->where('id', $userRole->id)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this perfectly is difficult without losing the distinction,
        // but we could recreate the 'user' role if needed.
    }
};
