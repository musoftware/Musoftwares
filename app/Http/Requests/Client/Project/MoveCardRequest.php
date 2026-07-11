<?php

namespace App\Http\Requests\Client\Project;

use App\Models\ProjectBoardItem;
use Illuminate\Foundation\Http\FormRequest;

class MoveCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'for_date' => ['required', 'string', 'date_format:Y-m-d'],
            'type' => ['required', 'string', 'in:'.implode(',', ProjectBoardItem::validTypeKeys())],
            'id' => ['required', 'integer'],
            'lane' => ['nullable', 'string', 'max:50'],
            'pos_x' => ['nullable', 'integer', 'min:0'],
            'pos_y' => ['nullable', 'integer', 'min:0'],
            // Optional: explicit new ordering within the target lane. When omitted,
            // the card is appended at the end.
            'sort' => ['nullable', 'integer', 'min:0'],
            // Optional: change the card's category in the same call. null clears it.
            'category_id' => ['nullable', 'integer', 'exists:project_board_categories,id'],
        ];
    }

    /**
     * Resolve the validated short type alias to the fully-qualified morph class.
     */
    public function morphClass(): string
    {
        return ProjectBoardItem::morphClassFor($this->input('type'));
    }
}
