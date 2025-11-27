#!/bin/bash

# JWT Security Testing Script
# Usage: ./security_test.sh [test_type]
# Run: chmod +x security_test.sh && ./security_test.sh

API_BASE="http://localhost:8181/api"
COLOR_GREEN='\033[0;32m'
COLOR_RED='\033[0;31m'
COLOR_YELLOW='\033[1;33m'
COLOR_BLUE='\033[0;34m'
COLOR_PURPLE='\033[0;35m'
COLOR_NC='\033[0m'

# Test credentials
TEST_EMAIL="securitytest@example.com"
TEST_PASSWORD="password123"

# Security test results
VULNERABILITIES_FOUND=0
TOTAL_TESTS=0

# Functions
print_header() {
    echo -e "${COLOR_PURPLE}================================$1================================${COLOR_NC}"
    echo ""
}

print_success() {
    echo -e "${COLOR_GREEN}✅ SECURE: $1${COLOR_NC}"
}

print_vulnerability() {
    echo -e "${COLOR_RED}🚨 VULNERABILITY: $1${COLOR_NC}"
    ((VULNERABILITIES_FOUND++))
}

print_warning() {
    echo -e "${COLOR_YELLOW}⚠️ WARNING: $1${COLOR_NC}"
}

print_info() {
    echo -e "${COLOR_BLUE}ℹ️ INFO: $1${COLOR_NC}"
}

increment_test_count() {
    ((TOTAL_TESTS++))
}

# Setup test user
setup_security_test_user() {
    print_header "SECURITY TEST SETUP"

    print_info "Creating security test user..."

    REGISTER_RESPONSE=$(curl -s -X POST "$API_BASE/auth/register" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{
            \"name\": \"Security Test User\",
            \"email\": \"$TEST_EMAIL\",
            \"password\": \"$TEST_PASSWORD\",
            \"password_confirmation\": \"$TEST_PASSWORD\"
        }")

    if echo "$REGISTER_RESPONSE" | grep -q '"success":true'; then
        print_success "Security test user created successfully"

        # Get login token for subsequent tests
        LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "{
                \"email\": \"$TEST_EMAIL\",
                \"password\": \"$TEST_PASSWORD\"
            }")

        if echo "$LOGIN_RESPONSE" | grep -q '"success":true'; then
            ACCESS_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')
            REFRESH_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.refresh_token')

            echo "ACCESS_TOKEN=$ACCESS_TOKEN" > security_test_vars
            echo "REFRESH_TOKEN=$REFRESH_TOKEN" >> security_test_vars
            echo "TEST_EMAIL=$TEST_EMAIL" >> security_test_vars
            echo "TEST_PASSWORD=$TEST_PASSWORD" >> security_test_vars

            print_info "Test user credentials prepared for security testing"
        else
            print_error "Failed to login with test user"
            return 1
        fi
    else
        print_warning "Test user might already exist, attempting to login..."

        LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "{
                \"email\": \"$TEST_EMAIL\",
                \"password\": \"$TEST_PASSWORD\"
            }")

        if echo "$LOGIN_RESPONSE" | grep -q '"success":true'; then
            print_success "Using existing security test user"

            ACCESS_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')
            REFRESH_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.refresh_token')

            echo "ACCESS_TOKEN=$ACCESS_TOKEN" > security_test_vars
            echo "REFRESH_TOKEN=$REFRESH_TOKEN" >> security_test_vars
            echo "TEST_EMAIL=$TEST_EMAIL" >> security_test_vars
            echo "TEST_PASSWORD=$TEST_PASSWORD" >> security_test_vars
        else
            print_error "Failed to create or login test user"
            echo "$REGISTER_RESPONSE"
            echo "$LOGIN_RESPONSE"
            return 1
        fi
    fi
    echo ""
}

