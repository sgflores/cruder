<?php

namespace SgFlores\Cruder\Services;

/**
 * Event Service
 * 
 * This service provides a simplified event system to replace the complex hook system.
 * It allows for simple event firing and listening without complex management.
 */
class EventService
{
    /**
     * Registered event listeners.
     * 
     * @var array
     */
    protected array $listeners = [];
    
    /**
     * Fires an event with optional data.
     * 
     * Triggers all registered listeners for the given event name.
     * If no listeners exist for the event, nothing happens.
     * 
     * @param string $event The event name
     * @param mixed $data Optional data to pass to listeners
     * @return void
     */
    public function fire(string $event, $data = null): void
    {
        // Skip if no listeners registered for this event
        if (!isset($this->listeners[$event])) {
            return;
        }
        
        // Execute all registered listeners for this event
        foreach ($this->listeners[$event] as $listener) {
            if (is_callable($listener)) {
                $listener($data);
            }
        }
    }
    
    /**
     * Registers an event listener.
     * 
     * Adds a callback function to be executed when the event is fired.
     * Multiple listeners can be registered for the same event.
     * 
     * @param string $event The event name
     * @param callable $callback The callback to execute
     * @return self
     */
    public function listen(string $event, callable $callback): self
    {
        // Initialize event array if it doesn't exist
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        
        // Add the callback to the event's listener list
        $this->listeners[$event][] = $callback;
        return $this;
    }
    
    /**
     * Removes all listeners for an event.
     * 
     * @param string $event The event name
     * @return self
     */
    public function forget(string $event): self
    {
        unset($this->listeners[$event]);
        return $this;
    }
    
    /**
     * Removes all listeners.
     * 
     * @return self
     */
    public function flush(): self
    {
        $this->listeners = [];
        return $this;
    }
    
    /**
     * Gets all registered events.
     * 
     * @return array
     */
    public function getEvents(): array
    {
        return array_keys($this->listeners);
    }
    
    /**
     * Checks if an event has listeners.
     * 
     * @param string $event The event name
     * @return bool
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && count($this->listeners[$event]) > 0;
    }
}
