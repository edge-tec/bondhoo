<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Marketplace listing or stock quantity becomes unavailable
 * due to a concurrent purchase or reservation conflict (HTTP 409 Conflict).
 */
class MarketplaceConflictException extends RuntimeException {}
