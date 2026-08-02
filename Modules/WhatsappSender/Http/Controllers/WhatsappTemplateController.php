<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Modules\WhatsappSender\Models\WhatsappTemplate;
use Modules\WhatsappSender\Models\WhatsappAccount;
use Modules\WhatsappSender\Services\MetaWhatsappService;
use Illuminate\Validation\ValidationException;

class WhatsappTemplateController extends Controller
{
    public function __construct(
        protected MetaWhatsappService $whatsappService
    ) {}

    /**
     * Store a new template locally and submit it to Meta for approval.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($request->has('name')) {
            $name = (string) $request->input('name');
            $slug = \Illuminate\Support\Str::slug($name, '_');
            $slug = preg_replace('/[^a-z0-9_]/', '', strtolower($slug));
            if (empty($slug)) {
                $slug = 'template_' . time();
            }
            $request->merge([
                'name' => $slug,
            ]);
        }

        $validated = $request->validate([
            'whatsapp_business_id' => ['required', 'exists:whatsapp_businesses,id'],
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/'], // Meta template names must be lowercase letters, numbers and underscores only
            'category' => ['required', 'string', 'in:UTILITY,MARKETING'],
            'language' => ['required', 'string', 'max:10'],
            'components' => ['required', 'array'],
        ]);

        $business = WhatsappBusiness::where('user_id', $user->id)
            ->where('id', $validated['whatsapp_business_id'])
            ->firstOrFail();

        // Check if there is an active account to submit to Facebook
        $account = WhatsappAccount::where('whatsapp_business_id', $business->id)
            ->where('status', 'active')
            ->first();

        if (!$account) {
            throw ValidationException::withMessages([
                'whatsapp_business_id' => 'No active WhatsApp account connected to this business. Connect a number first to submit templates.',
            ]);
        }

        // Create locally first
        $template = WhatsappTemplate::create([
            'whatsapp_business_id' => $business->id,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'language' => $validated['language'],
            'components' => $validated['components'],
            'status' => 'PENDING',
        ]);

        // Submit to Meta
        $result = $this->whatsappService->createMetaTemplate($account, $template);

        if ($result['success']) {
            $template->update([
                'status' => $result['status'] ?? 'PENDING',
                'meta_template_id' => $result['id'] ?? null,
            ]);

            return redirect()->back()->with('success', 'Template created and submitted to Facebook successfully.');
        }

        // Clean up local record if Meta submission fails
        $template->forceDelete();

        throw ValidationException::withMessages([
            'name' => 'Meta API Error: ' . $result['error'],
        ]);
    }

    /**
     * Delete a template from Meta and locally.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $template = WhatsappTemplate::findOrFail($id);
        $business = WhatsappBusiness::where('user_id', $request->user()->id)
            ->where('id', $template->whatsapp_business_id)
            ->firstOrFail();

        $account = WhatsappAccount::where('whatsapp_business_id', $business->id)
            ->where('status', 'active')
            ->first();

        if ($account) {
            $result = $this->whatsappService->deleteMetaTemplate($account, $template->name);
            if (!$result['success']) {
                return redirect()->back()->with('error', 'Failed to delete template from Meta: ' . $result['error']);
            }
        }

        $template->delete();

        return redirect()->back()->with('success', 'Template deleted successfully.');
    }

    /**
     * Synchronize templates from Meta.
     */
    public function sync(Request $request, int $businessId): RedirectResponse
    {
        $business = WhatsappBusiness::where('user_id', $request->user()->id)
            ->where('id', $businessId)
            ->firstOrFail();

        $account = WhatsappAccount::where('whatsapp_business_id', $business->id)
            ->where('status', 'active')
            ->first();

        if (!$account) {
            return redirect()->back()->with('error', 'No active WhatsApp account connected to this business to sync templates.');
        }

        $result = $this->whatsappService->syncMetaTemplates($account);

        if ($result['success']) {
            return redirect()->back()->with('success', "Synchronized {$result['count']} templates from Meta successfully.");
        }

        return redirect()->back()->with('error', 'Failed to sync templates from Meta: ' . $result['error']);
    }
}
