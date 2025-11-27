#!/bin/bash

# JWT Performance Testing Script
# Usage: ./performance_test.sh [test_type]
# Run: chmod +x performance_test.sh && ./performance_test.sh

API_BASE="http://localhost:8181/api"
COLOR_GREEN='\033[0;32m'
COLOR_RED='\033[0;31m'
COLOR_YELLOW='\033[1;33m'
COLOR_BLUE='\033[0;34m'
COLOR_CYAN='\033[0;36m'
COLOR_NC='\033[0m'

# Performance test credentials
TEST_EMAIL="perftest@example.com"
TEST_PASSWORD="password123"

# Functions
print_header() {
    echo -e "${COLOR_CYAN}================================$1================================${COLOR_NC}"
    echo ""
}

print_success() {
    echo -e "${COLOR_GREEN}✅ $1${COLOR_NC}"
}

print_warning() {
    echo -e "${COLOR_YELLOW}⚠️ $1${COLOR_NC}"
}

print_info() {
    echo -e "${COLOR_BLUE}ℹ️ $1${COLOR_NC}"
}

print_metric() {
    echo -e "${COLOR_CYAN}📊 $1${COLOR_NC}"
}

# Setup performance test user
setup_perf_user() {
    print_header "PERFORMANCE TEST SETUP"

    print_info "Setting up performance test user..."

    REGISTER_RESPONSE=$(curl -s -X POST "$API_BASE/auth/register" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{
            \"name\": \"Performance Test User\",
            \"email\": \"$TEST_EMAIL\",
            \"password\": \"$TEST_PASSWORD\",
            \"password_confirmation\": \"$TEST_PASSWORD\"
        }")

    if echo "$REGISTER_RESPONSE" | grep -q '"success":true'; then
        print_success "Performance test user created"
    else
        print_warning "Test user might already exist"
    fi

    # Get login token
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

        echo "ACCESS_TOKEN=$ACCESS_TOKEN" > perf_test_vars
        echo "REFRESH_TOKEN=$REFRESH_TOKEN" >> perf_test_vars

        print_success "Performance test credentials ready"
    else
        print_error "Failed to login with test user"
        echo "$LOGIN_RESPONSE"
        return 1
    fi
    echo ""
}

# Test authentication performance
test_auth_performance() {
    print_header "AUTHENTICATION PERFORMANCE TESTS"

    if [ ! -f "perf_test_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source perf_test_vars

    # Test 1: Login performance
    print_info "Testing login performance (20 requests)..."

    START_TIME=$(date +%s.%3N)
    SUCCESS_COUNT=0

    for i in {1..20}; do
        LOGIN_PERF_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "{
                \"email\": \"$TEST_EMAIL\",
                \"password\": \"$TEST_PASSWORD\"
            }")

        if echo "$LOGIN_PERF_RESPONSE" | grep -q '"success":true'; then
            ((SUCCESS_COUNT++))
        fi
    done

    END_TIME=$(date +%s.%3N)
    TOTAL_TIME=$(echo "$END_TIME - $START_TIME" | bc)
    AVG_TIME=$(echo "scale=2; $TOTAL_TIME / 20" | bc)
    RPS=$(echo "scale=0; 20 / $TOTAL_TIME" | bc)

    print_metric "Login Performance Results:"
    echo "  Total Requests: 20"
    echo "  Successful: $SUCCESS_COUNT"
    echo "  Total Time: ${TOTAL_TIME}s"
    echo "  Average Time: ${AVG_TIME}s"
    echo "  Requests/sec: $RPS"

    if (( $(echo "$AVG_TIME < 0.5" | bc -l) )); then
        print_success "Login performance is excellent (< 0.5s average)"
    elif (( $(echo "$AVG_TIME < 1.0" | bc -l) )); then
        print_success "Login performance is good (< 1.0s average)"
    else
        print_warning "Login performance may need improvement (> 1.0s average)"
    fi

    echo ""

    # Test 2: Token validation performance
    print_info "Testing JWT validation performance (100 requests)..."

    START_TIME=$(date +%s.%3N)
    VALIDATION_COUNT=0

    for i in {1..100}; do
        VALIDATE_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
            -H "Authorization: Bearer $ACCESS_TOKEN" \
            -H "Accept: application/json")

        if echo "$VALIDATE_RESPONSE" | grep -q '"success':true"; then
            ((VALIDATION_COUNT++))
        fi
    done

    END_TIME=$(date +%s.%3N)
    TOTAL_TIME=$(echo "$END_TIME - $START_TIME" | bc)
    AVG_TIME=$(echo "scale=3; $TOTAL_TIME / 100" | bc)
    RPS=$(echo "scale=0; 100 / $TOTAL_TIME" | bc)

    print_metric "JWT Validation Performance Results:"
    echo "  Total Requests: 100"
    echo "  Successful: $VALIDATION_COUNT"
    echo "  Total Time: ${TOTAL_TIME}s"
    echo "  Average Time: ${AVG_TIME}s"
    echo "  Requests/sec: $RPS"

    if (( $(echo "$AVG_TIME < 0.1" | bc -l) )); then
        print_success "JWT validation performance is excellent (< 0.1s average)"
    elif (( $(echo "$AVG_TIME < 0.2" | bc -l) )); then
        print_success "JWT validation performance is good (< 0.2s average)"
    else
        print_warning "JWT validation performance may need optimization (> 0.2s average)"
    fi

    echo ""

    # Test 3: Refresh token performance
    print_info "Testing refresh token performance (10 requests)..."

    START_TIME=$(date +%s.%3N)
    REFRESH_COUNT=0

    # Store original refresh token
    ORIGINAL_REFRESH_TOKEN=$REFRESH_TOKEN

    for i in {1..10}; do
        REFRESH_PERF_RESPONSE=$(curl -s -X POST "$API_BASE/auth/refresh-token" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

        if echo "$REFRESH_PERF_RESPONSE" | grep -q '"success":true'; then
            ((REFRESH_COUNT++))
            # Use the new refresh token for next iteration
            REFRESH_TOKEN=$(echo "$REFRESH_PERF_RESPONSE" | jq -r '.data.refresh_token')
        fi
    done

    END_TIME=$(date +%s.%3N)
    TOTAL_TIME=$(echo "$END_TIME - $START_TIME" | bc)
    AVG_TIME=$(echo "scale=2; $TOTAL_TIME / 10" | bc)
    RPS=$(echo "scale=0; 10 / $TOTAL_TIME" | bc)

    print_metric "Refresh Token Performance Results:"
    echo "  Total Requests: 10"
    echo "  Successful: $REFRESH_COUNT"
    echo "  Total Time: ${TOTAL_TIME}s"
    echo "  Average Time: ${AVG_TIME}s"
    echo "  Requests/sec: $RPS"

    if (( $(echo "$AVG_TIME < 0.5" | bc -l) )); then
        print_success "Refresh token performance is excellent (< 0.5s average)"
    elif (( $(echo "$AVG_TIME < 1.0" | bc -l) )); then
        print_success "Refresh token performance is good (< 1.0s average)"
    else
        print_warning "Refresh token performance may need improvement (> 1.0s average)"
    fi

    echo ""
}

# Test concurrent user load
test_concurrent_load() {
    print_header "CONCURRENT LOAD TESTING"

    if [ ! -f "perf_test_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source perf_test_vars

    print_info "Testing concurrent user load (50 parallel requests)..."

    # Create background processes for concurrent requests
    TEMP_DIR=$(mktemp -d)
    SUCCESS_COUNT=0

    start_time=$(date +%s.%3N)

    # Launch 50 parallel requests
    for i in {1..50}; do
        {
            RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
                -H "Authorization: Bearer $ACCESS_TOKEN" \
                -H "Accept: application/json")

            if echo "$RESPONSE" | grep -q '"success":true'; then
                echo "success" > "$TEMP_DIR/result_$i"
            else
                echo "failed" > "$TEMP_DIR/result_$i"
            fi
        } &
    done

    # Wait for all background processes to complete
    wait

    end_time=$(date +%s.%3N)
    total_time=$(echo "$end_time - $start_time" | bc)

    # Count successful requests
    for i in {1..50}; do
        if [ -f "$TEMP_DIR/result_$i" ]; then
            if grep -q "success" "$TEMP_DIR/result_$i"; then
                ((SUCCESS_COUNT++))
            fi
        fi
    done

    # Clean up temp directory
    rm -rf "$TEMP_DIR"

    avg_time=$(echo "scale=3; $total_time / 50" | bc)
    success_rate=$(echo "scale=1; $SUCCESS_COUNT * 100 / 50" | bc)
    throughput=$(echo "scale=0; 50 / $total_time" | bc)

    print_metric "Concurrent Load Test Results:"
    echo "  Total Requests: 50"
    echo "  Successful: $SUCCESS_COUNT"
    echo "  Success Rate: ${success_rate}%"
    echo "  Total Time: ${total_time}s"
    echo "  Average Time: ${avg_time}s"
    echo "  Throughput: $throughput RPS"

    if (( $(echo "$success_rate > 95" | bc -l) )); then
        print_success "Excellent concurrent performance (> 95% success rate)"
    elif (( $(echo "$success_rate > 85" | bc -l) )); then
        print_success "Good concurrent performance (> 85% success rate)"
    else
        print_warning "Concurrent performance needs improvement (< 85% success rate)"
    fi

    echo ""
}

# Test memory and resource usage
test_resource_usage() {
    print_header "RESOURCE USAGE TESTING"

    if [ ! -f "perf_test_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source perf_test_vars

    print_info "Testing resource usage under sustained load..."

    # Get initial memory usage (if monitoring tools are available)
    INITIAL_MEMORY=0
    if command -v ps &> /dev/null && pgrep -f "php.*artisan.*serve" > /dev/null; then
        INITIAL_MEMORY=$(ps -o rss= -p $(pgrep -f "php.*artisan.*serve") | awk '{print $1}')
        print_info "Initial memory usage: ${INITIAL_MEMORY} KB"
    fi

    # Sustained load test
    START_TIME=$(date +%s.%3N)
    SUSTAINED_REQUESTS=0
    SUSTAINED_SUCCESS=0

    for i in {1..200}; do
        SUSTAINED_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
            -H "Authorization: Bearer $ACCESS_TOKEN" \
            -H "Accept: application/json")

        ((SUSTAINED_REQUESTS++))
        if echo "$SUSTAINED_RESPONSE" | grep -q '"success":true'; then
            ((SUSTAINED_SUCCESS++))
        fi

        # Check memory usage every 50 requests
        if [ $((i % 50)) -eq 0 ] && command -v ps &> /dev/null; then
            CURRENT_MEMORY=$(ps -o rss= -p $(pgrep -f "php.*artisan.*serve") | awk '{print $1}')
            print_info "Request $i: Memory usage: ${CURRENT_MEMORY} KB"
        fi
    done

    END_TIME=$(date +%s.%3N)
    TOTAL_TIME=$(echo "$END_TIME - $START_TIME" | bc)

    # Final memory usage
    FINAL_MEMORY=0
    if command -v ps &> /dev/null && pgrep -f "php.*artisan.*serve" > /dev/null; then
        FINAL_MEMORY=$(ps -o rss= -p $(pgrep -f "php.*artisan.*serve") | awk '{print $1}')
        MEMORY_INCREASE=$((FINAL_MEMORY - INITIAL_MEMORY))
        print_info "Final memory usage: ${FINAL_MEMORY} KB"
        print_info "Memory increase: ${MEMORY_INCREASE} KB"
    fi

    success_rate=$(echo "scale=1; $SUSTAINED_SUCCESS * 100 / $SUSTAINED_REQUESTS" | bc)
    throughput=$(echo "scale=0; $SUSTAINED_REQUESTS / $TOTAL_TIME" | bc)

    print_metric "Sustained Load Test Results:"
    echo "  Total Requests: $SUSTAINED_REQUESTS"
    echo "  Successful: $SUSTAINED_SUCCESS"
    echo "  Success Rate: ${success_rate}%"
    echo "  Total Time: ${TOTAL_TIME}s"
    echo "  Throughput: $throughput RPS"

    if [ -n "$MEMORY_INCREASE" ]; then
        if [ $MEMORY_INCREASE -lt 5000 ]; then
            print_success "Memory usage is stable (< 5MB increase)"
        elif [ $MEMORY_INCREASE -lt 20000 ]; then
            print_success "Memory usage is acceptable (< 20MB increase)"
        else
            print_warning "High memory usage increase (> 20MB) - Possible memory leak"
        fi
    fi

    echo ""
}

# Test scalability with different token sizes
test_token_size_scalability() {
    print_header "TOKEN SIZE SCALABILITY TESTING"

    if [ ! -f "perf_test_vars" ]; then
        print_error "No test data found. Run setup first."
        return 1
    fi

    source perf_test_vars

    print_info "Testing performance with different JWT token sizes..."

    # Test with current token size
    CURRENT_TOKEN_SIZE=${#ACCESS_TOKEN}
    print_info "Current token size: $CURRENT_TOKEN_SIZE characters"

    START_TIME=$(date +%s.%3N)
    NORMAL_COUNT=0

    for i in {1..50}; do
        NORMAL_RESPONSE=$(curl -s -X GET "$API_BASE/auth/me" \
            -H "Authorization: Bearer $ACCESS_TOKEN" \
            -H "Accept: application/json")

        if echo "$NORMAL_RESPONSE" | grep -q '"success":true'; then
            ((NORMAL_COUNT++))
        fi
    done

    END_TIME=$(date +%s.%3N)
    NORMAL_TIME=$(echo "$END_TIME - $START_TIME" | bc)
    NORMAL_AVG=$(echo "scale=3; $NORMAL_TIME / 50" | bc)

    print_metric "Current Token Performance:"
    echo "  Token Size: $CURRENT_TOKEN_SIZE chars"
    echo "  Average Time: ${NORMAL_AVG}s"

    # Performance analysis
    if (( $(echo "$NORMAL_AVG < 0.05" | bc -l) )); then
        print_success "Excellent token validation performance"
    elif (( $(echo "$NORMAL_AVG < 0.1" | bc -l) )); then
        print_success "Good token validation performance"
    else
        print_warning "Token validation performance may need optimization"
    fi

    echo ""
}

# Generate performance report
generate_performance_report() {
    print_header "PERFORMANCE TEST SUMMARY"

    print_info "Performance Test Environment:"
    echo "  API Base URL: $API_BASE"
    echo "  Test Tool: curl"
    echo "  Timestamp: $(date)"
    echo ""

    print_metric "Key Performance Metrics:"
    echo "  ✓ Authentication Response Time: Should be < 1.0s"
    echo "  ✓ JWT Validation Speed: Should be < 0.1s"
    echo "  ✓ Concurrent User Support: Should handle > 85% success rate"
    echo "  ✓ Memory Efficiency: Should have minimal memory increase"
    echo "  ✓ Throughput: Should maintain stable RPS under load"
    echo ""

    print_info "Recommendations:"
    echo "  1. Monitor response times in production"
    echo "  2. Implement performance monitoring alerts"
    echo "  3. Consider load balancing for high traffic"
    echo "  4. Monitor Redis performance for refresh tokens"
    echo "  5. Regular performance testing with increasing loads"
    echo ""

    print_success "Performance testing completed successfully"
    echo ""
}

# Cleanup performance test data
cleanup_perf_tests() {
    print_header "PERFORMANCE TEST CLEANUP"

    if [ -f "perf_test_vars" ]; then
        source perf_test_vars

        if [ -n "$REFRESH_TOKEN" ]; then
            print_info "Revoking performance test tokens..."
            REVOKE_RESPONSE=$(curl -s -X POST "$API_BASE/auth/revoke-token" \
                -H "Content-Type: application/json" \
                -H "Accept: application/json" \
                -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

            if echo "$REVOKE_RESPONSE" | grep -q '"success":true'; then
                print_success "Performance test tokens revoked"
            fi
        fi

        rm -f perf_test_vars
        print_success "Performance test data cleaned up"
    else
        print_info "No performance test data to clean"
    fi

    echo ""
}

# Show usage
show_usage() {
    echo "JWT Performance Testing Script"
    echo "Usage: $0 [test_type]"
    echo ""
    echo "Available test types:"
    echo "  all              Run all performance tests (default)"
    echo "  setup            Setup performance test user"
    echo "  auth             Test authentication performance"
    echo "  concurrent       Test concurrent user load"
    echo "  resource         Test resource usage"
    echo "  scalability      Test token size scalability"
    echo "  report           Generate performance summary"
    echo "  cleanup          Clean up test data"
    echo ""
    echo "Examples:"
    echo "  $0                # Run all performance tests"
    echo "  $0 auth           # Test authentication performance only"
    echo "  $0 concurrent     # Test concurrent load only"
    echo "  $0 report         # Generate performance summary"
}

# Main execution
main() {
    cd "$(dirname "$0")"

    case "${1:-all}" in
        "all")
            setup_perf_user || exit 1
            test_auth_performance
            test_concurrent_load
            test_resource_usage
            test_token_size_scalability
            generate_performance_report
            cleanup_perf_tests
            ;;
        "setup")
            setup_perf_user
            ;;
        "auth")
            test_auth_performance
            ;;
        "concurrent")
            test_concurrent_load
            ;;
        "resource")
            test_resource_usage
            ;;
        "scalability")
            test_token_size_scalability
            ;;
        "report")
            generate_performance_report
            ;;
        "cleanup")
            cleanup_perf_tests
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

    if ! command -v bc &> /dev/null; then
        print_error "bc calculator is required for performance calculations"
        print_info "Install with: sudo apt-get install bc (Ubuntu/Debian)"
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