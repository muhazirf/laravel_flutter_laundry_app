# JWT Multi-Tenant Testing Guide

This guide provides comprehensive testing scripts for validating the JWT multi-tenant authentication system implemented in the Laravel laundry backend.

## Available Testing Scripts

### 1. `test_jwt.sh` - Main JWT Testing Script
**Purpose**: Comprehensive testing of JWT functionality, authentication flow, and basic security validation.

**Usage**:
```bash
./test_jwt.sh [test_name]
```

**Available Tests**:
- `all` - Run all tests (default)
- `setup` - Create test user and get credentials
- `auth` - Test basic authentication (login, password validation)
- `claims` - Analyze JWT claims structure and tenant context
- `protected` - Test protected endpoint access
- `refresh` - Test refresh token functionality
- `revoke` - Test token revocation
- `security` - Basic security vulnerability tests
- `performance` - Basic performance testing
- `expiration` - Test JWT expiration handling
- `cleanup` - Clean up test data

**Example**:
```bash
# Run all tests
./test_jwt.sh

# Run only authentication tests
./test_jwt.sh auth

# Clean up test data
./test_jwt.sh cleanup
```

### 2. `security_test.sh` - Comprehensive Security Testing
**Purpose**: In-depth security vulnerability testing using curl and various attack scenarios.

**Usage**:
```bash
./security_test.sh [test_type]
```

**Available Security Tests**:
- `all` - Run all security tests (default)
- `setup` - Setup security test user
- `auth` - Authentication bypass attempts
- `jwt` - JWT manipulation attacks
- `injection` - SQL injection, XSS, and other injection attacks
- `xss` - Cross-site scripting vulnerability tests
- `session` - Session and token attacks
- `brute` - Brute force protection testing
- `exposure` - Data exposure vulnerabilities
- `headers` - HTTP security headers validation
- `cors` - CORS security configuration
- `report` - Generate security summary report
- `cleanup` - Clean up test data

**Security Coverage**:
- Authentication bypass attempts
- JWT token manipulation (tampering, expiration, algorithm confusion)
- SQL injection, NoSQL injection, LDAP injection
- Cross-site scripting (XSS) attacks
- Session fixation and token substitution
- Brute force and password spraying
- Information disclosure
- Directory traversal
- HTTP security headers
- CORS misconfiguration

**Example**:
```bash
# Run comprehensive security audit
./security_test.sh

# Test only JWT manipulation attacks
./security_test.sh jwt

# Generate security report
./security_test.sh report
```

### 3. `performance_test.sh` - Performance and Load Testing
**Purpose**: Test system performance under various load conditions and validate scaling capabilities.

**Usage**:
```bash
./performance_test.sh [test_type]
```

**Available Performance Tests**:
- `all` - Run all performance tests (default)
- `setup` - Setup performance test user
- `auth` - Authentication and JWT validation performance
- `concurrent` - Concurrent user load testing
- `resource` - Memory and resource usage testing
- `scalability` - Token size scalability testing
- `report` - Generate performance summary
- `cleanup` - Clean up test data

**Performance Metrics**:
- Authentication response times
- JWT validation speed
- Concurrent user handling (50+ parallel requests)
- Memory usage under sustained load
- Throughput (requests per second)
- Success rates under load

**Example**:
```bash
# Run complete performance suite
./performance_test.sh

# Test concurrent load handling
./performance_test.sh concurrent

# Generate performance report
./performance_test.sh report
```

## Prerequisites

### Required Dependencies
```bash
# Ubuntu/Debian
sudo apt-get install curl jq bc

# macOS
brew install curl jq bc

# Verify installation
curl --version
jq --version
bc --version
```

### API Server Setup
Ensure your Laravel API server is running:
```bash
cd /path/to/laravel-laundry-be/laravel_laundry
php artisan serve --port=8181
```

### Redis Setup (Required for Refresh Tokens)
```bash
# Start Redis server
sudo systemctl start redis
# or
redis-server

# Verify Redis is running
redis-cli ping
```

## Test Data Management

All scripts automatically manage test data:
- Create test users with predictable email addresses
- Store tokens in temporary files (`*_test_vars`)
- Clean up test data automatically
- Manual cleanup available with `cleanup` parameter

### Test User Credentials
- **Main Test**: `testjwt@example.com` / `password123`
- **Security Test**: `securitytest@example.com` / `password123`
- **Performance Test**: `perftest@example.com` / `password123`

## Security Test Scenarios