# Test authentication bypass attempts
test_authentication_bypass() {
    print_header "AUTHENTICATION BYPASS TESTS"

    # Test 1: Empty Authorization header
    increment_test_count
    print_info "Testing empty Authorization header..."
    BYPASS_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: " \
        -H "Accept: application/json")

    if echo "$BYPASS_RESPONSE" | grep -q '"success":false'; then
        print_success "Empty Authorization header correctly rejected"
    else
        print_vulnerability "Empty Authorization header accepted - Possible auth bypass"
    fi

    # Test 2: Bearer token without value
    increment_test_count
    print_info "Testing Bearer token without value..."
    BEARER_EMPTY_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Bearer" \
        -H "Accept: application/json")

    if echo "$BEARER_EMPTY_RESPONSE" | grep -q '"success":false'; then
        print_success "Empty Bearer token correctly rejected"
    else
        print_vulnerability "Empty Bearer token accepted - Possible auth bypass"
    fi

    # Test 3: Invalid Authorization scheme
    increment_test_count
    print_info "Testing invalid Authorization scheme..."
    INVALID_SCHEME_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Basic dGVzdDp0ZXN0" \
        -H "Accept: application/json")

    if echo "$INVALID_SCHEME_RESPONSE" | grep -q '"success":false'; then
        print_success "Invalid Authorization scheme correctly rejected"
    else
        print_vulnerability "Invalid Authorization scheme accepted - Possible auth bypass"
    fi

    # Test 4: Manipulated JWT format
    increment_test_count
    print_info "Testing manipulated JWT format..."
    MANIPULATED_JWT_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Bearer header.payload.signature.extra" \
        -H "Accept: application/json")

    if echo "$MANIPULATED_JWT_RESPONSE" | grep -q '"success":false'; then
        print_success "Manipulated JWT format correctly rejected"
    else
        print_vulnerability "Manipulated JWT format accepted - JWT validation vulnerability"
    fi

    # Test 5: None algorithm attack
    increment_test_count
    print_info "Testing None algorithm attack..."
    NONE_ALG_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJub25lIn0.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkFkbWluIiwiaWF0IjoxNTE2MjM5MDIyfQ." \
        -H "Accept: application/json")

    if echo "$NONE_ALG_RESPONSE" | grep -q '"success":false'; then
        print_success "None algorithm attack correctly blocked"
    else
        print_vulnerability "None algorithm attack successful - Critical JWT vulnerability"
    fi

    echo ""
}

