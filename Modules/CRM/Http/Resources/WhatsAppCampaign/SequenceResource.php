<?php
namespace Modules\CRM\Http\Resources\WhatsAppCampaign;
use Illuminate\Http\Resources\Json\JsonResource;

class SequenceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'description' => $this->description,
            'is_active' => $this->is_active, 'total_steps' => $this->total_steps,
            'exit_conditions' => $this->exit_conditions,
            'steps' => $this->whenLoaded('steps', fn() => $this->steps->map(fn($s) => [
                'id' => $s->id, 'step_order' => $s->step_order, 'action_type' => $s->action_type,
                'template_id' => $s->template_id, 'message_body' => $s->message_body,
                'message_type' => $s->message_type, 'delay_minutes' => $s->delay_minutes,
                'delay_unit' => $s->delay_unit, 'conditions' => $s->conditions,
                'on_true_step' => $s->on_true_step, 'on_false_step' => $s->on_false_step,
                'skip_if_replied' => $s->skip_if_replied, 'stop_on_reply' => $s->stop_on_reply,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
