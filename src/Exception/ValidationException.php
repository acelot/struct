<?php declare(strict_types=1);

namespace Acelot\Struct\Exception;

use Throwable;

class ValidationException extends StructException
{
    /**
     * @var array
     */
    protected $errors;

    /**
     * @param array          $errors
     * @param Throwable|null $previous
     */
    public function __construct(array $errors, Throwable $previous = null)
    {
        parent::__construct('Validation error', 0, $previous);
        $this->errors = $errors;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