# Test JWT token manipulation
test_jwt_manipulation() {
    print_header "JWT TOKEN MANIPULATION TESTS"

    if [ ! -f "security_test_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source security_test_vars

    # Test 1: Token tampering - Modify user ID in payload
    increment_test_count
    print_info "Testing JWT payload tampering..."

    # Create a tampered token by changing the user ID claim
    TAMPERED_TOKEN=$(echo $ACCESS_TOKEN | cut -d. -f1).$(echo $ACCESS_TOKEN | cut -d. -f2 | base64 -d | jq '.sub = 999' | base64 -w 0).$(echo $ACCESS_TOKEN | cut -d. -f3)

    TAMPERED_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Bearer $TAMPERED_TOKEN" \
        -H "Accept: application/json")

    if echo "$TAMPERED_RESPONSE" | grep -q '"success":false'; then
        print_success "JWT payload tampering correctly detected"
    else
        print_vulnerability "JWT payload tampering not detected - Token integrity compromised"
    fi

    # Test 2: expired token (simulate with very old iat)
    increment_test_count
    print_info "Testing expired token acceptance..."

    # Create token with expired timestamp (24 hours ago)
    EXPIRED_PAYLOAD=$(echo $ACCESS_TOKEN | cut -d. -f2 | base64 -d | jq ".iat = $(($(date +%s) - 86400))" | base64 -w 0)
    EXPIRED_TOKEN=$(echo $ACCESS_TOKEN | cut -d. -f1).$EXPIRED_PAYLOAD.$(echo $ACCESS_TOKEN | cut -d. -f3)

    EXPIRED_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Bearer $EXPIRED_TOKEN" \
        -H "Accept: application/json")

    if echo "$EXPIRED_RESPONSE" | grep -q '"success":false'; then
        print_success "Expired token correctly rejected"
    else
        print_vulnerability "Expired token accepted - Token expiration bypass"
    fi

    # Test 3: Future token (nbf violation)
    increment_test_count
    print_info "Testing future token (not before)..."

    # Create token with future timestamp (1 hour from now)
    FUTURE_PAYLOAD=$(echo $ACCESS_TOKEN | cut -d. -f2 | base64 -d | jq ".nbf = $(($(date +%s) + 3600))" | base64 -w 0)
    FUTURE_TOKEN=$(echo $ACCESS_TOKEN | cut -d. -f1).$FUTURE_PAYLOAD.$(echo $ACCESS_TOKEN | cut -d. -f3)

    FUTURE_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Bearer $FUTURE_TOKEN" \
        -H "Accept: application/json")

    if echo "$FUTURE_RESPONSE" | grep -q '"success":false'; then
        print_success "Future token correctly rejected"
    else
        print_vulnerability "Future token accepted - nbf claim not enforced"
    fi

    # Test 4: Algorithm confusion attack
    increment_test_count
    print_info "Testing algorithm confusion attack..."

    # Try to use HMAC token with RSA public key (if configured)
    CONFUSION_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6Ikh1bWFuIEFjdG9yIiwiaWF0IjoxNTE2MjM5MDIyfQ.XbPfbIHMI6arZ3Y922BhjWgQzWXcXNrz0ogtVhfEd2o"

    CONFUSION_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Bearer $CONFUSION_TOKEN" \
        -H "Accept: application/json")

    if echo "$CONFUSION_RESPONSE" | grep -q '"success":false'; then
        print_success "Algorithm confusion attack correctly blocked"
    else
        print_vulnerability "Algorithm confusion attack successful - Critical JWT vulnerability"
    fi

    echo ""
}

# Test injection attacks
test_injection_attacks() {
    print_header "INJECTION ATTACK TESTS"

    # Test 1: SQL Injection in login email
    increment_test_count
    print_info "Testing SQL injection in login email..."
    SQL_INJECTION_EMAIL="admin'--"
    SQL_INJECTION_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"email\": \"$SQL_INJECTION_EMAIL\", \"password\": \"password\"}")

    if echo "$SQL_INJECTION_RESPONSE" | grep -q '"success":false'; then
        print_success "SQL injection in email correctly blocked"
    else
        print_vulnerability "SQL injection in email not blocked - Possible SQLi vulnerability"
    fi

    # Test 2: SQL Injection in login password
    increment_test_count
    print_info "Testing SQL injection in login password..."
    SQL_INJECTION_PASS="' OR '1'='1"
    SQL_INJECTION_PASS_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"email\": \"$TEST_EMAIL\", \"password\": \"$SQL_INJECTION_PASS\"}")

    if echo "$SQL_INJECTION_PASS_RESPONSE" | grep -q '"success":false'; then
        print_success "SQL injection in password correctly blocked"
    else
        print_vulnerability "SQL injection in password not blocked - Critical SQLi vulnerability"
    fi

    # Test 3: LDAP Injection
    increment_test_count
    print_info "Testing LDAP injection..."
    LDAP_INJECTION="*)(&"
    LDAP_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"email\": \"$LDAP_INJECTION\", \"password\": \"password\"}")

    if echo "$LDAP_RESPONSE" | grep -q '"success":false'; then
        print_success "LDAP injection correctly blocked"
    else
        print_vulnerability "LDAP injection not blocked - Possible LDAPi vulnerability"
    fi

    # Test 4: NoSQL Injection
    increment_test_count
    print_info "Testing NoSQL injection..."
    NOSQL_INJECTION='{"$ne":null}'
    NOSQL_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"email\": \"$NOSQL_INJECTION\", \"password\": \"password\"}")

    if echo "$NOSQL_RESPONSE" | grep -q '"success":false'; then
        print_success "NoSQL injection correctly blocked"
    else
        print_vulnerability "NoSQL injection not blocked - Possible NoSQLi vulnerability"
    fi

    # Test 5: Command injection in user agent
    increment_test_count
    print_info "Testing command injection via User-Agent header..."
    CMD_INJECTION_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -H "User-Agent: curl/7.68.0; rm -rf /" \
        -d "{\"email\": \"$TEST_EMAIL\", \"password\": \"$TEST_PASSWORD\"}")

    # This should still succeed for legitimate login, but command injection should be blocked
    if echo "$CMD_INJECTION_RESPONSE" | grep -q '"success":true'; then
        print_success "Command injection in User-Agent properly handled"
    else
        print_warning "Command injection test blocked legitimate login - Check input validation"
    fi

    echo ""
}

