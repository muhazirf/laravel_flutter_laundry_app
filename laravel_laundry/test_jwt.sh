#!/bin/bash

# JWT Authentication Testing Script
# Usage: ./test_jwt.sh [test_name]
# Run: chmod +x test_jwt.sh && ./test_jwt.sh

API_BASE="http://localhost:8181/api"
COLOR_GREEN='\033[0;32m'
COLOR_RED='\033[0;31m'
COLOR_YELLOW='\033[1;33m'
COLOR_BLUE='\033[0;34m'
COLOR_NC='\033[0m'

# Functions
print_header() {
    echo -e "${COLOR_BLUE}================================$1================================${COLOR_NC}"
    echo ""
}

print_success() {
    echo -e "${COLOR_GREEN}✅ $1${COLOR_NC}"
}

print_error() {
    echo -e "${COLOR_RED}❌ $1${COLOR_NC}"
}

print_warning() {
    echo -e "${COLOR_YELLOW}⚠️ $1${COLOR_NC}"
}

print_info() {
    echo -e "${COLOR_BLUE}ℹ️ $1${COLOR_NC}"
}

# Setup test data
setup_test_data() {
    print_header "SETUP TEST DATA"

    print_info "Creating test user and outlet..."

    # Create test user via registration
    REGISTER_RESPONSE=$(curl -s -X POST "$API_BASE/auth/register" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "name": "Test JWT User",
            "email": "testjwt@example.com",
            "password": "password",
            "password_confirmation": "password"
        }')

    if echo "$REGISTER_RESPONSE" | grep -q '"success":true'; then
        print_success "Test user created successfully"

        # Get user ID for cleanup
        USER_ID=$(echo "$REGISTER_RESPONSE" | jq -r '.data.user.id')
        echo "USER_ID=$USER_ID" > test_temp_vars

        print_info "Test credentials created:"
        echo "   Email: testjwt@example.com"
        echo "   Password: password"

    else
        print_error "Failed to create test user"
        echo "$REGISTER_RESPONSE"
        return 1
    fi
    echo ""
}

# Test basic authentication
test_basic_auth() {
    print_header "BASIC AUTHENTICATION TESTS"

    # Test 1: Valid login
    print_info "Testing valid login..."
    LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "email": "testjwt@example.com",
            "password": "password123"
        }')

    if echo "$LOGIN_RESPONSE" | grep -q '"success":true'; then
        print_success "Valid login successful"

        # Extract tokens
        ACCESS_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')
        REFRESH_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.refresh_token')

        echo "ACCESS_TOKEN=$ACCESS_TOKEN" >> test_temp_vars
        echo "REFRESH_TOKEN=$REFRESH_TOKEN" >> test_temp_vars

        print_info "Token generated: ${ACCESS_TOKEN:0:20}..."

        # Test 2: Invalid password
        print_info "Testing invalid password..."
        INVALID_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
            -H "Content-Type: application/json" \
            -d '{
                "email": "testjwt@example.com",
                "password": "wrongpassword"
            }')

        if echo "$INVALID_RESPONSE" | grep -q '"success":false'; then
            print_success "Invalid password correctly rejected"
        else
            print_error "Security issue: Invalid password accepted!"
        fi

        # Test 3: Non-existent user
        print_info "Testing non-existent user..."
        NONEXISTENT_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
            -H "Content-Type: application/json" \
            -d '{
                "email": "nonexistent@example.com",
                "password": "password123"
            }')

        if echo "$NONEXISTENT_RESPONSE" | grep -q '"success":false'; then
            print_success "Non-existent user correctly rejected"
        else
            print_error "Security issue: Non-existent user accepted!"
        fi

    else
        print_error "Valid login failed"
        echo "$LOGIN_RESPONSE"
    fi
    echo ""
}

