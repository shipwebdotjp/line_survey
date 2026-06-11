<?php

declare(strict_types=1);

namespace App\Application\Admin\RespondentMaster;

use Exception;

class ValidationException extends Exception
{
    public function __construct(
        private array $details,
        string $message = 'Validation Error'
    ) {
        parent::__construct($message);
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
