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
        'show_on_landing_portfolio',
        'portfolio_category',
        'portfolio_title',
        'portfolio_description',
        'portfolio_tech',
        'portfolio_live_url',
        'portfolio_github_url',
        'portfolio_sort_order',
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

            if (isset($data['portfolio_image_file'])) {
                $this->handlePortfolioImageUpload($project, $data['portfolio_image_file']);
            }

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

            if (isset($data['portfolio_image_file'])) {
                $this->handlePortfolioImageUpload($project, $data['portfolio_image_file']);
            }

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

            if (in_array($field, ['hide_future_tasks', 'show_on_landing_portfolio'], true) && $value !== null) {
                $value = (bool) $value;
            }

            $project->{$field} = $value;
        }
    }

    protected function handlePortfolioImageUpload(Project $project, $imageFile): void
    {
        if (!$imageFile || !$imageFile->isValid()) {
            return;
        }

        // Generate unique name
        $extension = $imageFile->getClientOriginalExtension() ?: 'png';
        $filename = uniqid('portfolio_', true);
        
        // Paths
        $originalFolder = public_path('storage/portfolio/original');
        $croppedFolder = public_path('storage/portfolio/cropped');

        if (!is_dir($originalFolder)) {
            mkdir($originalFolder, 0755, true);
        }
        if (!is_dir($croppedFolder)) {
            mkdir($croppedFolder, 0755, true);
        }

        $originalPath = $originalFolder . '/' . $filename . '.' . $extension;
        $croppedPath = $croppedFolder . '/' . $filename . '.webp';

        // Delete old files if they exist
        if ($project->portfolio_image) {
            $oldCropped = public_path(ltrim($project->portfolio_image, '/'));
            if (is_file($oldCropped)) {
                @unlink($oldCropped);
            }
        }
        if (is_array($project->portfolio_gallery) && count($project->portfolio_gallery) > 0) {
            foreach ($project->portfolio_gallery as $oldImg) {
                $oldOrig = public_path(ltrim($oldImg, '/'));
                if (is_file($oldOrig)) {
                    @unlink($oldOrig);
                }
            }
        }

        // Save original file
        $imageFile->move($originalFolder, $filename . '.' . $extension);

        // Crop & Resize the original file, save to cropped path
        $croppedSuccess = $this->cropAndResizeTopPortion($originalPath, $croppedPath);

        if ($croppedSuccess) {
            $project->portfolio_image = '/storage/portfolio/cropped/' . $filename . '.webp';
        } else {
            // Fallback: if crop failed, use the original image directly
            $project->portfolio_image = '/storage/portfolio/original/' . $filename . '.' . $extension;
        }

        // Save original screenshot in portfolio_gallery array
        $project->portfolio_gallery = ['/storage/portfolio/original/' . $filename . '.' . $extension];
    }

    protected function cropAndResizeTopPortion(string $filePath, string $outputPath): bool
    {
        try {
            $info = @getimagesize($filePath);
            if (!$info) {
                return false;
            }

            $mime = $info['mime'];
            switch ($mime) {
                case 'image/jpeg':
                    $src = @imagecreatefromjpeg($filePath);
                    break;
                case 'image/png':
                    $src = @imagecreatefrompng($filePath);
                    break;
                case 'image/webp':
                    $src = @imagecreatefromwebp($filePath);
                    break;
                case 'image/gif':
                    $src = @imagecreatefromgif($filePath);
                    break;
                default:
                    $src = @imagecreatefromstring(file_get_contents($filePath));
                    break;
            }

            if (!$src) {
                return false;
            }

            $W = imagesx($src);
            $H = imagesy($src);

            // Target aspect ratio is 1.5 (3:2)
            $cropWidth = $W;
            $cropHeight = (int)($W / 1.5);
            if ($cropHeight > $H) {
                $cropHeight = $H;
            }

            // Target dimensions for thumbnail
            $targetWidth = 800;
            $targetHeight = (int)($targetWidth * ($cropHeight / $cropWidth));

            $dst = imagecreatetruecolor($targetWidth, $targetHeight);

            // Preserve transparency for PNG/WEBP
            imagealphablending($dst, false);
            imagesavealpha($dst, true);

            imagecopyresampled(
                $dst,
                $src,
                0, 0, // Destination X, Y
                0, 0, // Source X, Y (starts from top)
                $targetWidth, $targetHeight,
                $cropWidth, $cropHeight
            );

            // Save cropped image as WebP for optimal page loading
            $saved = imagewebp($dst, $outputPath, 85);

            imagedestroy($src);
            imagedestroy($dst);

            return $saved;
        } catch (\Throwable $e) {
            logger()->error('Error cropping portfolio image: ' . $e->getMessage());
            return false;
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