### Authentication Bypass Tests
1. Empty Authorization header
2. Bearer token without value
3. Invalid Authorization scheme
4. Manipulated JWT format
5. None algorithm attack

### JWT Manipulation Tests
1. Token payload tampering
2. Expired token acceptance
3. Future token (nbf violation)
4. Algorithm confusion attack

### Injection Attack Tests
1. SQL injection in login fields
2. LDAP injection attempts
3. NoSQL injection vectors
4. Command injection via headers

### XSS Protection Tests
1. XSS in registration data
2. Reflected XSS in login
3. Content-Type manipulation

## Performance Benchmarks

### Expected Performance Metrics
- **Login Response Time**: < 1.0s (ideal: < 0.5s)
- **JWT Validation**: < 0.1s (ideal: < 0.05s)
- **Concurrent Success Rate**: > 85% (ideal: > 95%)
- **Memory Increase**: < 20MB under sustained load

### Load Testing Scenarios
- **Light Load**: 20 authentication requests
- **Medium Load**: 50 parallel requests
- **Heavy Load**: 200 sustained requests

## Security Vulnerability Detection

The security testing script can detect:

### Critical Vulnerabilities
- Authentication bypass
- JWT validation failures
- SQL injection points
- Session fixation
- Directory traversal

### High-Risk Issues
- XSS injection points
- Information disclosure
- Weak CORS configuration
- Missing security headers

### Medium-Risk Issues
- Rate limiting gaps
- Performance bottlenecks
- Memory leaks

## Using the Testing Scripts

### Quick Start
```bash
# Make scripts executable
chmod +x *.sh

# Run comprehensive test suite
./test_jwt.sh

# Run security audit
./security_test.sh

# Run performance tests
./performance_test.sh
```

### Individual Test Execution
```bash
# Test specific functionality
./test_jwt.sh auth
./security_test.sh jwt
./performance_test.sh concurrent
```

### Continuous Integration
For CI/CD integration:
```bash
# Non-verbose mode for CI
./test_jwt.sh auth > /dev/null 2>&1
echo $?

# Security audit with exit codes
./security_test.sh injection
if [ $? -ne 0 ]; then
    echo "Security vulnerabilities detected!"
    exit 1
fi
```

## Interpreting Results

### Success Indicators
- ✅ Green text: Tests passed, security measures working
- 📊 Blue/Cyan text: Performance metrics and information
- 🛡️ Security status: SECURE (no vulnerabilities)

### Warning Indicators
- ⚠️ Yellow text: Potential issues or configuration gaps
- Check warnings for:
  - Missing security headers
  - Performance concerns
  - Configuration recommendations

### Critical Issues
- 🚨 Red text: Security vulnerabilities or failures
- Immediate action required for:
  - Authentication bypass
  - Injection vulnerabilities
  - Token validation failures

## Troubleshooting

### Common Issues
1. **API Server Not Running**
   ```bash
   php artisan serve --port=8181
   ```

2. **Redis Not Available**
   ```bash
   sudo systemctl start redis
   # Check connection
   redis-cli ping
   ```

3. **Missing Dependencies**
   ```bash
   sudo apt-get install curl jq bc
   ```

4. **Permission Issues**
   ```bash
   chmod +x *.sh
   ```

### Debug Mode
For detailed output:
```bash
# Verbose curl output
curl -v [URL]

# Debug script execution
bash -x ./test_jwt.sh
```

## Best Practices

### Before Production
1. Run complete test suite: `./test_jwt.sh && ./security_test.sh && ./performance_test.sh`
2. Review all warning messages
3. Address any critical vulnerabilities
4. Validate performance meets requirements

### Regular Testing
1. Schedule regular security audits: `./security_test.sh`
2. Performance monitoring: `./performance_test.sh`
3. Regression testing after updates: `./test_jwt.sh`

### CI/CD Integration
1. Include tests in deployment pipeline
2. Fail builds on security vulnerabilities
3. Monitor performance trends
4. Automated cleanup after tests

## Contributing

When modifying the testing scripts:
1. Maintain consistent output formatting
2. Add appropriate test descriptions
3. Update this documentation
4. Test changes thoroughly
5. Consider backwards compatibility

## Support

For issues with the testing scripts:
1. Check API server status
2. Verify all dependencies
3. Review error messages
4. Check Laravel logs: `tail -f storage/logs/laravel.log`
5. Validate Redis connectivity

These testing scripts provide comprehensive validation of your JWT multi-tenant authentication system, ensuring security, performance, and reliability before production deployment.