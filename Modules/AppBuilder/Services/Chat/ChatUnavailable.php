<?php

namespace Modules\AppBuilder\Services\Chat;

use RuntimeException;

/** Claude could not answer this turn (API error, refusal, cut-off or bad JSON). The customer can send again. */
class ChatUnavailable extends RuntimeException {}
