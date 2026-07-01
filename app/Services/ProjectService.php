<?php

namespace App\Services;

use App\Models\Project;

class ProjectService extends BaseService
{
    public function createProject(array $data): Project
    {
        $project = new Project;
        $project->user_id = $data['user_id'];
        $project->project_name = $data['project_name'];
        if (isset($data['project_balance'])) {
            $project->project_balance = $data['project_balance'];
        }
        $project->status = 'open';
        $project->archived = 0;
        $project->save();

        return $project;
    }

    public function updateProject(int $id, array $data): Project
    {
        $project = Project::findOrFail($id);
        if (isset($data['project_name'])) {
            $project->project_name = $data['project_name'];
        }
        if (isset($data['project_balance'])) {
            $project->project_balance = $data['project_balance'];
        }
        if (array_key_exists('budget', $data) && $data['budget'] !== null) {
            $project->budget = $data['budget'];
        }
        if (array_key_exists('status', $data) && $data['status'] !== null) {
            $project->status = $data['status'];
        }
        if (array_key_exists('hide_future_tasks', $data) && $data['hide_future_tasks'] !== null) {
            $project->hide_future_tasks = (bool) $data['hide_future_tasks'];
        }
        $project->save();

        return $project;
    }

    public function archiveProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->archived = 1;
        $project->save();
    }

    public function restoreProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->archived = 0;
        $project->save();
    }

    public function deleteProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->delete();
    }
}
