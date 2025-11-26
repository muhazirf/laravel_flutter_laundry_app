<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tool;
use App\Models\Services;
use App\Models\ServiceVariants;

class ServiceManagementTool extends Tool
{
    /**
     * The name of the tool.
     */
    public string $name = 'service_management';

    /**
     * The description of the tool.
     */
    public string $description = 'Manage laundry services and pricing variants';

    /**
     * The input schema for the tool.
     */
    public array $inputSchema = [
        'type' => 'object',
        'properties' => [
            'action' => [
                'type' => 'string',
                'enum' => ['list', 'show', 'create', 'update', 'variants'],
                'description' => 'The action to perform'
            ],
            'service_data' => [
                'type' => 'object',
                'description' => 'Service data for creation/update',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'category' => ['type' => 'string'],
                    'base_price' => ['type' => 'number'],
                    'estimated_time_hours' => ['type' => 'number']
                ]
            ],
            'service_id' => [
                'type' => 'integer',
                'description' => 'Service ID for show/update actions'
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
                'list' => $this->listServices(),
                'show' => $this->showService($input['service_id']),
                'create' => $this->createService($input['service_data'] ?? []),
                'update' => $this->updateService($input['service_id'], $input['service_data'] ?? []),
                'variants' => $this->getServiceVariants($input['service_id']),
                default => ['error' => 'Invalid action']
            };
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function listServices(): array
    {
        $services = Services::with('variants')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return [
            'success' => true,
            'services' => $services->toArray()
        ];
    }

    private function showService(int $serviceId): array
    {
        $service = Services::with(['variants', 'orderItems'])
            ->find($serviceId);

        if (!$service) {
            return ['error' => 'Service not found'];
        }

        return [
            'success' => true,
            'service' => $service->toArray()
        ];
    }

    private function createService(array $data): array
    {
        $service = Services::create([
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'general',
            'base_price' => $data['base_price'] ?? 0,
            'estimated_time_hours' => $data['estimated_time_hours'] ?? 24,
            'is_active' => true,
        ]);

        return [
            'success' => true,
            'service' => $service->toArray()
        ];
    }

    private function updateService(int $serviceId, array $data): array
    {
        $service = Services::find($serviceId);
        if (!$service) {
            return ['error' => 'Service not found'];
        }

        $service->update(array_filter($data, fn($value) => $value !== null));

        return [
            'success' => true,
            'service' => $service->fresh()->toArray()
        ];
    }

    private function getServiceVariants(int $serviceId): array
    {
        $variants = ServiceVariants::where('service_id', $serviceId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'success' => true,
            'variants' => $variants->toArray()
        ];
    }
}