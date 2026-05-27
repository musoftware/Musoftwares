<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletTransfer;
use App\Services\WalletTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Exception;

class WalletTransferController extends Controller
{
    protected WalletTransferService $transferService;

    public function __construct(WalletTransferService $transferService)
    {
        $this->transferService = $transferService;
        $this->middleware('auth');
    }

    /**
     * Show the peer-to-peer transfer creation form.
     */
    public function create()
    {
        $user = Auth::user();
        return Inertia::render('Financial/WalletTransfer/Create', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'wallet' => [
                'balance' => (float) $user->user_balance,
                'currency' => $user->preferred_currency ?? 'USD',
            ]
        ]);
    }

    /**
     * Store and execute a new wallet transfer.
     */
    public function store(Request $request)
    {
        $request->validate([
            'receiver_email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
            'confirm_transfer' => 'required|accepted'
        ], [
            'receiver_email.exists' => 'The recipient email address could not be found.',
            'confirm_transfer.accepted' => 'You must confirm the transfer details to complete the transaction.'
        ]);

        $sender = Auth::user();
        $receiver = User::where('email', $request->receiver_email)->firstOrFail();

        // Prevent self transfers
        if ($sender->id === $receiver->id) {
            throw ValidationException::withMessages([
                'receiver_email' => ['You cannot transfer money to yourself.'],
            ]);
        }

        try {
            $transfer = $this->transferService->executeTransfer(
                $sender->id,
                $receiver->id,
                (float) $request->amount,
                $sender->preferred_currency ?? 'USD',
                $request->reason
            );

            return redirect()
                ->route('financial.transfer.show', $transfer->id)
                ->with('success', 'Transfer completed successfully! ' . \App\Helpers\FinanceHelper::instance()->format_money($transfer->amount, $transfer->currency_id ?? $transfer->currency) . ' was sent.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    /**
     * Render the transfer history page.
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        
        $transfers = WalletTransfer::with(['sender', 'receiver'])
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($transfer) use ($user) {
                $type = $transfer->sender_id === $user->id ? 'sent' : 'received';
                return [
                    'id' => $transfer->id,
                    'type' => $type,
                    'other_party' => $type === 'sent' ? $transfer->receiver->name : $transfer->sender->name,
                    'other_party_email' => $type === 'sent' ? $transfer->receiver->email : $transfer->sender->email,
                    'amount' => (float) $transfer->amount,
                    'currency' => $transfer->currency,
                    'fee' => (float) $transfer->fee_amount,
                    'converted_amount' => (float) $transfer->converted_amount,
                    'converted_currency' => $transfer->converted_currency,
                    'status' => $transfer->status,
                    'reason' => $transfer->reason,
                    'created_at' => $transfer->created_at->toISOString(),
                ];
            });

        return Inertia::render('Financial/WalletTransfer/History', [
            'transfers' => $transfers,
        ]);
    }

    /**
     * Show details of a specific transfer.
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $transfer = WalletTransfer::with(['sender', 'receiver'])
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
            })
            ->findOrFail($id);

        $type = $transfer->sender_id === $user->id ? 'sent' : 'received';

        return Inertia::render('Financial/WalletTransfer/Show', [
            'transfer' => [
                'id' => $transfer->id,
                'type' => $type,
                'sender_name' => $transfer->sender->name,
                'sender_email' => $transfer->sender->email,
                'receiver_name' => $transfer->receiver->name,
                'receiver_email' => $transfer->receiver->email,
                'amount' => (float) $transfer->amount,
                'currency' => $transfer->currency,
                'fee' => (float) $transfer->fee_amount,
                'converted_amount' => (float) $transfer->converted_amount,
                'converted_currency' => $transfer->converted_currency,
                'exchange_rate' => (float) $transfer->exchange_rate,
                'reason' => $transfer->reason,
                'status' => $transfer->status,
                'processed_at' => $transfer->processed_at->toISOString(),
            ]
        ]);
    }

    /**
     * Calculate fees and exchange previews in real-time.
     */
    public function calculateFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sender = Auth::user();
            $receiver = User::where('email', $request->receiver_email)->firstOrFail();

            if ($sender->id === $receiver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot transfer money to yourself.'
                ], 422);
            }

            $preview = $this->transferService->previewTransfer(
                $sender->id,
                $receiver->id,
                (float) $request->amount,
                $sender->preferred_currency ?? 'USD'
            );

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search user emails/names for recipient auto-complete.
     * Guarded to minimum 5 characters for user privacy/system security.
     */
    public function searchUsers(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 5) {
            return response()->json(['users' => []]);
        }

        $users = User::where('id', '!=', Auth::id())
            ->where(function ($q) use ($query) {
                $q->where('email', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'email')
            ->limit(8)
            ->get();

        return response()->json(['users' => $users]);
    }
}