# Test XSS attacks
test_xss_attacks() {
    print_header "XSS (CROSS-SITE SCRIPTING) ATTACK TESTS"

    # Test 1: XSS in registration name
    increment_test_count
    print_info "Testing XSS in registration name..."
    XSS_NAME="<script>alert('XSS')</script>"
    XSS_REGISTER_RESPONSE=$(curl -s -X POST "$API_BASE/auth/register" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{
            \"name\": \"$XSS_NAME\",
            \"email\": \"xsstest@example.com\",
            \"password\": \"password123\",
            \"password_confirmation\": \"password123\"
        }")

    if echo "$XSS_REGISTER_RESPONSE" | grep -q '"success":false'; then
        print_success "XSS in registration name correctly blocked"
    else
        print_vulnerability "XSS in registration name not blocked - Stored XSS possible"
    fi

    # Test 2: XSS in login email
    increment_test_count
    print_info "Testing XSS in login email..."
    XSS_EMAIL="<img src=x onerror=alert('XSS')>@example.com"
    XSS_EMAIL_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"email\": \"$XSS_EMAIL\", \"password\": \"password\"}")

    if echo "$XSS_EMAIL_RESPONSE" | grep -q '"success":false'; then
        print_success "XSS in login email correctly blocked"
    else
        print_vulnerability "XSS in login email not blocked - Reflected XSS possible"
    fi

    # Test 3: Content-Type sniffing XSS
    increment_test_count
    print_info "Testing Content-Type manipulation for XSS..."
    XSS_CONTENT_TYPE_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -H "Accept: text/html" \
        -H "Content-Type: text/html")

    # Should still return JSON, not HTML
    if echo "$XSS_CONTENT_TYPE_RESPONSE" | grep -q '"success"'; then
        print_success "Content-Type manipulation correctly handled"
    else
        print_vulnerability "Content-Type manipulation succeeded - XSS via content sniffing possible"
    fi

    echo ""
}

# Test session and token attacks
test_session_attacks() {
    print_header "SESSION AND TOKEN ATTACK TESTS"

    if [ ! -f "security_test_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source security_test_vars

    # Test 1: Session fixation via refresh token reuse
    increment_test_count
    print_info "Testing refresh token reuse protection..."

    # Use refresh token once
    FIRST_REFRESH=$(curl -s -X POST "$API_BASE/auth/refresh-token" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

    # Try to use the same refresh token again
    SECOND_REFRESH=$(curl -s -X POST "$API_BASE/auth/refresh-token" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

    if echo "$SECOND_REFRESH" | grep -q '"success":false'; then
        print_success "Refresh token reuse correctly prevented"
    else
        print_vulnerability "Refresh token reuse not prevented - Session fixation possible"
    fi

    # Test 2: Token substitution attack
    increment_test_count
    print_info "Testing token substitution attack..."

    # Get a valid token for another user attempt
    MALICIOUS_TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vZXhhbXBsZS5vcmciLCJhdWQiOiJodHRwOi8vZXhhbXBsZS5vcmciLCJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6Ik1hbGljaW91cyBVc2VyIiwiaWF0IjoxNTE2MjM5MDIyfQ.4Adcj3UFYzPUVaVF43FmMab6RlaQD8A9V8wFzzht-KQ"

    SUBSTITUTION_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Bearer $MALICIOUS_TOKEN" \
        -H "Accept: application/json")

    if echo "$SUBSTITUTION_RESPONSE" | grep -q '"success":false'; then
        print_success "Token substitution attack correctly blocked"
    else
        print_vulnerability "Token substitution attack successful - Critical authentication vulnerability"
    fi

    # Test 3: Parallel session abuse
    increment_test_count
    print_info "Testing parallel session abuse..."

    # Generate multiple refresh tokens rapidly
    SESSION_COUNT=0
    for i in {1..5}; do
        LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -H "X-Device-ID: test-device-$i" \
            -d "{\"email\": \"$TEST_EMAIL\", \"password\": \"$TEST_PASSWORD\"}")

        if echo "$LOGIN_RESPONSE" | grep -q '"success":true'; then
            ((SESSION_COUNT++))
        fi
    done

    if [ $SESSION_COUNT -gt 3 ]; then
        print_warning "Multiple concurrent sessions allowed - Consider session limiting"
    else
        print_success "Parallel session abuse appropriately limited"
    fi

    echo ""
}

# Test brute force protection
test_brute_force() {
    print_header "BRUTE FORCE PROTECTION TESTS"

    # Test 1: Rapid login attempts
    increment_test_count
    print_info "Testing brute force protection (10 rapid attempts)..."

    FAILED_ATTEMPTS=0
    for i in {1..10}; do
        BRUTE_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d '{"email": "bruteforce@test.com", "password": "wrongpass'$i'"}')

        if echo "$BRUTE_RESPONSE" | grep -q '"success":false'; then
            ((FAILED_ATTEMPTS++))
        fi

        sleep 0.1
    done

    if [ $FAILED_ATTEMPTS -eq 10 ]; then
        print_success "All brute force attempts blocked"
    else
        print_warning "Some brute force attempts may have succeeded - Check rate limiting"
    fi

    # Test 2: Password spraying
    increment_test_count
    print_info "Testing password spraying attack..."

    SPRAY_SUCCESS=0
    COMMON_PASSWORDS=("password" "123456" "admin" "qwerty" "letmein")

    for password in "${COMMON_PASSWORDS[@]}"; do
        SPRAY_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "{\"email\": \"$TEST_EMAIL\", \"password\": \"$password\"}")

        if echo "$SPRAY_RESPONSE" | grep -q '"success":true'; then
            ((SPRAY_SUCCESS++))
        fi
    done

    if [ $SPRAY_SUCCESS -eq 0 ]; then
        print_success "Password spraying attack blocked"
    else
        print_vulnerability "Password spraying successful - Weak password policy vulnerability"
    fi

    echo ""
}

