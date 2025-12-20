# BiznesPilot AI - API Testing Guide

## Quick Start Testing

### Prerequisites
1. Start Laravel development server:
   ```bash
   php artisan serve
   ```
   Default URL: `http://localhost:8000`

2. Ensure database is migrated:
   ```bash
   php artisan migrate
   ```

## Test Endpoints with cURL

### 1. Register a New User

**With Email:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**With Phone:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Jane Smith",
    "phone": "+1234567890",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 2. Login

**With Email:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "login": "john@example.com",
    "password": "password123"
  }'
```

**With Phone:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "login": "+1234567890",
    "password": "password123"
  }'
```

**Save the token from the response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {...},
    "token": "1|abcdef123456..."  // <- Copy this token
  }
}
```

### 3. Get Current User

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 4. Logout

```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Test with Postman

### Setup Postman Collection

1. **Create Environment:**
   - Variable: `base_url` = `http://localhost:8000/api/v1`
   - Variable: `token` = (leave empty, will be set automatically)

2. **Create Requests:**

#### Register Request
- Method: `POST`
- URL: `{{base_url}}/auth/register`
- Headers:
  ```
  Content-Type: application/json
  Accept: application/json
  ```
- Body (JSON):
  ```json
  {
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- Tests (to save token):
  ```javascript
  if (pm.response.code === 201) {
    const response = pm.response.json();
    pm.environment.set("token", response.data.token);
  }
  ```

#### Login Request
- Method: `POST`
- URL: `{{base_url}}/auth/login`
- Headers:
  ```
  Content-Type: application/json
  Accept: application/json
  ```
- Body (JSON):
  ```json
  {
    "login": "test@example.com",
    "password": "password123"
  }
  ```
- Tests:
  ```javascript
  if (pm.response.code === 200) {
    const response = pm.response.json();
    pm.environment.set("token", response.data.token);
  }
  ```

#### Get Me Request
- Method: `GET`
- URL: `{{base_url}}/auth/me`
- Headers:
  ```
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}
  ```

#### Logout Request
- Method: `POST`
- URL: `{{base_url}}/auth/logout`
- Headers:
  ```
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}
  ```
- Tests:
  ```javascript
  if (pm.response.code === 200) {
    pm.environment.unset("token");
  }
  ```

## Test Middleware

### Test Business Access Middleware

1. **Create a Business** (manually in database or via seeder):
   ```php
   $business = Business::create([
       'user_id' => 1,
       'name' => 'Test Business',
       'slug' => 'test-business',
   ]);
   ```

2. **Test with X-Business-ID Header:**
   ```bash
   curl -X GET http://localhost:8000/api/v1/businesses \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "X-Business-ID: 1"
   ```

3. **Test without X-Business-ID (should fail):**
   ```bash
   curl -X GET http://localhost:8000/api/v1/businesses \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```
   Expected: 400 Bad Request

4. **Test with unauthorized business ID (should fail):**
   ```bash
   curl -X GET http://localhost:8000/api/v1/businesses \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "X-Business-ID: 999"
   ```
   Expected: 403 Forbidden

### Test Subscription Middleware

1. **Create a subscription for the business:**
   ```php
   $subscription = Subscription::create([
       'business_id' => 1,
       'plan_id' => 1,
       'status' => 'active',
       'starts_at' => now(),
       'ends_at' => now()->addMonth(),
   ]);
   ```

2. **Test with active subscription (should work):**
   ```bash
   curl -X GET http://localhost:8000/api/v1/businesses \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "X-Business-ID: 1"
   ```

3. **Expire the subscription and test again (should fail):**
   ```php
   $subscription->update(['ends_at' => now()->subDay()]);
   ```
   Expected: 402 Payment Required

### Test Feature Limit Middleware

1. **Create a plan with limits:**
   ```php
   $plan = Plan::create([
       'name' => 'Basic',
       'slug' => 'basic',
       'lead_limit' => 10,
       'team_member_limit' => 2,
       'chatbot_channel_limit' => 1,
   ]);
   ```

