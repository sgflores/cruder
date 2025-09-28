<?php

namespace SgFlores\Cruder\Strategies\Hooks;

/**
 * Callable hook implementation.
 * 
 * Wraps a callable function to implement the HookInterface,
 * allowing for easy integration of existing functions and closures.
 */
class CallableHook implements HookInterface
{
    /**
     * The callable function to execute.
     * 
     * @var callable
     */
    private $callable;

    /**
     * Creates a new callable hook instance.
     * 
     * @param callable $callable The callable function
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Executes the callable hook.
     * 
     * @param mixed $data The data to process
     * @return mixed The result of the callable
     */
    public function execute($data): mixed
    {
        return call_user_func($this->callable, $data);
    }
}
