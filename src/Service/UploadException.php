<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Thrown by ImageUploadService for user-correctable problems (wrong type,
 * too large, unreadable). Controllers turn the message into a flash notice.
 */
final class UploadException extends \RuntimeException
{
}
