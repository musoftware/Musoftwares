<?php

namespace Modules\AppBuilder\Services;

use App\Models\User;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * The first simple page of a signed-in customer is built free, so people try us before they pay. One per account,
 * only for the small project types, and its build has a hard spending cap.
 */
class FreeBuild
{
    /** The open quote is the kind a free build covers (whoever is looking). */
    public function covers(BuilderProject $project): bool
    {
        return config('builder.free_build.enabled')
            && $project->status === BuilderProject::QUOTED
            && ! $project->free_fix
            && in_array($project->project_type, (array) config('builder.free_build.types'), true);
    }

    /** This signed-in customer can take it: the quote is covered and they never had a free build. */
    public function availableTo(BuilderProject $project, ?User $user): bool
    {
        return $user !== null && $this->covers($project) && ! $this->alreadyUsed($user);
    }

    private function alreadyUsed(User $user): bool
    {
        return BuilderProject::where('user_id', $user->id)->where('free_build', true)->exists();
    }
}
