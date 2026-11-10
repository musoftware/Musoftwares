<?php

namespace Modules\AppBuilder\Services\Chat;

/** The model that talks with the customer. Tests bind a scripted one; production uses Claude. */
interface ChatModel
{
    /**
     * @param  list<array{role: string, content: string}>  $turns  oldest first, ends with the customer's message
     *
     * @throws ChatUnavailable
     */
    public function reply(string $system, array $turns): ChatTurn;
}
