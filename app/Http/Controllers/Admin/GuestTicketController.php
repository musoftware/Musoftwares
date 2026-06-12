<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\GuestTicket;

class GuestTicketController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/GuestTickets/Index', [
            'tickets' => GuestTicket::latest()->paginate(15)
        ]);
    }

    public function show(GuestTicket $guest_ticket)
    {
        return Inertia::render('Admin/GuestTickets/Show', [
            'ticket' => $guest_ticket
        ]);
    }
}
