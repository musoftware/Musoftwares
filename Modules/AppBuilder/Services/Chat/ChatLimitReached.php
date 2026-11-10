<?php

namespace Modules\AppBuilder\Services\Chat;

use RuntimeException;

/** Each chat costs real tokens, so one visitor gets a fair number of chats and messages. */
class ChatLimitReached extends RuntimeException {}
