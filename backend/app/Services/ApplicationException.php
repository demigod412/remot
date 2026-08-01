<?php

namespace App\Services;

use RuntimeException;

/**
 * Thrown when a task application cannot proceed for a reason the worker should
 * see: no slots, already applied, not enough coins, wrong user type, KYC missing.
 *
 * Distinct from a real failure so controllers can safely render getMessage() to
 * the end user without leaking internals.
 */
class ApplicationException extends RuntimeException
{
}
