<?php

namespace SgFlores\Cruder\Strategies\Hooks;

/**
 * Interface for query hooks.
 * 
 * This interface defines the contract for different hook implementations,
 * allowing for easy extension and addition of custom business logic.
 */
interface HookInterface
{
    /**
     * Executes the hook with the provided data.
     * 
     * @param mixed $data The data to process
     * @return mixed The processed data
     */
    public function execute($data): mixed;
}