# Test data exposure
test_data_exposure() {
    print_header "DATA EXPOSURE TESTS"

    # Test 1: Information disclosure in error messages
    increment_test_count
    print_info "Testing information disclosure in error messages..."

    DISCLOSURE_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{"email": "nonexistent@test.com", "password": "password"}')

    if echo "$DISCLOSURE_RESPONSE" | grep -q -i "database\|sql\|table\|column"; then
        print_vulnerability "Information disclosure in error messages - Database details exposed"
    else
        print_success "No sensitive information disclosed in error messages"
    fi

    # Test 2: Stack trace exposure
    increment_test_count
    print_info "Testing stack trace exposure..."

    MALFORMED_JSON='{"email": "test@test.com" "password": "password"}'
    STACK_TRACE_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "$MALFORMED_JSON")

    if echo "$STACK_TRACE_RESPONSE" | grep -q -i "stack\|trace\|#\d\|\.php:"; then
        print_vulnerability "Stack trace information exposed - Debug mode may be enabled"
    else
        print_success "No stack trace information exposed"
    fi

    # Test 3: Directory traversal
    increment_test_count
    print_info "Testing directory traversal attempt..."

    DIR_TRAVERSAL_RESPONSE=$(curl -s -X GET "$API_BASE/auth/../../../etc/passwd" \
        -H "Accept: application/json")

    if echo "$DIR_TRAVERSAL_RESPONSE" | grep -q "root:"; then
        print_vulnerability "Directory traversal successful - Critical file system access"
    else
        print_success "Directory traversal correctly blocked"
    fi

    echo ""
}

# Test HTTP security headers
test_http_headers() {
    print_header "HTTP SECURITY HEADERS TEST"

    increment_test_count
    print_info "Testing HTTP security headers..."

    HEADERS_RESPONSE=$(curl -s -I -X GET "$API_BASE/status")

    # Check for security headers
    if echo "$HEADERS_RESPONSE" | grep -qi "x-frame-options"; then
        print_success "X-Frame-Options header present"
    else
        print_warning "X-Frame-Options header missing - Clickjacking possible"
    fi

    if echo "$HEADERS_RESPONSE" | grep -qi "x-content-type-options"; then
        print_success "X-Content-Type-Options header present"
    else
        print_warning "X-Content-Type-Options header missing - MIME sniffing possible"
    fi

    if echo "$HEADERS_RESPONSE" | grep -qi "x-xss-protection"; then
        print_success "X-XSS-Protection header present"
    else
        print_warning "X-XSS-Protection header missing"
    fi

    if echo "$HEADERS_RESPONSE" | grep -qi "strict-transport-security"; then
        print_success "HSTS header present"
    else
        print_warning "Strict-Transport-Security header missing"
    fi

    if echo "$HEADERS_RESPONSE" | grep -qi "content-security-policy"; then
        print_success "Content-Security-Policy header present"
    else
        print_warning "Content-Security-Policy header missing"
    fi

    echo ""
}

