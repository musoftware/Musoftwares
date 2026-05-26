<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Freelance\Models\Proposal;
use Inertia\Inertia;

class FreelanceProposalController extends Controller
{
    public function index(Request $request)
    {
        $query = Proposal::with(['job', 'freelancer']);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('job', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })->orWhereHas('freelancer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        $proposals = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Freelance/Proposals/Index', [
            'proposals' => $proposals,
            'filters' => $request->only('search', 'status')
        ]);
    }

    public function destroy(Proposal $proposal)
    {
        $proposal->delete();
        return back()->with('success', 'Proposal deleted successfully.');
    }
}
