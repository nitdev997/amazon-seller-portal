<?php

namespace App\Exceptions;

/**
 * Thrown when an SP-API call requires a Restricted Data Token (RDT)
 * that hasn't been approved by Amazon yet.
 *
 * This is expected during Amazon's developer review period.
 * Catch this separately to skip gracefully rather than failing the sync.
 */
class RdtPendingReviewException extends \RuntimeException {}