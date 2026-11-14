<?php

namespace Modules\AppBuilder\Services\Chat;

/** This visitor's chat was closed for messages that were not serious; only the owner opens it again. */
class ChatBanned extends ChatLimitReached {}
