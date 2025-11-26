const express = require('express');
const fetch = require('node-fetch');

const app = express();
const PORT = 3000;

// Enable CORS for all routes
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    if (req.method === 'OPTIONS') {
        res.sendStatus(200);
    } else {
        next();
    }
});

app.use(express.json());

// Proxy all requests to MCP server
app.all('/mcp/*', async (req, res) => {
    try {
        const mcpUrl = `http://localhost:8181/mcp/demo`;

        const response = await fetch(mcpUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(req.body)
        });

        const data = await response.json();
        res.json(data);

    } catch (error) {
        console.error('Proxy error:', error);
        res.status(500).json({ error: error.message });
    }
});

// Serve the test page
app.get('/', (req, res) => {
    res.send(`
<!DOCTYPE html>
<html>
<head>
    <title>MCP Server Test (via Proxy)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        button { padding: 10px 20px; margin: 5px; background: #007cba; color: white; border: none; cursor: pointer; }
        button:hover { background: #005a87; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .response { margin-top: 10px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Laundry App MCP Server Test (Docker Proxy)</h1>

    <div class="test-section">
        <h3>🧪 Test MCP Connection</h3>
        <button onclick="testMCP('listTools')">📋 List Available Tools</button>
        <button onclick="testMCP('listCustomers')">👥 List Customers</button>
        <button onclick="testMCP('listOrders')">📦 List Orders</button>
        <button onclick="testMCP('createCustomer')">➕ Create Test Customer</button>
        <div id="response" class="response"></div>
    </div>

    <script>
        const PROXY_URL = '';

        async function testMCP(type) {
            const responseDiv = document.getElementById('response');
            responseDiv.innerHTML = '<p>🔄 Testing...</p>';

            let requestBody;

            switch(type) {
                case 'listTools':
                    requestBody = {
                        jsonrpc: "2.0",
                        id: Date.now(),
                        method: "tools/list"
                    };
                    break;
                case 'listCustomers':
                    requestBody = {
                        jsonrpc: "2.0",
                        id: Date.now(),
                        method: "tools/call",
                        params: {
                            name: "customer_management",
                            arguments: { action: "list" }
                        }
                    };
                    break;
                case 'listOrders':
                    requestBody = {
                        jsonrpc: "2.0",
                        id: Date.now(),
                        method: "tools/call",
                        params: {
                            name: "order_management",
                            arguments: { action: "list" }
                        }
                    };
                    break;
                case 'createCustomer':
                    requestBody = {
                        jsonrpc: "2.0",
                        id: Date.now(),
                        method: "tools/call",
                        params: {
                            name: "customer_management",
                            arguments: {
                                action: "create",
                                customer_data: {
                                    name: "Test Customer",
                                    email: "test@example.com",
                                    phone: "+1234567890"
                                }
                            }
                        }
                    };
                    break;
            }

            try {
                const response = await fetch(PROXY_URL + '/mcp/demo', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestBody)
                });

                const data = await response.json();

                if (data.error) {
                    responseDiv.innerHTML = \`
                        <h4 class="error">❌ Error:</h4>
                        <pre>\${JSON.stringify(data.error, null, 2)}</pre>
                    \`;
                } else {
                    responseDiv.innerHTML = \`
                        <h4 class="success">✅ Success:</h4>
                        <pre>\${JSON.stringify(data, null, 2)}</pre>
                    \`;
                }

            } catch (error) {
                responseDiv.innerHTML = \`
                    <h4 class="error">❌ Network Error:</h4>
                    <pre>\${error.message}</pre>
                    <p><strong>Make sure your Laravel Docker container is running on port 8181!</strong></p>
                \`;
            }
        }

        // Auto-test on page load
        window.onload = () => testMCP('listTools');
    </script>
</body>
</html>
    `);
});

app.listen(PORT, () => {
    console.log(\`🚀 MCP Proxy running on http://localhost:\${PORT}\`);
    console.log(\`📝 Test page: http://localhost:\${PORT}\`);
    console.log(\`🔗 Proxying to: http://localhost:8181/mcp/demo\`);
});