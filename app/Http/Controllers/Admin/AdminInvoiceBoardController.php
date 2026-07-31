<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ProjectBoardItem;
use App\Models\ProjectBoardNote;
use App\Models\Task;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminInvoiceBoardController extends Controller
{
    private function cairoToday(): string
    {
        return Carbon::today('Africa/Cairo')->toDateString();
    }

    private function place(
        Invoice $invoice,
        string $date,
        string $morphClass,
        int $morphId,
        string $lane,
        ?int $categoryId = null
    ): ProjectBoardItem {
        $attributes = [
            'itemable_type' => $morphClass,
            'itemable_id' => $morphId,
        ];

        $values = [
            'invoice_id' => $invoice->id,
            'project_id' => $invoice->project_id,
            'for_date' => $date,
            'lane' => $lane,
            'pos_x' => 24,
            'pos_y' => 24,
        ];

        $existing = ProjectBoardItem::where($attributes)->first();
        if (!$existing) {
            $nextSort = (int) (ProjectBoardItem::where('project_id', $invoice->project_id)
                ->where('for_date', $date)
                ->where('lane', $lane)
                ->max('sort') ?? -1) + 1;
            $values['sort'] = $nextSort;
        }

        $placement = ProjectBoardItem::updateOrCreate($attributes, $values);

        if ($categoryId !== null) {
            $placement->category_id = $categoryId;
            $placement->save();
        }

        return $placement;
    }

    // ─── Notes ────────────────────────────────────────────────────────

    public function storeNote(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->project_id, 422, 'Invoice must be linked to a project.');

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'color' => 'nullable|string|max:30',
            'lane' => 'nullable|string|max:50',
            'category_id' => 'nullable|integer',
        ]);

        $date = $this->cairoToday();

        $note = ProjectBoardNote::create([
            'project_id' => $invoice->project_id,
            'invoice_id' => $invoice->id,
            'author_id' => $request->user()->id,
            'for_date' => $date,
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'color' => $data['color'] ?? 'yellow',
        ]);

        $placement = $this->place(
            $invoice,
            $date,
            ProjectBoardNote::class,
            $note->id,
            $data['lane'] ?? 'backlog',
            $data['category_id'] ?? null
        );

        return response()->json([
            'ok' => true,
            'card' => array_merge([
                'type' => 'note',
                'id' => $note->id,
                'title' => $note->title ?: ($note->content ? mb_strimwidth($note->content, 0, 80, '…') : __('general.sticky_note')),
                'lane' => $placement->lane,
                'pos_x' => $placement->pos_x,
                'pos_y' => $placement->pos_y,
                'color' => $note->color,
                'content' => $note->content,
                'sort' => $placement->sort,
                'category_id' => $placement->category_id,
            ])
        ]);
    }

    public function updateNote(Request $request, Invoice $invoice, ProjectBoardNote $note)
    {
        abort_unless($note->invoice_id === $invoice->id, 404);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'color' => 'nullable|string|max:30',
        ]);

        $note->update($data);

        return response()->json(['ok' => true]);
    }

    public function destroyNote(Invoice $invoice, ProjectBoardNote $note)
    {
        abort_unless($note->invoice_id === $invoice->id, 404);
        $note->delete();

        return response()->json(['ok' => true]);
    }

    // ─── Tasks ────────────────────────────────────────────────────────

    public function storeTask(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->project_id, 422, 'Invoice must be linked to a project.');

        $data = $request->validate([
            'task_name' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'lane' => 'nullable|string|max:50',
            'category_id' => 'nullable|integer',
        ]);

        $date = $this->cairoToday();

        $task = Task::create([
            'user_id' => $invoice->user_id,
            'project_id' => $invoice->project_id,
            'invoice_id' => $invoice->id,
            'task_name' => $data['task_name'],
            'task_description' => $data['task_description'] ?? null,
            'due_date' => $date,
            'priority' => $data['priority'] ?? 'normal',
        ]);

        $placement = $this->place(
            $invoice,
            $date,
            Task::class,
            $task->id,
            $data['lane'] ?? 'backlog',
            $data['category_id'] ?? null
        );

        return response()->json([
            'ok' => true,
            'card' => [
                'type' => 'task',
                'id' => $task->id,
                'title' => $task->task_name,
                'lane' => $placement->lane,
                'pos_x' => $placement->pos_x,
                'pos_y' => $placement->pos_y,
                'description' => $task->task_description,
                'priority' => $task->priority,
                'done' => false,
                'sort' => $placement->sort,
                'category_id' => $placement->category_id,
            ]
        ]);
    }

    public function updateTask(Request $request, Invoice $invoice, Task $task)
    {
        abort_unless($task->invoice_id === $invoice->id, 404);

        $data = $request->validate([
            'task_name' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
        ]);

        $task->update($data);

        return response()->json(['ok' => true]);
    }

    public function destroyTask(Invoice $invoice, Task $task)
    {
        abort_unless($task->invoice_id === $invoice->id, 404);
        $task->delete();

        return response()->json(['ok' => true]);
    }

    // ─── Todos ────────────────────────────────────────────────────────

    public function storeTodo(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->project_id, 422, 'Invoice must be linked to a project.');

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lane' => 'nullable|string|max:50',
            'category_id' => 'nullable|integer',
        ]);

        $date = $this->cairoToday();

        $todo = Todo::create([
            'user_id' => $invoice->user_id,
            'project_id' => $invoice->project_id,
            'invoice_id' => $invoice->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'completed' => false,
            'inDate' => $date,
            'priority' => 'normal',
            'priorityColor' => 'gray',
            'tags' => '[]',
            'paused' => false,
        ]);

        $placement = $this->place(
            $invoice,
            $date,
            Todo::class,
            $todo->id,
            $data['lane'] ?? 'backlog',
            $data['category_id'] ?? null
        );

        return response()->json([
            'ok' => true,
            'card' => [
                'type' => 'todo',
                'id' => $todo->id,
                'title' => $todo->title,
                'lane' => $placement->lane,
                'pos_x' => $placement->pos_x,
                'pos_y' => $placement->pos_y,
                'description' => $todo->description,
                'completed' => false,
                'checklist' => [],
                'sort' => $placement->sort,
                'category_id' => $placement->category_id,
            ]
        ]);
    }

    public function updateTodo(Request $request, Invoice $invoice, Todo $todo)
    {
        abort_unless($todo->invoice_id === $invoice->id, 404);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'nullable|boolean',
        ]);

        $todo->update($data);

        return response()->json(['ok' => true]);
    }

    public function destroyTodo(Invoice $invoice, Todo $todo)
    {
        abort_unless($todo->invoice_id === $invoice->id, 404);
        $todo->delete();

        return response()->json(['ok' => true]);
    }

    // ─── Positions & Lanes ─────────────────────────────────────────────

    public function moveCard(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
            'lane' => 'required|string',
        ]);

        $morphClass = ProjectBoardItem::morphClassFor($data['type']);

        $placement = ProjectBoardItem::where('invoice_id', $invoice->id)
            ->where('itemable_type', $morphClass)
            ->where('itemable_id', $data['id'])
            ->firstOrFail();

        $placement->update([
            'lane' => $data['lane'],
        ]);

        return response()->json(['ok' => true]);
    }

    public function reorderCards(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'lane' => 'required|string',
            'order' => 'required|array',
            'order.*.type' => 'required|string',
            'order.*.id' => 'required|integer',
        ]);

        DB::transaction(function () use ($invoice, $data) {
            foreach ($data['order'] as $index => $item) {
                $morphClass = ProjectBoardItem::morphClassFor($item['type']);
                ProjectBoardItem::where('invoice_id', $invoice->id)
                    ->where('itemable_type', $morphClass)
                    ->where('itemable_id', $item['id'])
                    ->update([
                        'lane' => $data['lane'],
                        'sort' => $index,
                    ]);
            }
        });

        return response()->json(['ok' => true]);
    }
}