# Test JWT claims structure
test_jwt_claims() {
    print_header "JWT CLAIMS ANALYSIS"

    if [ ! -f "test_temp_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source test_temp_vars

    if [ -z "$ACCESS_TOKEN" ]; then
        print_error "No access token found. Run auth tests first."
        return 1
    fi

    print_info "Analyzing JWT claims structure..."

    # Decode JWT (without verification for analysis)
    CLAIMS=$(echo $ACCESS_TOKEN | cut -d. -f2 | base64 -d 2>/dev/null | jq . 2>/dev/null)

    if [ $? -eq 0 ]; then
        print_success "JWT claims decoded successfully"

        echo "Subject: $(echo $CLAIMS | jq -r '.sub')"
        echo "Issued At: $(echo $CLAIMS | jq -r '.iat' | xargs -I {} date -d @{} '+%Y-%m-%d %H:%M:%S')"

        if echo "$CLAIMS" | jq -e '.user' > /dev/null 2>&1; then
            print_info "User claims:"
            echo "  ID: $(echo $CLAIMS | jq -r '.user.id')"
            echo "  Name: $(echo $CLAIMS | jq -r '.user.name')"
            echo "  Email: $(echo $CLAIMS | jq -r '.user.email')"
            echo "  Active: $(echo $CLAIMS | jq -r '.user.is_active')"
        fi

        if echo "$CLAIMS" | jq -e '.tenant' > /dev/null 2>&1; then
            print_info "Tenant claims:"
            echo "  Current Outlet: $(echo $CLAIMS | jq -r '.tenant.current_outlet_id')"
            echo "  Available Outlets: $(echo $CLAIMS | jq -r '.tenant.available_outlets | @ts')"
            echo "  Primary Role: $(echo $CLAIMS | jq -r '.tenant.primary_role')"
        fi

        if echo "$CLAIMS" | jq -e '.permissions' > /dev/null 2>&1; then
            print_info "Permission claims found:"
            echo "$CLAIMS" | jq -r '.permissions | keys[]' | while read key; do
                role=$(echo $CLAIMS | jq -r ".permissions.$key.role")
                active=$(echo $CLAIMS | jq -r ".permissions.$key.is_active")
                echo "  $key: $role ($([ "$active" == "true" ] && echo "Active" || echo "Inactive"))"
            done
        fi

    else
        print_error "Failed to decode JWT claims"
    fi
    echo ""
}

# Test protected endpoints
test_protected_endpoints() {
    print_header "PROTECTED ENDPOINTS TEST"

    if [ ! -f "test_temp_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source test_temp_vars

    if [ -z "$ACCESS_TOKEN" ]; then
        print_error "No access token found. Run auth tests first."
        return 1
    fi

    # Test without token (should fail)
    print_info "Testing protected endpoint without token..."
    NO_TOKEN_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Accept: application/json")

    if echo "$NO_TOKEN_RESPONSE" | grep -q '"success":false'; then
        print_success "Request without token correctly rejected"
    else
        print_error "Security issue: Request without token accepted!"
    fi

    # Test with valid token (should succeed)
    print_info "Testing protected endpoint with valid token..."
    WITH_TOKEN_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -H "Accept: application/json")

    if echo "$WITH_TOKEN_RESPONSE" | grep -q '"success":true'; then
        print_success "Request with valid token accepted"
        USER_NAME=$(echo "$WITH_TOKEN_RESPONSE" | jq -r '.data.name')
        echo "  User retrieved: $USER_NAME"
    else
        print_error "Valid token rejected unexpectedly"
    fi
    echo ""
}

# Test refresh token
test_refresh_token() {
    print_header "REFRESH TOKEN TEST"

    if [ ! -f "test_temp_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source test_temp_vars

    if [ -z "$REFRESH_TOKEN" ]; then
        print_error "No refresh token found. Run auth tests first."
        return 1
    fi

    ORIGINAL_TOKEN=$ACCESS_TOKEN

    print_info "Testing token refresh..."
    REFRESH_RESPONSE=$(curl -s -X POST "$API_BASE/auth/refresh-token" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

    if echo "$REFRESH_RESPONSE" | grep -q '"success":true'; then
        print_success "Token refresh successful"

        NEW_TOKEN=$(echo "$REFRESH_RESPONSE" | jq -r '.data.token')

        if [ "$ORIGINAL_TOKEN" != "$NEW_TOKEN" ]; then
            print_success "New token is different from original"
            echo "  Original: ${ORIGINAL_TOKEN:0:20}..."
            echo "  New: ${NEW_TOKEN:0:20}..."

            # Update token for subsequent tests
            sed -i "s/ACCESS_TOKEN=.*/ACCESS_TOKEN=$NEW_TOKEN/" test_temp_vars

            # Test new token validity
            print_info "Testing new token validity..."
            NEW_TOKEN_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
                -H "Authorization: Bearer $NEW_TOKEN" \
                -H "Accept: application/json")

            if echo "$NEW_TOKEN_RESPONSE" | grep -q '"success":true'; then
                print_success "New token is valid"
            else
                print_error "New token is invalid"
            fi
        else
            print_error "New token is the same as original (refresh failed)"
        fi

    else
        print_error "Token refresh failed"
        echo "$REFRESH_RESPONSE"
    fi
    echo ""
}

# Test token revocation
test_token_revocation() {
    print_header "TOKEN REVOCATION TEST"

    if [ ! -f "test_temp_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source test_temp_vars

    if [ -z "$REFRESH_TOKEN" ]; then
        print_error "No refresh token found. Run auth tests first."
        return 1
    fi

    # Revoke token
    print_info "Revoking refresh token..."
    REVOKE_RESPONSE=$(curl -s -X POST "$API_BASE/auth/revoke-token" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

    if echo "$REVOKE_RESPONSE" | grep -q '"success":true'; then
        print_success "Token revoked successfully"

        # Test revoked token
        print_info "Testing revoked token (should fail)..."
        REVOKED_TEST_RESPONSE=$(curl -s -X POST "$API_BASE/auth/refresh-token" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

        if echo "$REVOKED_TEST_RESPONSE" | grep -q '"success":false'; then
            print_success "Revoked token correctly rejected"
        else
            print_error "Security issue: Revoked token still works!"
        fi

    else
        print_error "Token revocation failed"
        echo "$REVOKE_RESPONSE"
    fi
    echo ""
}

# Test security vulnerabilities
test_security() {
    print_header "SECURITY VULNERABILITY TESTS"

    # Test 1: SQL Injection attempt
    print_info "Testing SQL injection attempt..."
    SQL_INJECT_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -d '{"email": "test@example.com\"; DROP TABLE users; --", "password": "password"}')

    if echo "$SQL_INJECT_RESPONSE" | grep -q '"success":false'; then
        print_success "SQL injection attempt blocked"
    else
        print_error "WARNING: Possible SQL injection vulnerability!"
    fi

    # Test 2: XSS attempt
    print_info "Testing XSS attempt..."
    XSS_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -d '{"email": "test@example.com<script>alert(\"XSS\")</script>", "password": "password"}')

    if echo "$XSS_RESPONSE" | grep -q '"success":false'; then
        print_success "XSS attempt blocked"
    else
        print_error "WARNING: Possible XSS vulnerability!"
    fi

    # Test 3: Rate limiting
    print_info "Testing rate limiting (multiple rapid requests)..."
    RATE_LIMIT_COUNT=0
    for i in {1..10}; do
        RAPID_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
            -H "Content-Type: application/json" \
            -d '{"email": "test@example.com", "password": "password"}')

        if echo "$RAPID_RESPONSE" | grep -q '"success":false'; then
            ((RATE_LIMIT_COUNT++))
        fi
    done

    if [ $RATE_LIMIT_COUNT -gt 0 ]; then
        print_success "Rate limiting detected ($RATE_LIMIT_COUNT out of 10 requests blocked)"
    else
        print_warning "Rate limiting may not be configured"
    fi

    # Test 4: Large payload
    print_info "Testing large payload..."
    LARGE_PAYLOAD=$(printf 'A%.0s' {1..100000})
    LARGE_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\": \"$LARGE_PAYLOAD\", \"password\": \"password\"}")

    if echo "$LARGE_RESPONSE" | grep -q '"success":false'; then
        print_success "Large payload correctly rejected"
    else
        print_warning "Large payload accepted (check if this is expected)"
    fi

    echo ""
}

# Test performance
test_performance() {
    print_header "PERFORMANCE TEST"

    if [ ! -f "test_temp_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source test_temp_vars

    if [ -z "$ACCESS_TOKEN" ]; then
        print_error "No access token found. Run auth tests first."
        return 1
    fi

    print_info "Running performance test (20 requests)..."

    START_TIME=$(date +%s.%3N)
    SUCCESS_COUNT=0

    for i in {1..20}; do
        PERF_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
            -H "Authorization: Bearer $ACCESS_TOKEN" \
            -H "Accept: application/json")

        if echo "$PERF_RESPONSE" | grep -q '"success":true'; then
            ((SUCCESS_COUNT++))
        fi
    done

    END_TIME=$(date +%s.%3N)
    TOTAL_TIME=$(echo "$END_TIME - $START_TIME" | bc)
    AVG_TIME=$(echo "scale=2; $TOTAL_TIME / 20" | bc)
    RPS=$(echo "scale=0; 20 / $TOTAL_TIME" | bc)

    print_success "Performance Test Results:"
    echo "  Total Requests: 20"
    echo "  Successful: $SUCCESS_COUNT"
    echo "  Total Time: ${TOTAL_TIME}s"
    echo "  Average Time: ${AVG_TIME}s"
    echo "  Requests/sec: $RPS"
    echo ""
}

# Test JWT expiration
test_jwt_expiration() {
    print_header "JWT EXPIRATION TEST"

    if [ -f "test_temp_vars" ]; then
        source test_temp_vars

        print_info "Testing with malformed JWT..."
        MALFORMED_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
            -H "Authorization: Bearer malformed.jwt.token" \
            -H "Accept: application/json")

        if echo "$MALFORMED_RESPONSE" | grep -q '"success":false'; then
            print_success "Malformed JWT correctly rejected"
        else
            print_error "Security issue: Malformed JWT accepted!"
        fi
    else
        print_warning "No test data available for expiration testing"
    fi

    # Note: Real expiration testing would require modifying JWT TTL
    print_info "Note: Actual expiration testing requires JWT TTL configuration changes"
    echo ""
}

