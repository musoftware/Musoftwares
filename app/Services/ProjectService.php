<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectAuditLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectService extends BaseService
{
    public const PROJECT_WRITEABLE = [
        'user_id',
        'owner_id',
        'project_name',
        'description',
        'budget',
        'hour_rate',
        'percentage',
        'status',
        'date_start',
        'date_end',
        'hide_future_tasks',
    ];

    public function __construct(
        protected ProjectAuditService $audit,
    ) {}

    public function createProject(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            $project = new Project;
            $this->fillFromArray($project, $data);
            $project->status ??= 'open';
            $project->archived = 0;
            $project->archived_at = null;
            $project->save();

            $this->audit->logFromRequest(
                project: $project,
                action: ProjectAuditLog::ACTION_CREATED,
                changes: ['after' => $project->only(self::PROJECT_WRITEABLE)],
            );

            return $project->fresh(['client', 'owner']);
        });
    }

    public function updateProject(int $id, array $data): Project
    {
        return DB::transaction(function () use ($id, $data) {
            $project = Project::findOrFail($id);
            $before = $project->only(self::PROJECT_WRITEABLE);

            $this->fillFromArray($project, $data);
            $project->save();

            $after = $project->fresh()->only(self::PROJECT_WRITEABLE);
            $diff = $this->diffArrays($before, $after);

            if ($diff !== []) {
                $this->audit->logFromRequest(
                    project: $project,
                    action: ProjectAuditLog::ACTION_UPDATED,
                    changes: ['before' => $before, 'after' => $after, 'changed' => array_keys($diff)],
                );
            }

            return $project->fresh(['client', 'owner']);
        });
    }

    public function archiveProject(int $id): Project
    {
        return DB::transaction(function () use ($id) {
            $project = Project::findOrFail($id);
            $project->archived = 1;
            $project->archived_at = Carbon::now();
            $project->save();

            $this->audit->logFromRequest(
                project: $project,
                action: ProjectAuditLog::ACTION_ARCHIVED,
            );

            return $project;
        });
    }

    public function restoreProject(int $id): Project
    {
        return DB::transaction(function () use ($id) {
            $project = Project::findOrFail($id);
            $project->archived = 0;
            $project->archived_at = null;
            $project->save();

            $this->audit->logFromRequest(
                project: $project,
                action: ProjectAuditLog::ACTION_RESTORED,
            );

            return $project;
        });
    }

    public function deleteProject(int $id): void
    {
        DB::transaction(function () use ($id) {
            $project = Project::findOrFail($id);
            $project->delete();
        });
    }

    /**
     * Bulk-archive many projects in a single transaction. Returns the count actually
     * affected (skips projects that were already archived).
     */
    public function bulkArchive(Collection $ids): int
    {
        return DB::transaction(function () use ($ids) {
            $projects = Project::whereIn('id', $ids)->where('archived', 0)->get();
            $now = Carbon::now();
            foreach ($projects as $project) {
                $project->archived = 1;
                $project->archived_at = $now;
                $project->save();
                $this->audit->logFromRequest(
                    project: $project,
                    action: ProjectAuditLog::ACTION_BULK_ARCHIVED,
                );
            }

            return $projects->count();
        });
    }

    public function bulkRestore(Collection $ids): int
    {
        return DB::transaction(function () use ($ids) {
            $projects = Project::whereIn('id', $ids)->where('archived', 1)->get();
            foreach ($projects as $project) {
                $project->archived = 0;
                $project->archived_at = null;
                $project->save();
                $this->audit->logFromRequest(
                    project: $project,
                    action: ProjectAuditLog::ACTION_BULK_RESTORED,
                );
            }

            return $projects->count();
        });
    }

    public function bulkDelete(Collection $ids): int
    {
        return DB::transaction(function () use ($ids) {
            $count = 0;
            $projects = Project::whereIn('id', $ids)->get();
            foreach ($projects as $project) {
                $this->audit->logFromRequest(
                    project: $project,
                    action: ProjectAuditLog::ACTION_BULK_DELETED,
                );
                $project->delete();
                $count++;
            }

            return $count;
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    public function exportRows(iterable $projects, array $columns): array
    {
        $rows = [];
        foreach ($projects as $project) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $this->resolveExportValue($project, $column);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    protected function fillFromArray(Project $project, array $data): void
    {
        foreach (self::PROJECT_WRITEABLE as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];

            if (in_array($field, ['date_start', 'date_end'], true)) {
                $value = $value ? Carbon::parse($value) : null;
            }

            if ($field === 'hide_future_tasks' && $value !== null) {
                $value = (bool) $value;
            }

            $project->{$field} = $value;
        }
    }

    protected function diffArrays(array $before, array $after): array
    {
        $diff = [];
        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;
            if ($oldValue != $newValue) {
                $diff[$key] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        return $diff;
    }

    protected function resolveExportValue(Project $project, string $column): string
    {
        return match ($column) {
            'id' => (string) $project->id,
            'client' => (string) ($project->client?->name ?? ''),
            'owner' => (string) ($project->owner?->name ?? ''),
            'status' => (string) ($project->status ?? ''),
            'archived' => $project->archived ? 'yes' : 'no',
            'date_start' => $project->date_start?->toDateString() ?? '',
            'date_end' => $project->date_end?->toDateString() ?? '',
            'archived_at' => $project->archived_at?->toIso8601String() ?? '',
            'created_at' => $project->created_at?->toIso8601String() ?? '',
            'description' => (string) ($project->description ?? ''),
            'budget', 'total_paid', 'hour_rate' => (string) ($project->{$column} ?? '0'),
            'cost' => (string) $project->costAmount(),
            'paid_invoices' => (string) $project->paidInvoicesAmount(),
            'pending_invoices' => (string) $project->pendingInvoicesAmount(),
            default => (string) ($project->{$column} ?? ''),
        };
    }
}
