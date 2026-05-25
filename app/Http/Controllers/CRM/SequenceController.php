<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Sequence;
use Modules\CRM\Models\SequenceStep;
use App\Services\SequenceService;
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
        return redirect()->back()->with('success', 'Sequence created successfully.');
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
        return redirect()->route('admin.sequences.index')->with('success', 'Sequence deleted.');
    }

    // -- Steps Management --
    public function storeStep(StoreSequenceStepRequest $request, PlatformSequence $sequence)
    {
        $this->sequenceService->addStep($sequence, $request->validated());
        return redirect()->back()->with('success', 'Step added.');
    }

    public function updateStep(UpdateSequenceStepRequest $request, PlatformSequenceStep $step)
    {
        $this->sequenceService->updateStep($step, $request->validated());
        return redirect()->back()->with('success', 'Step updated.');
    }

    public function deleteStep(PlatformSequenceStep $step)
    {
        $this->sequenceService->deleteStep($step);
        return redirect()->back()->with('success', 'Step deleted.');
    }

    // -- AI Generation --
    public function generateStepsWithAI(GenerateAISequenceRequest $request, PlatformSequence $sequence)
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

    public function applyGeneratedSteps(ApplyGeneratedStepsRequest $request, PlatformSequence $sequence)
    {
        $this->sequenceService->applyGeneratedSteps($sequence, $request->validated('steps'));

        return redirect()->back()->with('success', 'AI steps applied successfully.');
    }
}
