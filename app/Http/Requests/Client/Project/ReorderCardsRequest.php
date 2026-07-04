<?php

namespace App\Http\Requests\Client\Project;

use App\Models\ProjectBoardItem;
use Illuminate\Foundation\Http\FormRequest;

class ReorderCardsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'for_date' => ['required', 'string', 'date_format:Y-m-d'],
            'lane' => ['required', 'string', 'max:50'],
            // The list of (type, id) pairs in the order the UI wants them rendered.
            // `type` must be one of the polymorphic aliases used by the board.
            'order' => ['required', 'array', 'min:1'],
            'order.*.type' => ['required', 'string', 'in:'.implode(',', ProjectBoardItem::validTypeKeys())],
            'order.*.id' => ['required', 'integer', 'min:1'],
        ];
    }
}
