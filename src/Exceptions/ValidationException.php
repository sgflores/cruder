<?php

namespace SgFlores\Cruder\Exceptions;

use Illuminate\Validation\ValidationException as LaravelValidationException;

/**
 * Cruder Validation Exception
 *
 * This exception is thrown when validation fails in CRUD operations.
 */
class ValidationException extends CruderException
{
    protected $validator;

    public function __construct(LaravelValidationException $exception)
    {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        $this->validator = $exception->validator;
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function errors()
    {
        return $this->validator->errors();
    }
}
