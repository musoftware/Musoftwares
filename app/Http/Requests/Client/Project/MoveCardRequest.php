<?php

namespace App\Http\Requests\Client\Project;

use App\Models\ProjectBoardNote;
use App\Models\ProjectReport;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class MoveCardRequest extends FormRequest
{
    public const ALLOWED_TYPES = [
        'note' => ProjectBoardNote::class,
        'task' => Task::class,
        'report' => ProjectReport::class,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'for_date' => ['required', 'string', 'date_format:Y-m-d'],
            'type' => ['required', 'string', 'in:note,task,report'],
            'id' => ['required', 'integer'],
            'lane' => ['nullable', 'string', 'max:50'],
            'pos_x' => ['nullable', 'integer', 'min:0'],
            'pos_y' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Resolve the validated short type alias to the fully-qualified morph class.
     */
    public function morphClass(): string
    {
        return self::ALLOWED_TYPES[$this->input('type')];
    }
}
