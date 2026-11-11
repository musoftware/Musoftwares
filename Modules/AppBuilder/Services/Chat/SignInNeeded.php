<?php

namespace Modules\AppBuilder\Services\Chat;

/** A guest talked a lot without reaching a price: signing in keeps the chat going (and stops idle talk from costing). */
class SignInNeeded extends ChatLimitReached {}
