<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tool;
use App\Models\Orders;
use App\Models\OrderItem;
use App\Models\Customers;

class OrderManagementTool extends Tool
{
    /**
     * The name of the tool.
     */
    public string $name = 'order_management';

    /**
     * The description of the tool.
     */
    public string $description = 'Manage laundry orders - create, read, update, and track orders';

    /**
     * The input schema for the tool.
     */
    public array $inputSchema = [
        'type' => 'object',
        'properties' => [
            'action' => [
                'type' => 'string',
                'enum' => ['create', 'list', 'show', 'update_status', 'by_customer'],
                'description' => 'The action to perform'
            ],
            'order_data' => [
                'type' => 'object',
                'description' => 'Order data for creation',
                'properties' => [
                    'customer_id' => ['type' => 'integer'],
                    'outlet_id' => ['type' => 'integer'],
                    'estimated_weight' => ['type' => 'number'],
                    'notes' => ['type' => 'string']
                ]
            ],
            'order_id' => [
                'type' => 'integer',
                'description' => 'Order ID for show/update actions'
            ],
            'status' => [
                'type' => 'string',
                'description' => 'New status for order'
            ],
            'customer_id' => [
                'type' => 'integer',
                'description' => 'Customer ID to filter orders'
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
                'create' => $this->createOrder($input['order_data'] ?? []),
                'list' => $this->listOrders(),
                'show' => $this->showOrder($input['order_id']),
                'update_status' => $this->updateOrderStatus($input['order_id'], $input['status']),
                'by_customer' => $this->getOrdersByCustomer($input['customer_id']),
                default => ['error' => 'Invalid action']
            };
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function createOrder(array $data): array
    {
        $order = Orders::create([
            'customer_id' => $data['customer_id'] ?? null,
            'outlet_id' => $data['outlet_id'] ?? null,
            'order_number' => 'ORD-' . time(),
            'status' => 'pending',
            'estimated_weight' => $data['estimated_weight'] ?? null,
            'notes' => $data['notes'] ?? null,
            'total_amount' => 0,
        ]);

        return [
            'success' => true,
            'order' => $order->load(['customer', 'outlet'])
        ];
    }

    private function listOrders(): array
    {
        $orders = Orders::with(['customer', 'outlet', 'items.service'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return [
            'success' => true,
            'orders' => $orders->toArray()
        ];
    }

    private function showOrder(int $orderId): array
    {
        $order = Orders::with(['customer', 'outlet', 'items.service', 'statusHistory'])
            ->find($orderId);

        if (!$order) {
            return ['error' => 'Order not found'];
        }

        return [
            'success' => true,
            'order' => $order->toArray()
        ];
    }

    private function updateOrderStatus(int $orderId, string $status): array
    {
        $order = Orders::find($orderId);
        if (!$order) {
            return ['error' => 'Order not found'];
        }

        $order->status = $status;
        $order->save();

        // Create status history
        OrderStatusHistory::create([
            'order_id' => $orderId,
            'status' => $status,
            'changed_by' => auth()->id(),
            'notes' => "Status changed to {$status}"
        ]);

        return [
            'success' => true,
            'order' => $order->fresh()->load(['customer', 'outlet'])
        ];
    }

    private function getOrdersByCustomer(int $customerId): array
    {
        $orders = Orders::with(['outlet', 'items.service'])
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'success' => true,
            'orders' => $orders->toArray()
        ];
    }
}