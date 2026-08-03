<?php

namespace App\Http\Controllers\Frontend;

use App\Events\ContractSigned;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Currency;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientContractController extends Controller
{
    /**
     * Show the contract publicly via UUID.
     */
    public function show(Request $request, $uuid)
    {
        $contract = Contract::with(['project', 'versions', 'invoices' => function ($q) {
            $q->orderBy('created_at', 'asc');
        }])->where('uuid', $uuid)->firstOrFail();

        $user = $request->user();

        // If client is logged in and contract has no user_id, bind contract & project to client
        if ($user && empty($contract->user_id)) {
            $contract->update(['user_id' => $user->id]);
            if ($contract->project && empty($contract->project->user_id)) {
                $contract->project->update(['user_id' => $user->id]);
            }
        }

        $depositAmount = $contract->deposit_amount > 0
            ? (float) $contract->deposit_amount
            : round((float) $contract->total_amount * 0.50, 2);

        $userBalance = $user ? (float) $user->user_balance : 0.0;
        $hasSufficientBalance = $user ? ($userBalance >= $depositAmount) : false;

        $data = [
            'contract' => [
                'id'             => $contract->id,
                'uuid'           => $contract->uuid,
                'project_name'   => $contract->project_name,
                'description'    => $contract->description,
                'payment_terms'  => $contract->payment_terms,
                'total_amount'   => $contract->total_amount,
                'deposit_amount' => $depositAmount,
                'currency'       => $contract->currencyRow() ?? Currency::first(),
                'duration'       => $contract->duration,
                'status'         => $contract->status,
                'signed_at'      => $contract->signed_at,
                'content'        => $contract->content,
            ],
            'wallet_check' => [
                'is_logged_in'           => !empty($user),
                'user_balance'           => $userBalance,
                'deposit_amount'         => $depositAmount,
                'has_sufficient_balance' => $hasSufficientBalance,
                'missing_amount'         => max(0, round($depositAmount - $userBalance, 2)),
                'currency_symbol'        => $user?->currency_name() ?? '$',
            ],
            'invoices' => $contract->invoices->map(function ($invoice) {
                return [
                    'id'         => $invoice->id,
                    'uuid'       => $invoice->uuid,
                    'status'     => $invoice->status,
                    'total_str'  => $invoice->total_str(),
                    'unpaid_str' => $invoice->unpaid_str(),
                    'enc_id'     => $invoice->enc_id(),
                    'items'      => $invoice->items->map(function ($item) {
                        return [
                            'item'  => $item->item,
                            'price' => $item->price,
                        ];
                    }),
                ];
            }),
            'project' => $contract->project ? [
                'id'         => $contract->project->id,
                'date_start' => $contract->project->date_start_str(),
                'date_end'   => $contract->project->date_end_str(),
            ] : null,
        ];

        return Inertia::render('Frontend/Contract/Show', $data);
    }

    /**
     * Sign the contract & process 50% deposit payment
     */
    public function sign(Request $request, $uuid)
    {
        $request->validate([
            'signature'   => 'required|string',
            'client_name' => 'required|string',
        ]);

        $contract = Contract::with('project')->where('uuid', $uuid)->firstOrFail();

        if ($contract->status === 'signed') {
            return redirect()->back()->with('error', __('general.already_signed'));
        }

        $user = $request->user() ?? \App\Models\User::find($contract->user_id);
        if (!$user) {
            return redirect()->back()->with('error', 'يتوجب تسجيل الدخول لاتمام التوقيع وسداد الدفعة الأولى.');
        }

        $depositAmount = $contract->deposit_amount > 0
            ? (float) $contract->deposit_amount
            : round((float) $contract->total_amount * 0.50, 2);

        if ((float) $user->user_balance < $depositAmount) {
            return redirect()->back()->with('error', 'رصيد محفظتك غير كافٍ لسداد الدفعة الأولى (50%) المطلوبة لتوقيع العقد. المبلغ المطلوب: ' . number_format($depositAmount, 2) . ' ' . $user->currency_name());
        }

        \DB::transaction(function () use ($user, $contract, $depositAmount, $request) {
            // 1. Deduct 50% deposit from user wallet
            \App\Models\Transaction::create([
                'user_id'     => $user->id,
                'amount'      => -$depositAmount,
                'reason'      => '50% Deposit Payment for Contract #' . $contract->reference,
                'category'    => 'other',
                'type'        => 'used',
                'project_id'  => $contract->project_id,
                'currency_id' => $user->currency_id,
            ]);

            \App\Helpers\BalancesHelper::UpdateBalance($user, $contract->project);

            // 2. Mark contract as signed & deposit paid
            $contract->update([
                'client_signature' => $request->signature,
                'client_name'      => $request->client_name,
                'signed_at'        => now(),
                'status'           => 'signed',
                'deposit_paid'     => true,
            ]);

            // 3. Create Commercial Invoice for Project
            if ($contract->project) {
                $project = $contract->project;

                $invoice = \App\Models\Invoice::create([
                    'uuid'       => (string) \Illuminate\Support\Str::uuid(),
                    'project_id' => $project->id,
                    'user_id'    => $user->id,
                    'currency'   => $user->currency_id ?? 1,
                    'status'     => 'partial',
                    'unpaid'     => round((float) $contract->total_amount - $depositAmount, 2),
                    'paid'       => $depositAmount,
                ]);

                $project->update(['status' => 'open']);

                $project->updateAiContext([
                    'current_stage'          => 'EXECUTION',
                    'current_invoice_id'     => $invoice->id,
                    'current_invoice_status' => 'paid_50_percent',
                ]);

                // 4. Generate Developer Tasks
                $todoTool = (new \App\Services\AI\AiToolRegistry())->getTool('create_todos');
                if ($todoTool) {
                    $features = $contract->features ?? $project->ai_context['pending_features'] ?? ['ميزات الكود والمشروع'];
                    $todos = [];
                    foreach ($features as $feat) {
                        $todos[] = [
                            'title'       => 'تنفيذ: ' . mb_strimwidth($feat, 0, 50, '…'),
                            'description' => 'مهمة معتمدة بعد توقيع العقد وسداد الدفعة الأولى (50%)',
                            'priority'    => 'high',
                        ];
                    }
                    $todoTool->execute($project, ['todos' => $todos]);
                }

                // 5. Schedule tasks into Admin Working Hours & Queue 15-min Admin notifications
                $scheduler = new \App\Services\AI\TaskSchedulerService();
                $scheduler->scheduleProjectTasks($project);

                // 6. Post system confirmation message
                \App\Models\ProjectComment::create([
                    'project_id'       => $project->id,
                    'author_id'        => null,
                    'guest_name'       => 'AI Agency Manager',
                    'body'             => '[System: تم توقيع العقد وسداد الدفعة الأولى (50%) بنجاح! تم إنشاء الفاتورة واختيار مرحلة التنفيذ وجدولة المهام في أوقات العمل الرسمية وتفعيل إشعارات الـ FCM والإيميل للأدمن قبل كل مهمة بـ 15 دقيقة.]',
                    'commentable_type' => \App\Models\Project::class,
                    'commentable_id'   => $project->id,
                ]);
            }
        });

        event(new ContractSigned($contract));

        return redirect()->back()->with('success', __('general.contract_signed_successfully'));
    }
}
