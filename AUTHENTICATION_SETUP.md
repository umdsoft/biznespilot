# BiznesPilot AI - Authentication System Documentation

## Overview
This document describes the complete authentication system implementation for BiznesPilot AI with email/phone + password login using Laravel Sanctum.

## Created Files

### 1. Form Requests

#### RegisterRequest.php
**Location:** `d:\marketing startap\biznespilot\app\Http\Requests\Auth\RegisterRequest.php`

**Validation Rules:**
- `name`: required, string, max:255
- `email`: nullable, email, unique:users (required without phone)
- `phone`: nullable, string, max:20, unique:users (required without email)
- `password`: required, string, min:8, confirmed

**Features:**
- Ensures either email OR phone is provided
- Custom error messages for better UX
- Unique validation for both email and phone

#### LoginRequest.php
**Location:** `d:\marketing startap\biznespilot\app\Http\Requests\Auth\LoginRequest.php`

**Validation Rules:**
- `login`: required, string (accepts email or phone)
- `password`: required, string

### 2. Controllers

#### AuthController.php
**Location:** `d:\marketing startap\biznespilot\app\Http\Controllers\Api\Auth\AuthController.php`

**Methods:**

1. **register(RegisterRequest $request)**
   - Validates and creates new user
   - Hashes password
   - Generates Sanctum token
   - Returns user + token with 201 status

2. **login(LoginRequest $request)**
   - Automatically detects email vs phone login
   - Validates credentials
   - Generates Sanctum token
   - Returns user + token with 200 status

3. **logout(Request $request)**
   - Revokes current access token
   - Returns success message

4. **me(Request $request)**
   - Returns authenticated user
   - Includes businesses (owned) and teamBusinesses (member)

### 3. Middleware

#### EnsureBusinessAccess.php
**Location:** `d:\marketing startap\biznespilot\app\Http\Middleware\EnsureBusinessAccess.php`

**Functionality:**
- Checks X-Business-ID header or business_id parameter
- Verifies user is owner OR team member of the business
- Sets current_business_id in session
- Returns 403 if no access
- Returns 400 if business ID not provided

**Usage:**
```php
Route::middleware('business.access')->group(function () {
    // Protected business routes
});
```

#### CheckSubscription.php
**Location:** `d:\marketing startap\biznespilot\app\Http\Middleware\CheckSubscription.php`

**Functionality:**
- Gets current business from session
- Checks for active subscription
- Checks for active trial (not expired)
- Returns 402 if no active subscription

**Usage:**
```php
Route::middleware(['business.access', 'subscription'])->group(function () {
    // Routes requiring active subscription
});
```

#### CheckFeatureLimit.php
**Location:** `d:\marketing startap\biznespilot\app\Http\Middleware\CheckFeatureLimit.php`

**Functionality:**
- Checks feature limits based on subscription plan
- Supports: leads, team_members, chatbot_channels
- Returns 403 with upgrade message if limit reached

**Usage:**
```php
Route::post('leads', [LeadController::class, 'store'])
    ->middleware('feature.limit:leads');
Route::post('team-members', [TeamController::class, 'store'])
    ->middleware('feature.limit:team_members');
```

### 4. Routes

#### api.php
**Location:** `d:\marketing startap\biznespilot\routes\api.php`

**Public Routes (v1):**
- `POST /api/v1/auth/register` - User registration
- `POST /api/v1/auth/login` - User login

**Protected Routes (v1, requires auth:sanctum):**
- `POST /api/v1/auth/logout` - Logout current user
- `GET /api/v1/auth/me` - Get authenticated user

**Business Routes Template:**
```php
Route::middleware(['business.access', 'subscription'])->group(function () {
    // Add your business-scoped resources here
    Route::apiResource('leads', LeadController::class)
        ->middleware('feature.limit:leads');
});
```

### 5. Configuration

#### bootstrap/app.php
**Location:** `d:\marketing startap\biznespilot\bootstrap\app.php`

**Changes:**
- Added API routes registration
- Registered middleware aliases:
  - `business.access` => EnsureBusinessAccess::class
  - `subscription` => CheckSubscription::class
  - `feature.limit` => CheckFeatureLimit::class

#### User Model
**Location:** `d:\marketing startap\biznespilot\app\Models\User.php`

**Changes:**
- Added `HasApiTokens` trait for Laravel Sanctum support

