<?php

namespace SgFlores\Cruder\Strategies\Validation;

use Illuminate\Validation\ValidationException;

/**
 * Validation Strategy Interface
 *
 * This interface defines the contract for validation strategies.
 * Implementations can provide custom validation logic for different operations.
 */
interface ValidationStrategyInterface
{
    /**
     * Validates data for the specified operation.
     *
     * @param  array  $data  The data to validate
     * @param  string  $operation  The operation type ('create', 'update', 'bulk_create', 'bulk_update')
     * @param  array  $context  Additional context for validation (e.g., model instance, filters)
     * @return bool|array Returns true if valid, or array of validated data
     *
     * @throws ValidationException If validation fails
     */
    public function validate(array $data, string $operation, array $context = []): bool|array;
}
