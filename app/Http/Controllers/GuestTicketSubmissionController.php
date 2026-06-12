<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GuestTicket;

class GuestTicketSubmissionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => 'required|string|max:20',
            'body' => 'required|string',
        ]);

        GuestTicket::create($data);

        return back()->with('success', __('general.ticket_submitted_success'));
    }
}
