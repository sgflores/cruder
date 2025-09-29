<?php

namespace SgFlores\Cruder\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use SgFlores\Cruder\Strategies\Validation\ValidationStrategyInterface;
use SgFlores\Cruder\Exceptions\ValidationException as CruderValidationException;

/**
 * Validation Factory
 * 
 * This factory simplifies validation logic by providing a unified interface
 * for different validation approaches.
 */
class ValidationFactory
{
    /**
     * Creates a validator and returns validated data.
     * 
     * Centralized validation factory that handles different validation approaches:
     * - Array rules: Uses Laravel's built-in validation
     * - Strategy objects: Uses custom validation strategies
     * - Null: Returns data without validation
     * 
     * @param array $data The data to validate
     * @param mixed $validationRules Validation rules or strategy
     * @param string $operation The operation being performed
     * @param array $context Additional context for validation
     * @return array The validated data
     * @throws CruderValidationException If validation fails
     */
    public static function createValidator(array $data, $validationRules, string $operation = 'create', array $context = []): array
    {
        // Return data unchanged if no validation rules provided
        if ($validationRules === null) {
            return $data;
        }
        
        // Use Laravel validation for array rules
        if (is_array($validationRules)) {
            return self::validateWithArray($data, $validationRules);
        }
        
        // Use custom strategy for strategy objects
        if ($validationRules instanceof ValidationStrategyInterface) {
            return self::validateWithStrategy($data, $validationRules, $operation, $context);
        }
        
        // Fallback: return data unchanged
        return $data;
    }
    
    /**
     * Validates data using Laravel validation rules.
     * 
     * @param array $data The data to validate
     * @param array $rules The validation rules
     * @return array The validated data
     * @throws CruderValidationException If validation fails
     */
    protected static function validateWithArray(array $data, array $rules): array
    {
        if (empty($rules)) {
            return $data;
        }
        
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new CruderValidationException(new ValidationException($validator));
        }
        
        return $validator->validated();
    }
    
    /**
     * Validates data using a validation strategy.
     * 
     * @param array $data The data to validate
     * @param ValidationStrategyInterface $strategy The validation strategy
     * @param string $operation The operation being performed
     * @param array $context Additional context for validation
     * @return array The validated data
     * @throws CruderValidationException If validation fails
     */
    protected static function validateWithStrategy(array $data, ValidationStrategyInterface $strategy, string $operation, array $context): array
    {
        try {
            $result = $strategy->validate($data, $operation, $context);
            return is_array($result) ? $result : $data;
        } catch (ValidationException $e) {
            throw new CruderValidationException($e);
        }
    }
}
