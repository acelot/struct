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
     * @param string|array   $errors
     * @param Throwable|null $previous
     */
    public function __construct($errors, Throwable $previous = null)
    {
        parent::__construct(is_string($errors) ? $errors : 'Validation error', 0, $previous);
        $this->errors = is_array($errors) ? $errors : [];
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
