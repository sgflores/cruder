<?php

namespace SgFlores\Cruder\Services;

use SgFlores\Cruder\Strategies\Hooks\HookInterface;

/**
 * Hook service for managing query hooks.
 * 
 * This service manages the execution of hooks at different points in the
 * CRUD lifecycle. It follows the Observer pattern to allow for easy
 * extension of business logic without modifying core functionality.
 */
class HookService
{
    /**
     * Registered hooks by operation.
     * 
     * @var array<string, array<HookInterface>>
     */
    private array $hooks = [];

    /**
     * Adds a hook for a specific operation.
     * 
     * @param string $operation The operation name (e.g., 'before_find', 'after_create')
     * @param HookInterface $hook The hook implementation
     * @return void
     */
    public function addHook(string $operation, HookInterface $hook): void
    {
        $this->hooks[$operation][] = $hook;
    }

    /**
     * Executes all hooks for a specific operation.
     * 
     * @param string $operation The operation name
     * @param mixed $data The data to process
     * @return mixed The processed data
     */
    public function executeHooks(string $operation, $data): mixed
    {
        $hooks = $this->hooks[$operation] ?? [];
        
        foreach ($hooks as $hook) {
            $data = $hook->execute($data);
        }
        
        return $data;
    }

    /**
     * Gets all registered operations.
     * 
     * @return array Array of operation names
     */
    public function getAvailableOperations(): array
    {
        return array_keys($this->hooks);
    }

    /**
     * Gets hooks for a specific operation.
     * 
     * @param string $operation The operation name
     * @return array Array of hooks
     */
    public function getHooksForOperation(string $operation): array
    {
        return $this->hooks[$operation] ?? [];
    }

    /**
     * Checks if there are hooks for a specific operation.
     * 
     * @param string $operation The operation name
     * @return bool True if hooks exist for the operation
     */
    public function hasHooks(string $operation): bool
    {
        return !empty($this->hooks[$operation] ?? []);
    }
}
