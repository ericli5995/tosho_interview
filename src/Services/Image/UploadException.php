<?php

declare(strict_types=1);

namespace App\Services\Image;

/**
 * Thrown by ImageUploadService for user-correctable problems (wrong type,
 * too large, unreadable). The controller returns the message as a 422 field error.
 */
final class UploadException extends \RuntimeException
{
}