## API Usage Examples

### 1. Register a New User

**Endpoint:** `POST /api/v1/auth/register`

**Request (Email):**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Request (Phone):**
```json
{
    "name": "John Doe",
    "phone": "+1234567890",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": null,
            "created_at": "2025-12-19T10:00:00.000000Z"
        },
        "token": "1|abcd1234..."
    }
}
```

### 2. Login

**Endpoint:** `POST /api/v1/auth/login`

**Request:**
```json
{
    "login": "john@example.com",
    "password": "password123"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": null
        },
        "token": "2|efgh5678..."
    }
}
```

### 3. Get Authenticated User

**Endpoint:** `GET /api/v1/auth/me`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": null,
            "businesses": [],
            "team_businesses": []
        }
    }
}
```

### 4. Logout

**Endpoint:** `POST /api/v1/auth/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Logout successful"
}
```

### 5. Business-Scoped Request

**Endpoint:** Any business route

**Headers:**
```
Authorization: Bearer {token}
X-Business-ID: 1
```

**OR Query Parameter:**
```
?business_id=1
```

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Login failed",
    "errors": {
        "login": ["The provided credentials are incorrect."]
    }
}
```

### No Business Access (403)
```json
{
    "success": false,
    "message": "You do not have access to this business"
}
```

### No Active Subscription (402)
```json
{
    "success": false,
    "message": "No active subscription found. Please upgrade your plan.",
    "error_code": "NO_ACTIVE_SUBSCRIPTION"
}
```

### Feature Limit Exceeded (403)
```json
{
    "success": false,
    "message": "Lead limit reached (100). Please upgrade your plan.",
    "error_code": "FEATURE_LIMIT_EXCEEDED",
    "upgrade_required": true
}
```

## Testing with Postman/Insomnia

### Setup
1. Set base URL: `http://localhost:8000/api/v1`
2. Create environment variables:
   - `base_url`: Your API base URL
   - `token`: Save after login/register

### Request Flow
1. **Register** → Save token
2. **Login** → Save token
3. **Get Me** → Use token in Authorization header
4. **Business Routes** → Use token + X-Business-ID header
5. **Logout** → Revokes token

## Security Features

1. **Password Hashing:** Uses bcrypt via Laravel's Hash facade
2. **Token-Based Auth:** Laravel Sanctum provides secure API tokens
3. **Token Revocation:** Logout deletes current access token
4. **Validation:** Comprehensive input validation
5. **Business Access Control:** Ensures users can only access their businesses
6. **Subscription Checks:** Enforces active subscription requirements
7. **Feature Limits:** Prevents exceeding plan limits

## Next Steps

To complete the system setup:

1. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

2. **Publish Sanctum Config (if needed):**
   ```bash
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   ```

3. **Create Business Controllers:**
   - BusinessController
   - LeadController
   - TeamMemberController
   - etc.

4. **Test the API:**
   - Use Postman/Insomnia
   - Create automated tests
   - Test all middleware scenarios

5. **Additional Features to Consider:**
   - Email verification
   - Phone verification (SMS)
   - Password reset
   - Two-factor authentication
   - Rate limiting
   - API documentation (Swagger/OpenAPI)

## File Structure

```
biznespilot/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── Auth/
│   │   │           └── AuthController.php
│   │   ├── Middleware/
│   │   │   ├── CheckFeatureLimit.php
│   │   │   ├── CheckSubscription.php
│   │   │   └── EnsureBusinessAccess.php
│   │   └── Requests/
│   │       └── Auth/
│   │           ├── LoginRequest.php
│   │           └── RegisterRequest.php
│   └── Models/
│       └── User.php (updated with HasApiTokens)
├── bootstrap/
│   └── app.php (updated with middleware aliases)
└── routes/
    └── api.php (created)
```

## Laravel Sanctum Notes

Laravel Sanctum is already installed (version 4.2.1). It provides:
- API token authentication for SPAs and mobile apps
- Simple token management
- Token abilities (permissions)
- Multiple tokens per user
- Token expiration support

## Support

For issues or questions:
1. Check Laravel Sanctum documentation: https://laravel.com/docs/sanctum
2. Review this documentation
3. Check error logs in `storage/logs/laravel.log`
4. Use `php artisan route:list` to verify routes
5. Use Laravel Telescope for debugging (already installed)
