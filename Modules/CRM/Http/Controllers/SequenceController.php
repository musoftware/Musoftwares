<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Sequence;
use Modules\CRM\Models\SequenceStep;
use Modules\CRM\Services\SequenceService;
use App\Http\Requests\Admin\Sequence\StoreSequenceRequest;
use App\Http\Requests\Admin\Sequence\StoreSequenceStepRequest;
use App\Http\Requests\Admin\Sequence\UpdateSequenceStepRequest;
use App\Http\Requests\Admin\Sequence\GenerateAISequenceRequest;
use App\Http\Requests\Admin\Sequence\ApplyGeneratedStepsRequest;
use App\Http\Resources\SequenceResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SequenceController extends Controller
{
    public function __construct(
        protected SequenceService $sequenceService
    ) {}
    public function index()
    {
        $sequences = Sequence::withCount(['steps', 'states'])->latest()->paginate(20)->through(fn($s) => (new SequenceResource($s))->resolve());
        return Inertia::render('CRM/Sequences/Index', [
            'sequences' => $sequences
        ]);
    }

    public function store(StoreSequenceRequest $request)
    {
        $this->sequenceService->createSequence($request->validated());
        return redirect()->back()->with('success', __('crm.sequence_created'));
    }

    public function show(Sequence $sequence)
    {
        $sequence->load('steps');
        return Inertia::render('CRM/Sequences/Show', [
            'sequence' => clone (new SequenceResource($sequence))->resolve()
        ]);
    }

    public function destroy(Sequence $sequence)
    {
        $this->sequenceService->deleteSequence($sequence);
        return redirect()->route('admin.sequences.index')->with('success', __('crm.sequence_deleted'));
    }

    // -- Steps Management --
    public function storeStep(StoreSequenceStepRequest $request, Sequence $sequence)
    {
        $this->sequenceService->addStep($sequence, $request->validated());
        return redirect()->back()->with('success', __('crm.step_added'));
    }

    public function updateStep(UpdateSequenceStepRequest $request, SequenceStep $step)
    {
        $this->sequenceService->updateStep($step, $request->validated());
        return redirect()->back()->with('success', __('crm.step_updated'));
    }

    public function deleteStep(SequenceStep $step)
    {
        $this->sequenceService->deleteStep($step);
        return redirect()->back()->with('success', __('crm.step_deleted'));
    }

    // -- AI Generation --
    public function generateStepsWithAI(GenerateAISequenceRequest $request, Sequence $sequence)
    {
        try {
            $steps = $this->sequenceService->generateStepsWithAI(
                $request->input('num_steps'),
                $request->input('context') ?? '',
                $request->input('tone')
            );
            return response()->json(['steps' => $steps]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function applyGeneratedSteps(ApplyGeneratedStepsRequest $request, Sequence $sequence)
    {
        $this->sequenceService->applyGeneratedSteps($sequence, $request->validated('steps'));

        return redirect()->back()->with('success', __('crm.ai_steps_applied'));
    }
}
