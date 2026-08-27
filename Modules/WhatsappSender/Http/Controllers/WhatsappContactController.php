<?php

namespace Modules\WhatsappSender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Modules\WhatsappSender\Models\WhatsappContactGroup;
use Modules\WhatsappSender\Models\WhatsappContact;
use Modules\WhatsappSender\Models\WhatsappBusiness;
use Illuminate\Validation\ValidationException;

class WhatsappContactController extends Controller
{
    /**
     * Create a new contact group.
     */
    public function storeGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'whatsapp_business_id' => ['nullable', 'exists:whatsapp_businesses,id'],
        ]);

        $user = $request->user();

        WhatsappContactGroup::create([
            'user_id' => $user->id,
            'whatsapp_business_id' => $validated['whatsapp_business_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Contact group created successfully.');
    }

    /**
     * Delete contact group.
     */
    public function destroyGroup(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $query = WhatsappContactGroup::where('id', $id);
        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('business', function ($bq) use ($user) {
                      $bq->where('user_id', $user->id);
                  });
            });
        }
        $group = $query->firstOrFail();

        $group->delete();

        return redirect()->back()->with('success', 'Contact group deleted successfully.');
    }

    /**
     * Add/import contacts into a group.
     */
    public function storeContacts(Request $request, int $groupId): RedirectResponse
    {
        $user = $request->user();
        $query = WhatsappContactGroup::where('id', $groupId);
        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('business', function ($bq) use ($user) {
                      $bq->where('user_id', $user->id);
                  });
            });
        }
        $group = $query->firstOrFail();

        $validated = $request->validate([
            'contacts_text' => ['nullable', 'string'],
            'csv_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $imported = 0;

        // 1. Process CSV File if uploaded
        if ($request->hasFile('csv_file')) {
            $path = $request->file('csv_file')->getRealPath();
            $file = fopen($path, 'r');
            $headers = fgetcsv($file);

            // Clean headers (remove BOM if present)
            if ($headers) {
                $headers[0] = preg_replace('/[\x{FEFF}\x{FFFE}]/u', '', $headers[0]);
                $headers = array_map('trim', $headers);
            }

            // Find phone column index
            $phoneIndex = -1;
            $nameIndex = -1;
            foreach ($headers as $index => $header) {
                $lower = strtolower($header);
                if (in_array($lower, ['phone', 'mobile', 'number', 'recipient_phone', 'phone_number'])) {
                    $phoneIndex = $index;
                }
                if (in_array($lower, ['name', 'full_name', 'username'])) {
                    $nameIndex = $index;
                }
            }

            if ($phoneIndex === -1) {
                fclose($file);
                throw ValidationException::withMessages([
                    'csv_file' => 'Could not find a phone/mobile column in the CSV file headers.',
                ]);
            }

            while (($row = fgetcsv($file)) !== false) {
                $phone = preg_replace('/[^0-9]/', '', $row[$phoneIndex] ?? '');
                if (empty($phone) || strlen($phone) < 7) {
                    continue;
                }

                $name = $nameIndex !== -1 ? trim($row[$nameIndex] ?? '') : null;

                // Rest of columns go to custom fields
                $customFields = [];
                foreach ($headers as $index => $header) {
                    if ($index !== $phoneIndex && $index !== $nameIndex && isset($row[$index])) {
                        $customFields[trim($header)] = trim($row[$index]);
                    }
                }

                WhatsappContact::create([
                    'whatsapp_contact_group_id' => $group->id,
                    'name' => $name,
                    'phone' => $phone,
                    'custom_fields' => $customFields,
                ]);
                $imported++;
            }
            fclose($file);
        }

        // 2. Process manual contacts text
        if (!empty($validated['contacts_text'])) {
            $lines = explode("\n", $validated['contacts_text']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // Format expected: phone, name or just phone
                $parts = explode(',', $line, 2);
                $phone = preg_replace('/[^0-9]/', '', $parts[0]);
                
                if (empty($phone) || strlen($phone) < 7) {
                    continue;
                }

                $name = isset($parts[1]) ? trim($parts[1]) : null;

                WhatsappContact::create([
                    'whatsapp_contact_group_id' => $group->id,
                    'name' => $name,
                    'phone' => $phone,
                    'custom_fields' => [],
                ]);
                $imported++;
            }
        }

        return redirect()->back()->with('success', "Imported {$imported} contacts into group successfully.");
    }

    /**
     * Delete an individual contact.
     */
    public function destroyContact(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $contact = WhatsappContact::findOrFail($id);
        
        // Ensure user has access to the group
        $groupQuery = WhatsappContactGroup::where('id', $contact->whatsapp_contact_group_id);
        if (!$user->isAdmin()) {
            $groupQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('business', function ($bq) use ($user) {
                      $bq->where('user_id', $user->id);
                  });
            });
        }
        $groupQuery->firstOrFail();

        $contact->delete();

        return redirect()->back()->with('success', 'Contact deleted successfully.');
    }
}
