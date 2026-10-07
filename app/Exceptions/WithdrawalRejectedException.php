<?php

namespace App\Exceptions;

use DomainException;

/**
 * Thrown when a withdrawal fails its eligibility re-check under the user row lock
 * (for example a parallel request already reserved the same funds).
 * The message is safe to show to the user.
 */
class WithdrawalRejectedException extends DomainException {}
