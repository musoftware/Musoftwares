<?php

namespace Modules\AppBuilder\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\AppBuilder\Http\Requests\ImportProjectRequest;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\ProjectImports;

/** The owner brings in an existing project for a customer to keep changing, and releases its changes when they choose. */
class AdminImportController extends Controller
{
    public function __construct(private readonly ProjectImports $imports) {}

    public function store(ImportProjectRequest $request): RedirectResponse
    {
        $project = $this->imports->import($request->validated() + ['deploy_enabled' => $request->boolean('deploy_enabled')], (string) $request->ip());

        return redirect()->route('admin.builder.show', $project)->with('success', __('builder.admin.import_started'));
    }

    /** Pushes and deploys the waiting changes now, whatever the project's release rule says. */
    public function release(BuilderProject $project): RedirectResponse
    {
        abort_unless($this->imports->queueRelease($project), 409);

        return back()->with('success', __('builder.admin.release_started'));
    }
}
