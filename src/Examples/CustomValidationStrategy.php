<?php

namespace SgFlores\Cruder\Examples;

use SgFlores\Cruder\Strategies\Validation\ValidationStrategyInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Custom Validation Strategy Example
 * 
 * Demonstrates how to create a custom validation strategy for specific business rules.
 * Shows complex validation logic with database checks and custom error messages.
 */
class CustomValidationStrategy implements ValidationStrategyInterface
{
    /**
     * Validate data using custom business rules.
     * 
     * @param array $data The data to validate
     * @param string $operation The operation type (create, update, etc.)
     * @param array $context Additional context for validation
     * @return array|bool The validated data or true if valid
     * @throws ValidationException If validation fails
     */
    public function validate(array $data, string $operation, array $context = []): array|bool
    {
        // Get base validation rules
        $rules = $this->getBaseRules($operation);
        
        // Add operation-specific rules
        $rules = array_merge($rules, $this->getOperationSpecificRules($operation, $context));
        
        // Add custom business rules
        $rules = array_merge($rules, $this->getBusinessRules($data, $operation, $context));
        
        // Create validator
        $validator = Validator::make($data, $rules, $this->getCustomMessages());
        
        // Add custom validation rules
        $this->addCustomValidationRules($validator, $data, $operation, $context);
        
        // Validate
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        // Post-validation processing
        $validatedData = $validator->validated();
        $this->postValidationProcessing($validatedData, $operation, $context);
        
        return $validatedData;
    }
    
    /**
     * Get base validation rules.
     * 
     * @param string $operation
     * @return array
     */
    protected function getBaseRules(string $operation): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'status' => 'required|in:active,inactive,pending',
        ];
    }
    
    /**
     * Get operation-specific validation rules.
     * 
     * @param string $operation
     * @param array $context
     * @return array
     */
    protected function getOperationSpecificRules(string $operation, array $context): array
    {
        $rules = [];
        
        switch ($operation) {
            case 'create':
                $rules['password'] = 'required|string|min:8|confirmed';
                $rules['terms_accepted'] = 'required|accepted';
                break;
                
            case 'update':
                $rules['password'] = 'sometimes|string|min:8|confirmed';
                if (isset($context['model'])) {
                    // Add unique email rule excluding current model
                    $rules['email'] = 'required|email|max:255|unique:users,email,' . $context['model']->id;
                }
                break;
                
            case 'bulk_create':
                $rules['*.name'] = 'required|string|max:255';
                $rules['*.email'] = 'required|email|max:255';
                break;
        }
        
        return $rules;
    }
    
    /**
     * Get custom business rules.
     * 
     * @param array $data
     * @param string $operation
     * @param array $context
     * @return array
     */
    protected function getBusinessRules(array $data, string $operation, array $context): array
    {
        $rules = [];
        
        // Business rule: Check if user can be created based on company limits
        if ($operation === 'create' && isset($data['company_id'])) {
            // Add validation to check company user limit
            $rules['company_id'] = 'required|exists:companies,id';
        }
        
        // Business rule: Check if email domain is allowed
        if (isset($data['email'])) {
            $rules['email'] = array_merge(
                $rules['email'] ?? [],
                ['regex:/^[a-zA-Z0-9._%+-]+@(allowed-domain\.com|company\.org)$/']
            );
        }
        
        return $rules;
    }
    
    /**
     * Get custom validation messages.
     * 
     * @return array
     */
    protected function getCustomMessages(): array
    {
        return [
            'email.regex' => 'Email must be from an allowed domain.',
            'company_id.exists' => 'The selected company does not exist or is not active.',
            'terms_accepted.required' => 'You must accept the terms and conditions.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions.',
        ];
    }
    
    /**
     * Add custom validation rules to the validator.
     * 
     * @param \Illuminate\Validation\Validator $validator
     * @param array $data
     * @param string $operation
     * @param array $context
     * @return void
     */
    protected function addCustomValidationRules($validator, array $data, string $operation, array $context): void
    {
        // Custom rule: Check company user limit
        $validator->after(function ($validator) use ($data, $operation) {
            if ($operation === 'create' && isset($data['company_id'])) {
                // Check if company has reached user limit
                // $userCount = User::where('company_id', $data['company_id'])->count();
                // $company = Company::find($data['company_id']);
                // if ($userCount >= $company->user_limit) {
                //     $validator->errors()->add('company_id', 'Company has reached maximum user limit.');
                // }
            }
        });
        
        // Custom rule: Check business hours for account creation
        $validator->after(function ($validator) use ($operation) {
            if ($operation === 'create') {
                $currentHour = now()->hour;
                if ($currentHour < 9 || $currentHour > 17) {
                    // $validator->errors()->add('created_at', 'Accounts can only be created during business hours (9 AM - 5 PM).');
                }
            }
        });
        
        // Custom rule: Validate role assignments
        $validator->after(function ($validator) use ($data, $operation, $context) {
            if (isset($data['roles']) && is_array($data['roles'])) {
                // Check if user has permission to assign these roles
                // $currentUser = auth()->user();
                // $assignableRoles = $currentUser->getAssignableRoles();
                // $invalidRoles = array_diff($data['roles'], $assignableRoles);
                // if (!empty($invalidRoles)) {
                //     $validator->errors()->add('roles', 'You do not have permission to assign these roles: ' . implode(', ', $invalidRoles));
                // }
            }
        });
    }
    
    /**
     * Post-validation processing.
     * 
     * @param array $validatedData
     * @param string $operation
     * @param array $context
     * @return void
     */
    protected function postValidationProcessing(array &$validatedData, string $operation, array $context): void
    {
        // Hash password if present
        if (isset($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        }
        
        // Set default values
        if ($operation === 'create') {
            $validatedData['status'] = $validatedData['status'] ?? 'pending';
            $validatedData['email_verified_at'] = null;
        }
        
        // Add audit fields
        if ($operation === 'create') {
            $validatedData['created_by'] = 1; // Example: current user ID
        } elseif ($operation === 'update') {
            $validatedData['updated_by'] = 1; // Example: current user ID
        }
        
        // Remove confirmation fields
        unset($validatedData['password_confirmation']);
        unset($validatedData['terms_accepted']);
    }
}