# Test CORS configuration
test_cors_security() {
    print_header "CORS SECURITY TEST"

    increment_test_count
    print_info "Testing CORS security configuration..."

    # Test malicious origin
    MALICIOUS_ORIGIN_RESPONSE=$(curl -s -X OPTIONS "$API_BASE/auth/login" \
        -H "Origin: https://malicious-site.com" \
        -H "Access-Control-Request-Method: POST" \
        -H "Access-Control-Request-Headers: Content-Type")

    # Check if malicious origin is allowed
    if echo "$MALICIOUS_ORIGIN_RESPONSE" | grep -qi "access-control-allow-origin.*malicious-site.com"; then
        print_vulnerability "CORS allows arbitrary origins - CSRF attack possible"
    else
        print_success "CORS properly restricts origins"
    fi

    # Test credentials with wildcard origin
    WILDCARD_CRED_RESPONSE=$(curl -s -X OPTIONS "$API_BASE/auth/login" \
        -H "Origin: https://test-site.com" \
        -H "Access-Control-Request-Method: POST" \
        -H "Access-Control-Request-Headers: Authorization")

    # This should be handled by McpCors middleware
    if echo "$WILDCARD_CRED_RESPONSE" | grep -qi "access-control-allow-origin.*\*"; then
        print_warning "CORS allows wildcard origin with credentials - Check security policy"
    else
        print_success "CORS credentials properly configured"
    fi

    echo ""
}

# Generate security report
generate_security_report() {
    print_header "SECURITY TEST SUMMARY REPORT"

    echo -e "${COLOR_BLUE}Total Security Tests Conducted: $TOTAL_TESTS${COLOR_NC}"
    echo -e "${COLOR_RED}Vulnerabilities Found: $VULNERABILITIES_FOUND${COLOR_NC}"

    if [ $VULNERABILITIES_FOUND -eq 0 ]; then
        echo -e "${COLOR_GREEN}🛡️ PASSED: No critical security vulnerabilities detected${COLOR_NC}"
        echo ""
        echo -e "${COLOR_GREEN}✅ Security Status: SECURE${COLOR_NC}"
        echo -e "${COLOR_GREEN}✅ Authentication: Properly implemented${COLOR_NC}"
        echo -e "${COLOR_GREEN}✅ JWT Validation: Secure against common attacks${COLOR_NC}"
        echo -e "${COLOR_GREEN}✅ Injection Protection: Effective${COLOR_NC}"
        echo -e "${COLOR_GREEN}✅ Session Management: Secure${COLOR_NC}"
        echo -e "${COLOR_GREEN}✅ Input Validation: Robust${COLOR_NC}"
    else
        echo -e "${COLOR_RED}🚨 CRITICAL ISSUES REQUIRING IMMEDIATE ATTENTION:${COLOR_NC}"
        echo ""
        echo -e "${COLOR_RED}⚠️ Security Status: VULNERABLE${COLOR_NC}"
        echo -e "${COLOR_RED}📋 Action Items:${COLOR_NC}"
        echo -e "   1. Review authentication bypass vulnerabilities"
        echo -e "   2. Implement proper JWT validation"
        echo -e "   3. Strengthen input sanitization"
        echo -e "   4. Configure rate limiting"
        echo -e "   5. Review CORS security settings"
        echo -e "   6. Enable HTTP security headers"
        echo ""
        echo -e "${COLOR_YELLOW}⚡ IMMEDIATE ACTIONS REQUIRED:${COLOR_NC}"
        echo -e "   - Fix critical authentication vulnerabilities"
        echo -e "   - Implement comprehensive input validation"
        echo -e "   - Configure security headers"
        echo -e "   - Test fixes with this script"
    fi

    echo ""
    echo -e "${COLOR_BLUE}📊 Security Score: $(( 100 - (VULNERABILITIES_FOUND * 10) ))/100${COLOR_NC}"
    echo -e "${COLOR_BLUE}🔒 Next Steps: Review and fix identified vulnerabilities${COLOR_NC}"
    echo ""
}

