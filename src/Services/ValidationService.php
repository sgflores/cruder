<?php

namespace SgFlores\Cruder\Services;

use Illuminate\Validation\ValidationException;
use SgFlores\Cruder\Strategies\Validation\ValidationStrategyInterface;

/**
 * Validation Service
 *
 * This service manages validation strategies and provides a unified interface
 * for validating data in CRUD operations.
 */
class ValidationService
{
    /**
     * The validation strategy to use.
     */
    protected ?ValidationStrategyInterface $strategy = null;

    /**
     * ValidationService constructor.
     *
     * @param  ValidationStrategyInterface|null  $strategy  The validation strategy to use
     */
    public function __construct(?ValidationStrategyInterface $strategy = null)
    {
        $this->strategy = $strategy;
    }

    /**
     * Sets the validation strategy.
     *
     * @param  ValidationStrategyInterface  $strategy  The validation strategy
     */
    public function setStrategy(ValidationStrategyInterface $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Validates data using the current strategy.
     *
     * Delegates validation to the configured strategy.
     * If no strategy is set, returns data unchanged.
     *
     * @param  array  $data  The data to validate
     * @param  string  $operation  The operation type
     * @param  array  $context  Additional context for validation
     * @return bool|array Returns true if valid, or array of validated data
     *
     * @throws ValidationException If validation fails
     */
    public function validate(array $data, string $operation, array $context = []): bool|array
    {
        // Return data unchanged if no strategy is configured
        if ($this->strategy === null) {
            return $data;
        }

        // Delegate to the configured validation strategy
        return $this->strategy->validate($data, $operation, $context);
    }

    /**
     * Gets the current validation strategy.
     */
    public function getStrategy(): ?ValidationStrategyInterface
    {
        return $this->strategy;
    }
}
