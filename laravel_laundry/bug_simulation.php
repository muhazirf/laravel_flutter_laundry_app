<?php

/**
 * Simulasi bug untuk demonstrasi
 * Show what the broken code would do vs fixed code
 */

echo "=== BUG SIMULATION DEMO ===\n\n";

// Simulated request data
$requestData = [
    'email' => 'victim@example.com',
    'password' => 'any_random_text_or_empty'
];

echo "Input Request:\n";
echo "Email: {$requestData['email']}\n";
echo "Password: {$requestData['password']}\n\n";

// === BUGGY VERSION (Before Fix) ===
echo "❌ BUGGY VERSION (Before Fix):\n";
echo "Code: \$user = JWTAuth::user(); // Tanpa validasi!\n";
echo "Result: User bisa login tanpa validasi password\n";
echo "Security Impact: CRITICAL - Authentication bypass\n\n";

// === FIXED VERSION (After Fix) ===
echo "✅ FIXED VERSION (After Fix):\n";
echo "Code: if (!\$token = JWTAuth::attempt(\$credentials)) {\n";
echo "           return unauthorized('Invalid credentials');\n";
echo "       }\n";
echo "       \$user = JWTAuth::user(); // Setelah validasi\n\n";

// Simulate what JWTAuth::attempt does
$simulatedCredentials = [
    'email' => $requestData['email'],
    'password' => $requestData['password']
];

echo "JWTAuth::attempt() akan:\n";
echo "1. Cek email di database\n";
echo "2. Hash input password dengan bcrypt\n";
echo "3. Compare dengan stored hash di database\n";
echo "4. Return token JIKA valid, false JIKA invalid\n\n";

// Simulate outcomes
if ($requestData['password'] === 'correct_password') {
    echo "🔓 Result: SUCCESS - Valid credentials\n";
    echo "✅ HTTP 200 - JWT token generated\n";
    echo "✅ User properly authenticated\n";
} else {
    echo "🔒 Result: FAILED - Invalid credentials\n";
    echo "❌ HTTP 401 - Authentication denied\n";
    echo "✅ Security maintained\n";
}

echo "\n=== SECURITY COMPARISON ===\n";
echo "Before Fix: 0/10 - Anyone can login with email only\n";
echo "After Fix:  10/10 - Proper authentication required\n";

?>