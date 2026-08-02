<?php

namespace App\Http\Requests\Client\Project;

use App\Models\ProjectBoardItem;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:'.implode(',', array_merge(ProjectBoardItem::validTypeKeys(), ['project']))],
            'commentable_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:5000'],
            'adjust_future_ai' => ['sometimes', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:project_comments,id'],
        ];
    }
}
