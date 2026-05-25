<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Workspaces
        if (!Schema::hasTable('crm_workspaces')) {
            Schema::create('crm_workspaces', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // The Billing Owner
                $table->string('name');
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Roles
        if (!Schema::hasTable('crm_roles')) {
            Schema::create('crm_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->nullable()->constrained('crm_workspaces')->cascadeOnDelete(); // Null for System Default Roles
                $table->string('name'); // Owner, Admin, Manager, Sales, Viewer
                $table->boolean('is_system')->default(false); // Can't be deleted or edited
                $table->timestamps();
            });
        }

        // 3. Permissions
        if (!Schema::hasTable('crm_permissions')) {
            Schema::create('crm_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // e.g. crm.leads.view
                $table->string('group')->nullable(); // e.g. Leads
                $table->timestamps();
            });
        }

        // 4. Role Permissions
        if (!Schema::hasTable('crm_role_permissions')) {
            Schema::create('crm_role_permissions', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained('crm_roles')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('crm_permissions')->cascadeOnDelete();
                $table->primary(['role_id', 'permission_id']);
            });
        }

        // 5. Workspace Users
        if (!Schema::hasTable('crm_workspace_users')) {
            Schema::create('crm_workspace_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('crm_workspaces')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('crm_roles')->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->unique(['workspace_id', 'user_id']);
            });
        }

        // 6. Add workspace_id to Leads, migrate data, then drop user_id
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'workspace_id')) {
                $table->foreignId('workspace_id')->nullable()->after('id')->constrained('crm_workspaces')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('leads', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('workspace_id')->constrained('users')->nullOnDelete();
            }
        });

        // Migrate Data
        $leads = DB::table('leads')->get();
        foreach ($leads as $lead) {
            if (isset($lead->user_id)) {
                // Find or create workspace for this user
                $workspace = DB::table('crm_workspaces')->where('user_id', $lead->user_id)->first();
                if (!$workspace) {
                    $workspaceId = DB::table('crm_workspaces')->insertGetId([
                        'user_id' => $lead->user_id,
                        'name' => 'Default Workspace',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $workspaceId = $workspace->id;
                }
                
                DB::table('leads')->where('id', $lead->id)->update([
                    'workspace_id' => $workspaceId,
                    'assigned_to' => $lead->user_id, // Default assign to owner
                ]);
            }
        }

        // Now drop the old user_id columns safely
        try {
            Schema::table('leads', function (Blueprint $table) {
                if (Schema::hasColumn('leads', 'user_id')) {
                    $table->dropColumn('user_id');
                }
                if (Schema::hasColumn('leads', 'assignable_type')) {
                    $table->dropMorphs('assignable');
                }
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('leads', function (Blueprint $table) {
                if (Schema::hasColumn('leads', 'workspace_id')) {
                    $table->foreignId('workspace_id')->nullable(false)->change();
                }
            });
        } catch (\Exception $e) {}

        // 7. Update Lead Notes Table
        try {
            Schema::table('lead_notes', function (Blueprint $table) {
                if (Schema::hasColumn('lead_notes', 'user_id')) {
                    $table->dropForeign(['user_id']);
                }
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('lead_notes', function (Blueprint $table) {
                if (Schema::hasColumn('lead_notes', 'user_id')) {
                    $table->dropColumn('user_id');
                }
                if (Schema::hasColumn('lead_notes', 'authorable_type')) {
                    $table->dropMorphs('authorable');
                }
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('lead_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('lead_notes', 'workspace_id')) {
                    $table->foreignId('workspace_id')->after('id')->constrained('crm_workspaces')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('lead_notes', 'author_id')) {
                    $table->foreignId('author_id')->nullable()->after('lead_id')->constrained('users')->nullOnDelete();
                }
            });
        } catch (\Exception $e) {}

        // 8. Update Lead Tags Pivot Table
        try {
            Schema::table('lead_tags', function (Blueprint $table) {
                if (Schema::hasColumn('lead_tags', 'user_id')) {
                    $table->dropForeign(['user_id']);
                }
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('lead_tags', function (Blueprint $table) {
                if (Schema::hasColumn('lead_tags', 'user_id')) {
                    $table->dropColumn('user_id');
                }
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('lead_tags', function (Blueprint $table) {
                if (!Schema::hasColumn('lead_tags', 'workspace_id')) {
                    $table->foreignId('workspace_id')->after('id')->constrained('crm_workspaces')->cascadeOnDelete();
                }
            });
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_workspace_users');
        Schema::dropIfExists('crm_role_permissions');
        Schema::dropIfExists('crm_permissions');
        Schema::dropIfExists('crm_roles');
        Schema::dropIfExists('crm_workspaces');
    }
};
