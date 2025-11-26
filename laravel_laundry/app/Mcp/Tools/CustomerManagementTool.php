<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tool;
use App\Models\Customers;

class CustomerManagementTool extends Tool
{
    /**
     * The name of the tool.
     */
    public string $name = 'customer_management';

    /**
     * The description of the tool.
     */
    public string $description = 'Manage customers - create, read, update customer information';

    /**
     * The input schema for the tool.
     */
    public array $inputSchema = [
        'type' => 'object',
        'properties' => [
            'action' => [
                'type' => 'string',
                'enum' => ['create', 'list', 'show', 'update', 'search'],
                'description' => 'The action to perform'
            ],
            'customer_data' => [
                'type' => 'object',
                'description' => 'Customer data for creation/update',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'address' => ['type' => 'string'],
                    'notes' => ['type' => 'string']
                ]
            ],
            'customer_id' => [
                'type' => 'integer',
                'description' => 'Customer ID for show/update actions'
            ],
            'search_term' => [
                'type' => 'string',
                'description' => 'Search term to find customers'
            ]
        ],
        'required' => ['action']
    ];

    /**
     * Execute the tool.
     */
    public function execute(array $input): array
    {
        $action = $input['action'];

        try {
            return match ($action) {
                'create' => $this->createCustomer($input['customer_data'] ?? []),
                'list' => $this->listCustomers(),
                'show' => $this->showCustomer($input['customer_id']),
                'update' => $this->updateCustomer($input['customer_id'], $input['customer_data'] ?? []),
                'search' => $this->searchCustomers($input['search_term'] ?? ''),
                default => ['error' => 'Invalid action']
            };
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function createCustomer(array $data): array
    {
        $customer = Customers::create([
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'active',
        ]);

        return [
            'success' => true,
            'customer' => $customer->toArray()
        ];
    }

    private function listCustomers(): array
    {
        $customers = Customers::orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return [
            'success' => true,
            'customers' => $customers->toArray()
        ];
    }

    private function showCustomer(int $customerId): array
    {
        $customer = Customers::with(['orders' => function($query) {
            $query->latest()->limit(10);
        }])->find($customerId);

        if (!$customer) {
            return ['error' => 'Customer not found'];
        }

        return [
            'success' => true,
            'customer' => $customer->toArray()
        ];
    }

    private function updateCustomer(int $customerId, array $data): array
    {
        $customer = Customers::find($customerId);
        if (!$customer) {
            return ['error' => 'Customer not found'];
        }

        $customer->update(array_filter($data, fn($value) => $value !== null));

        return [
            'success' => true,
            'customer' => $customer->fresh()->toArray()
        ];
    }

    private function searchCustomers(string $searchTerm): array
    {
        $customers = Customers::where('name', 'like', "%{$searchTerm}%")
            ->orWhere('email', 'like', "%{$searchTerm}%")
            ->orWhere('phone', 'like', "%{$searchTerm}%")
            ->orderBy('name')
            ->limit(20)
            ->get();

        return [
            'success' => true,
            'customers' => $customers->toArray(),
            'search_term' => $searchTerm
        ];
    }
}