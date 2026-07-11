<?php

namespace App\Http\Controllers;

use App\Services\GuestTicketCreator;
use Illuminate\Http\Request;

class GuestTicketSubmissionController extends Controller
{
    public function store(Request $request, GuestTicketCreator $creator)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => 'required|string|max:20',
            'body' => 'required|string',
            'subject' => 'nullable|string|max:255',
        ]);

        $creator->create($data);

        return back()->with('success', __('general.ticket_submitted_success'));
    }
}
