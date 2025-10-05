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
    // ========================================================================
    // --- Event Constants ---
    // ========================================================================
    
    // Reader Events
    public const BEFORE_FIND = 'before_find';
    public const AFTER_FIND = 'after_find';
    
    // CRUD Events
    public const BEFORE_CREATE = 'before_create';
    public const AFTER_CREATE = 'after_create';
    public const BEFORE_UPDATE = 'before_update';
    public const AFTER_UPDATE = 'after_update';
    public const BEFORE_DELETE = 'before_delete';
    public const AFTER_DELETE = 'after_delete';
    
    // Bulk CRUD Events
    public const BEFORE_BULK_CREATE = 'before_bulk_create';
    public const AFTER_BULK_CREATE = 'after_bulk_create';
    public const BEFORE_BULK_UPDATE = 'before_bulk_update';
    public const AFTER_BULK_UPDATE = 'after_bulk_update';
    public const BEFORE_BULK_DELETE = 'before_bulk_delete';
    public const AFTER_BULK_DELETE = 'after_bulk_delete';
    public const BEFORE_BULK_UPDATE_FILTERS = 'before_bulk_update_filters';
    public const BEFORE_BULK_DELETE_FILTERS = 'before_bulk_delete_filters';
    
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
