<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tool;
use App\Models\Outlets;

class OutletManagementTool extends Tool
{
    /**
     * The name of the tool.
     */
    public string $name = 'outlet_management';

    /**
     * The description of the tool.
     */
    public string $description = 'Manage laundry outlets - locations, operating hours, and capacity';

    /**
     * The input schema for the tool.
     */
    public array $inputSchema = [
        'type' => 'object',
        'properties' => [
            'action' => [
                'type' => 'string',
                'enum' => ['list', 'show', 'create', 'update', 'nearby'],
                'description' => 'The action to perform'
            ],
            'outlet_data' => [
                'type' => 'object',
                'description' => 'Outlet data for creation/update',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'address' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'latitude' => ['type' => 'number'],
                    'longitude' => ['type' => 'number'],
                    'operating_hours' => ['type' => 'string'],
                    'max_capacity' => ['type' => 'integer']
                ]
            ],
            'outlet_id' => [
                'type' => 'integer',
                'description' => 'Outlet ID for show/update actions'
            ],
            'latitude' => [
                'type' => 'number',
                'description' => 'User latitude for nearby outlets'
            ],
            'longitude' => [
                'type' => 'number',
                'description' => 'User longitude for nearby outlets'
            ],
            'radius_km' => [
                'type' => 'number',
                'description' => 'Search radius in kilometers (default: 10)'
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
                'list' => $this->listOutlets(),
                'show' => $this->showOutlet($input['outlet_id']),
                'create' => $this->createOutlet($input['outlet_data'] ?? []),
                'update' => $this->updateOutlet($input['outlet_id'], $input['outlet_data'] ?? []),
                'nearby' => $this->getNearbyOutlets($input),
                default => ['error' => 'Invalid action']
            };
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function listOutlets(): array
    {
        $outlets = Outlets::where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'success' => true,
            'outlets' => $outlets->toArray()
        ];
    }

    private function showOutlet(int $outletId): array
    {
        $outlet = Outlets::with(['orders' => function($query) {
            $query->latest()->limit(10);
        }])->find($outletId);

        if (!$outlet) {
            return ['error' => 'Outlet not found'];
        }

        return [
            'success' => true,
            'outlet' => $outlet->toArray()
        ];
    }

    private function createOutlet(array $data): array
    {
        $outlet = Outlets::create([
            'name' => $data['name'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'operating_hours' => $data['operating_hours'] ?? '08:00-20:00',
            'max_capacity' => $data['max_capacity'] ?? 100,
            'is_active' => true,
        ]);

        return [
            'success' => true,
            'outlet' => $outlet->toArray()
        ];
    }

    private function updateOutlet(int $outletId, array $data): array
    {
        $outlet = Outlets::find($outletId);
        if (!$outlet) {
            return ['error' => 'Outlet not found'];
        }

        $outlet->update(array_filter($data, fn($value) => $value !== null));

        return [
            'success' => true,
            'outlet' => $outlet->fresh()->toArray()
        ];
    }

    private function getNearbyOutlets(array $input): array
    {
        $latitude = $input['latitude'] ?? null;
        $longitude = $input['longitude'] ?? null;
        $radiusKm = $input['radius_km'] ?? 10;

        if (!$latitude || !$longitude) {
            return ['error' => 'Latitude and longitude are required for nearby search'];
        }

        $outlets = Outlets::where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('*,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) as distance',
                [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->get();

        return [
            'success' => true,
            'outlets' => $outlets->toArray(),
            'search_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius_km' => $radiusKm
            ]
        ];
    }
}