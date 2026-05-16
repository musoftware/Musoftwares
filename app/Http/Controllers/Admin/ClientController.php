<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Modules\Core\Models\Wallet;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index()
    {
        $clients = User::where('role', 'client')->paginate(15);

        // Eager load or manually fetch wallets
        // Assuming wallet owner_type is App\Models\User
        $clientIds = $clients->pluck('id');
        $wallets = Wallet::where('owner_type', User::class)
            ->whereIn('owner_id', $clientIds)
            ->get()
            ->keyBy('owner_id');

        $clients->getCollection()->transform(function ($client) use ($wallets) {
            $client->wallet = $wallets->get($client->id);
            return $client;
        });

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients,
        ]);
    }

    public function show($id)
    {
        $client = User::with(['supportTickets'])->findOrFail($id);
        $wallets = Wallet::where('owner_type', User::class)
            ->where('owner_id', $id)
            ->with('transactions')
            ->get();

        return Inertia::render('Admin/Clients/Show', [
            'client' => $client,
            'wallets' => $wallets,
        ]);
    }
}
