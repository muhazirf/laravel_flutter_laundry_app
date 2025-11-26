<?php

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

// Register the Laundry server for CLI usage
Mcp::local('laundry', \App\Mcp\Servers\LaundryServer::class);

// Apply CORS middleware to MCP routes
Route::middleware(['mcp.cors'])->group(function () {
    Mcp::web('/mcp/demo', \App\Mcp\Servers\LaundryServer::class);
});
