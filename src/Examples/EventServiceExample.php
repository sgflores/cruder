<?php

namespace SgFlores\Cruder\Examples;

use SgFlores\Cruder\Services\EventService;

/**
 * Event Service Example
 * 
 * Demonstrates how to use the EventService for various business scenarios.
 * Shows event registration, firing, and practical use cases.
 */
class EventServiceExample
{
    protected EventService $eventService;
    
    public function __construct()
    {
        $this->eventService = new EventService();
        $this->setupEventListeners();
    }
    
    /**
     * Setup various event listeners for different scenarios.
     */
    protected function setupEventListeners(): void
    {
        $this->setupUserEvents();
        $this->setupOrderEvents();
        $this->setupNotificationEvents();
        $this->setupAuditEvents();
    }
    
    /**
     * Setup user-related events.
     */
    protected function setupUserEvents(): void
    {
        // User registration events
        $this->eventService->listen('user.registered', function ($user) {
            // Send welcome email
            // Create user profile
            // Assign default permissions
            // Log registration
        });
        
        // User login events
        $this->eventService->listen('user.login', function ($user) {
            // Update last login timestamp
            // Log login activity
            // Check for suspicious activity
        });
        
        // User profile update events
        $this->eventService->listen('user.profile.updated', function ($user, $changes) {
            // Send profile update notification
            // Update search index
            // Log profile changes
        });
    }
    
    /**
     * Setup order-related events.
     */
    protected function setupOrderEvents(): void
    {
        // Order creation events
        $this->eventService->listen('order.created', function ($order) {
            // Send order confirmation email
            // Reserve inventory
            // Create payment record
            // Log order creation
        });
        
        // Order status change events
        $this->eventService->listen('order.status.changed', function ($order, $oldStatus, $newStatus) {
            // Send status update notification
            // Update inventory if cancelled
            // Trigger fulfillment if shipped
            // Log status change
        });
        
        // Order payment events
        $this->eventService->listen('order.payment.completed', function ($order, $payment) {
            // Send payment confirmation
            // Trigger order fulfillment
            // Update customer loyalty points
            // Log payment completion
        });
    }
    
    /**
     * Setup notification events.
     */
    protected function setupNotificationEvents(): void
    {
        // Email notification events
        $this->eventService->listen('notification.email', function ($recipient, $template, $data) {
            // Queue email for sending
            // Add to email tracking
            // Log email notification
        });
        
        // SMS notification events
        $this->eventService->listen('notification.sms', function ($recipient, $message) {
            // Send SMS via provider
            // Track delivery status
            // Log SMS notification
        });
        
        // Push notification events
        $this->eventService->listen('notification.push', function ($user, $title, $body, $data) {
            // Send push notification
            // Track delivery status
            // Log push notification
        });
    }
    
    /**
     * Setup audit and logging events.
     */
    protected function setupAuditEvents(): void
    {
        // Data change events
        $this->eventService->listen('data.changed', function ($model, $changes, $user) {
            // Create audit log entry
            // Store change history
            // Notify administrators if sensitive data
        });
        
        // Security events
        $this->eventService->listen('security.violation', function ($user, $violation, $details) {
            // Log security violation
            // Notify security team
            // Take protective actions
        });
        
        // System events
        $this->eventService->listen('system.maintenance', function ($type, $details) {
            // Notify users of maintenance
            // Update system status
            // Log maintenance activity
        });
    }
    
    /**
     * Example: Simulate user registration process.
     */
    public function simulateUserRegistration(): void
    {
        $user = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        // Fire user registration event
        $this->eventService->fire('user.registered', $user);
        
        echo "User registration process completed with events fired.\n";
    }
    
    /**
     * Example: Simulate order creation process.
     */
    public function simulateOrderCreation(): void
    {
        $order = (object) [
            'id' => 123,
            'user_id' => 1,
            'total' => 99.99,
            'status' => 'pending'
        ];
        
        // Fire order creation event
        $this->eventService->fire('order.created', $order);
        
        echo "Order creation process completed with events fired.\n";
    }
    
    /**
     * Example: Simulate order status change.
     */
    public function simulateOrderStatusChange(): void
    {
        $order = (object) ['id' => 123, 'status' => 'shipped'];
        $oldStatus = 'processing';
        $newStatus = 'shipped';
        
        // Fire order status change event
        $this->eventService->fire('order.status.changed', $order, $oldStatus, $newStatus);
        
        echo "Order status change process completed with events fired.\n";
    }
    
    /**
     * Example: Simulate notification sending.
     */
    public function simulateNotificationSending(): void
    {
        $recipient = 'user@example.com';
        $template = 'welcome';
        $data = ['name' => 'John Doe'];
        
        // Fire email notification event
        $this->eventService->fire('notification.email', $recipient, $template, $data);
        
        echo "Notification sending process completed with events fired.\n";
    }
    
    /**
     * Example: Simulate audit logging.
     */
    public function simulateAuditLogging(): void
    {
        $model = (object) ['id' => 1, 'name' => 'Product'];
        $changes = ['name' => ['old' => 'Old Name', 'new' => 'New Name']];
        $user = (object) ['id' => 1, 'name' => 'Admin'];
        
        // Fire data change event
        $this->eventService->fire('data.changed', $model, $changes, $user);
        
        echo "Audit logging process completed with events fired.\n";
    }
    
    /**
     * Get all registered events.
     * 
     * @return array
     */
    public function getRegisteredEvents(): array
    {
        return $this->eventService->getEvents();
    }
    
    /**
     * Check if an event has listeners.
     * 
     * @param string $event
     * @return bool
     */
    public function hasEventListeners(string $event): bool
    {
        return $this->eventService->hasListeners($event);
    }
    
    /**
     * Clear all event listeners.
     */
    public function clearAllListeners(): void
    {
        $this->eventService->flush();
        echo "All event listeners cleared.\n";
    }
}
