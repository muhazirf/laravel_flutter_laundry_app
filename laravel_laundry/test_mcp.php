<?php

/**
 * MCP Server Configuration Test
 *
 * This file tests if your MCP server is properly configured.
 * Run with: php test_mcp.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== MCP Server Configuration Test ===\n\n";

try {
    // Test 1: Check if MCP package is installed
    echo "1. Checking MCP package installation...\n";
    if (class_exists('Laravel\Mcp\Server')) {
        echo "✓ Laravel MCP package is installed\n";
    } else {
        echo "✗ Laravel MCP package not found\n";
        exit(1);
    }

    // Test 2: Check if LaundryServer class exists
    echo "\n2. Checking LaundryServer class...\n";
    if (class_exists('App\Mcp\Servers\LaundryServer')) {
        echo "✓ LaundryServer class exists\n";
    } else {
        echo "✗ LaundryServer class not found\n";
        exit(1);
    }

    // Test 3: Check if tools exist
    echo "\n3. Checking MCP tools...\n";
    $tools = [
        'App\Mcp\Tools\OrderManagementTool',
        'App\Mcp\Tools\CustomerManagementTool',
        'App\Mcp\Tools\ServiceManagementTool',
        'App\Mcp\Tools\OutletManagementTool'
    ];

    foreach ($tools as $tool) {
        if (class_exists($tool)) {
            echo "✓ {$tool} exists\n";
        } else {
            echo "✗ {$tool} not found\n";
        }
    }

    // Test 4: Test server class configuration (without instantiation)
    echo "\n4. Testing server class configuration...\n";
    $serverClass = new ReflectionClass('App\Mcp\Servers\LaundryServer');

    // Get default properties (static analysis)
    $defaultProperties = $serverClass->getDefaultProperties();

    echo "✓ LaundryServer configuration:\n";
    echo "  - Name: " . ($defaultProperties['name'] ?? 'Not set') . "\n";
    echo "  - Version: " . ($defaultProperties['version'] ?? 'Not set') . "\n";
    echo "  - Tools count: " . count($defaultProperties['tools'] ?? []) . "\n";
    echo "  - Instructions: " . (substr($defaultProperties['instructions'] ?? 'Not set', 0, 50) . '...') . "\n";

    // Test 5: Check if routes are registered
    echo "\n5. Checking route registration...\n";
    $routes = app('router')->getRoutes();
    $mcpRoutes = [];

    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'mcp')) {
            $mcpRoutes[] = $route->uri();
        }
    }

    if (!empty($mcpRoutes)) {
        echo "✓ MCP routes found:\n";
        foreach ($mcpRoutes as $route) {
            echo "  - {$route}\n";
        }
    } else {
        echo "✗ No MCP routes found (may need to run php artisan route:clear)\n";
    }

    echo "\n=== Test Complete ===\n";
    echo "Your MCP server appears to be properly configured!\n\n";

    echo "Next steps:\n";
    echo "1. Start your Laravel server: php artisan serve\n";
    echo "2. Access MCP endpoint: http://localhost:8000/mcp/demo\n";
    echo "3. Configure your MCP client to connect to: ws://localhost:8000/mcp/demo\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}