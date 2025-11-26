<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use App\Mcp\Tools\OrderManagementTool;
use App\Mcp\Tools\CustomerManagementTool;
use App\Mcp\Tools\ServiceManagementTool;
use App\Mcp\Tools\OutletManagementTool;

class LaundryServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Laundry App API Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = 'You are an AI assistant for a laundry management system. You can help manage customers, orders, services, outlets, and payments. Use the available tools to perform operations like creating orders, checking order status, managing customers, and handling laundry services. Always validate input data and provide helpful feedback to users.';

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        OrderManagementTool::class,
        CustomerManagementTool::class,
        ServiceManagementTool::class,
        OutletManagementTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