2. **Test creating a lead (example route):**
   ```bash
   curl -X POST http://localhost:8000/api/v1/leads \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "X-Business-ID: 1" \
     -H "Content-Type: application/json" \
     -d '{"name": "New Lead"}'
   ```

3. **After reaching limit (should fail):**
   Expected: 403 Forbidden with upgrade message

## Validation Testing

### Test Required Fields

**Missing name:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```
Expected: 422 Unprocessable Entity

### Test Email/Phone Required

**Missing both email and phone:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```
Expected: 422 Unprocessable Entity

### Test Password Confirmation

**Mismatched password:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "different_password"
  }'
```
Expected: 422 Unprocessable Entity

### Test Unique Constraints

**Duplicate email:**
```bash
# Register first user
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "First User",
    "email": "duplicate@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Try to register with same email
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Second User",
    "email": "duplicate@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```
Expected: 422 Unprocessable Entity

## Testing Workflow

### Complete User Journey

1. **Register** → Get token
2. **Login** → Verify token works
3. **Get Me** → Verify user data
4. **Logout** → Revoke token
5. **Try Get Me again** → Should fail (401 Unauthorized)
6. **Login again** → Get new token

### Complete Business Journey

1. **Register/Login** → Get auth token
2. **Create Business** → (via database or API)
3. **Access Business Route** → With X-Business-ID header
4. **Try unauthorized business** → Should fail (403)
5. **Create Subscription** → Enable business features
6. **Test Feature Limits** → Create resources until limit
7. **Logout** → Clean up

## Expected HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | OK | Successful request (login, logout, get) |
| 201 | Created | Successful registration |
| 400 | Bad Request | Missing required parameters |
| 401 | Unauthorized | Invalid or missing token |
| 402 | Payment Required | No active subscription |
| 403 | Forbidden | No access or limit exceeded |
| 404 | Not Found | Business not found |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Internal Server Error | Server error |

## Troubleshooting

### Token Not Working

1. Check if token is valid:
   ```sql
   SELECT * FROM personal_access_tokens WHERE tokenable_id = YOUR_USER_ID;
   ```

2. Verify Bearer format:
   ```
   Authorization: Bearer 1|abc123...
   ```
   NOT: `Authorization: 1|abc123...`

### Routes Not Found

1. Clear route cache:
   ```bash
   php artisan route:clear
   php artisan route:cache
   ```

2. List all routes:
   ```bash
   php artisan route:list --path=api
   ```

### Session Issues with Middleware

1. Check session driver in `.env`:
   ```
   SESSION_DRIVER=database
   ```

2. Run session migration:
   ```bash
   php artisan session:table
   php artisan migrate
   ```

### Database Connection Issues

1. Verify `.env` database settings:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=biznespilot
   DB_USERNAME=root
   DB_PASSWORD=
   ```

2. Test connection:
   ```bash
   php artisan migrate:status
   ```

## Automated Testing with PHPUnit

Create test file: `tests/Feature/AuthenticationTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_email()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user', 'token']
            ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user', 'token']
            ]);
    }

    public function test_authenticated_user_can_access_me_endpoint()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }
}
```

Run tests:
```bash
php artisan test --filter AuthenticationTest
```

## API Documentation Tools

### Install Laravel Scribe (Optional)

```bash
composer require --dev knuckleswtf/scribe
php artisan vendor:publish --tag=scribe-config
php artisan scribe:generate
```

### Access Docs
After generation: `http://localhost:8000/docs`

## Monitoring & Debugging

### Use Laravel Telescope

Already installed - access at: `http://localhost:8000/telescope`

Monitor:
- API requests
- Database queries
- Exceptions
- Authentication attempts

### Enable Query Log

In your controller:
```php
\DB::enableQueryLog();
// ... your code
dd(\DB::getQueryLog());
```

## Production Checklist

Before deploying:

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set strong `APP_KEY`
- [ ] Configure proper database
- [ ] Set `SESSION_DRIVER=database` or `redis`
- [ ] Configure CORS properly
- [ ] Set up HTTPS
- [ ] Configure rate limiting
- [ ] Set up monitoring
- [ ] Test all endpoints
- [ ] Review error handling
- [ ] Set up logging
- [ ] Configure backup strategy
- [ ] Document API for frontend team
