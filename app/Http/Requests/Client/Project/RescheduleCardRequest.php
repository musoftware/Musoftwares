<?php

namespace App\Http\Requests\Client\Project;

use App\Models\ProjectBoardItem;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'for_date' => ['required', 'date_format:Y-m-d'],
            'type' => ['required', 'string', 'in:'.implode(',', ProjectBoardItem::validTypeKeys())],
            'id' => ['required', 'integer'],
        ];
    }

    /**
     * Throw a 403 when the user is not an admin. authorize() returning false
     * already results in a 403 — this exists to surface a domain-specific
     * translation key consistent with the rest of the board module.
     */
    protected function failedAuthorization(): void
    {
        abort(403, __('general.card_reschedule_admin_only'));
    }
}