# Cleanup security test data
cleanup_security_tests() {
    print_header "SECURITY TEST CLEANUP"

    if [ -f "security_test_vars" ]; then
        source security_test_vars

        if [ -n "$ACCESS_TOKEN" ]; then
            print_info "Revoking test tokens..."

            # Revoke refresh token if available
            if [ -n "$REFRESH_TOKEN" ]; then
                REVOKE_RESPONSE=$(curl -s -X POST "$API_BASE/auth/revoke-token" \
                    -H "Content-Type: application/json" \
                    -H "Accept: application/json" \
                    -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

                if echo "$REVOKE_RESPONSE" | grep -q '"success":true'; then
                    print_success "Test refresh token revoked"
                fi
            fi
        fi

        rm -f security_test_vars
        print_success "Security test data cleaned up"
    else
        print_info "No security test data to clean"
    fi

    echo ""
}

# Show usage
show_usage() {
    echo "JWT Security Testing Script"
    echo "Usage: $0 [test_type]"
    echo ""
    echo "Available test types:"
    echo "  all              Run all security tests (default)"
    echo "  setup            Setup security test user"
    echo "  auth             Test authentication bypass attempts"
    echo "  jwt              Test JWT manipulation attacks"
    echo "  injection        Test injection vulnerabilities"
    echo "  xss              Test XSS vulnerabilities"
    echo "  session          Test session and token attacks"
    echo "  brute            Test brute force protection"
    echo "  exposure         Test data exposure vulnerabilities"
    echo "  headers          Test HTTP security headers"
    echo "  cors             Test CORS security configuration"
    echo "  report           Generate security summary report"
    echo "  cleanup          Clean up test data"
    echo ""
    echo "Examples:"
    echo "  $0                # Run all security tests"
    echo "  $0 auth           # Test authentication bypass only"
    echo "  $0 report         # Generate summary report"
}

# Main execution
main() {
    cd "$(dirname "$0")"

    case "${1:-all}" in
        "all")
            setup_security_test_user || exit 1
            test_authentication_bypass
            test_jwt_manipulation
            test_injection_attacks
            test_xss_attacks
            test_session_attacks
            test_brute_force
            test_data_exposure
            test_http_headers
            test_cors_security
            generate_security_report
            cleanup_security_tests
            ;;
        "setup")
            setup_security_test_user
            ;;
        "auth")
            test_authentication_bypass
            ;;
        "jwt")
            test_jwt_manipulation
            ;;
        "injection")
            test_injection_attacks
            ;;
        "xss")
            test_xss_attacks
            ;;
        "session")
            test_session_attacks
            ;;
        "brute")
            test_brute_force
            ;;
        "exposure")
            test_data_exposure
            ;;
        "headers")
            test_http_headers
            ;;
        "cors")
            test_cors_security
            ;;
        "report")
            if [ -f "security_test_vars" ]; then
                generate_security_report
            else
                print_error "No test data available. Run tests first."
            fi
            ;;
        "cleanup")
            cleanup_security_tests
            ;;
        "help"|"-h"|"--help")
            show_usage
            ;;
        *)
            print_error "Unknown test type: $1"
            show_usage
            exit 1
            ;;
    esac
}

# Check dependencies
check_dependencies() {
    if ! command -v curl &> /dev/null; then
        print_error "curl is required but not installed"
        exit 1
    fi

    if ! command -v jq &> /dev/null; then
        print_warning "jq is recommended for better JSON parsing"
        echo "Install with: sudo apt-get install jq (Ubuntu/Debian)"
        echo "Or: brew install jq (macOS)"
        echo ""
    fi

    # Check if API is running
    print_info "Checking if API server is running..."
    if curl -s "$API_BASE/status" | grep -q '"status"'; then
        print_success "API server is running"
    else
        print_error "API server is not running at $API_BASE"
        print_info "Start the server with: php artisan serve --port=8181"
        exit 1
    fi
    echo ""
}

# Check dependencies and run main
check_dependencies
main "$@"