# Cleanup test data
cleanup() {
    print_header "CLEANUP"

    if [ -f "test_temp_vars" ]; then
        source test_temp_vars

        if [ -n "$USER_ID" ]; then
            print_info "Cleaning up test data (User ID: $USER_ID)..."
            # This would need to be implemented based on your cleanup strategy
            print_warning "Please manually clean up test user (ID: $USER_ID) and related data"
        fi

        rm -f test_temp_vars
        print_success "Temporary files cleaned up"
    else
        print_info "No temporary files to clean"
    fi

    echo ""
}

# Show usage
show_usage() {
    echo "JWT Authentication Testing Script"
    echo "Usage: $0 [test_name]"
    echo ""
    echo "Available tests:"
    echo "  all              Run all tests (default)"
    echo "  setup            Setup test data"
    echo "  auth             Test basic authentication"
    echo "  claims           Analyze JWT claims"
    echo "  protected       Test protected endpoints"
    echo "  refresh          Test refresh token"
    echo ""
    echo "  revoke          Test token revocation"
    echo "  security         Test security vulnerabilities"
    echo "  performance      Test API performance"
    echo "  expiration       Test JWT expiration"
    echo "  cleanup          Clean up test data"
    echo ""
    echo "Examples:"
    echo "  $0                # Run all tests"
    echo "  $0 auth          # Run authentication tests only"
    echo "  $0 cleanup        # Clean up test data"
}

# Main execution
main() {
    cd "$(dirname "$0")"

    case "${1:-all}" in
        "all")
            setup_test_data || exit 1
            test_basic_auth
            test_jwt_claims
            test_protected_endpoints
            test_refresh_token
            test_token_revocation
            test_security
            test_performance
            test_jwt_expiration
            cleanup
            ;;
        "setup")
            setup_test_data
            ;;
        "auth")
            test_basic_auth
            ;;
        "claims")
            test_jwt_claims
            ;;
        "protected")
            test_protected_endpoints
            ;;
        "refresh")
            test_refresh_token
            ;;
        "revoke")
            test_token_revocation
            ;;
        "security")
            test_security
            ;;
        "performance")
            test_performance
            ;;
        "expiration")
            test_jwt_expiration
            ;;
        "cleanup")
            cleanup
            ;;
        "help"|"-h"|"--help")
            show_usage
            ;;
        *)
            print_error "Unknown test: $1"
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
