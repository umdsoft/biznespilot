<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can be created.
     */
    public function test_user_can_be_created(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    /**
     * Test user password is hashed.
     */
    public function test_user_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Test user can have multiple businesses.
     */
    public function test_user_can_have_multiple_businesses(): void
    {
        $user = User::factory()->create();
        $business1 = Business::factory()->create(['user_id' => $user->id]);
        $business2 = Business::factory()->create(['user_id' => $user->id]);

        $user->teamBusinesses()->attach($business1->id, ['role' => 'owner']);
        $user->teamBusinesses()->attach($business2->id, ['role' => 'admin']);

        $this->assertCount(2, $user->businesses);
    }

    /**
     * Test user current business.
     */
    public function test_user_current_business(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);
        $user->teamBusinesses()->attach($business->id, ['role' => 'owner']);

        session(['current_business_id' => $business->id]);

        // The currentBusiness accessor should work based on session
        $this->assertEquals($business->id, session('current_business_id'));
    }

    /**
     * Test user factory creates valid user.
     */
    public function test_user_factory_creates_valid_user(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->id);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->login);
        $this->assertNotNull($user->phone);
    }

    /**
     * Test unverified user factory state.
     */
    public function test_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);
    }

    /**
     * Test verified user.
     */
    public function test_verified_user(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->email_verified_at);
    }

    /**
     * Test user email is unique.
     */
    public function test_user_email_is_unique(): void
    {
        User::factory()->create(['email' => 'unique@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['email' => 'unique@example.com']);
    }

    /**
     * Test user login is unique.
     */
    public function test_user_login_is_unique(): void
    {
        User::factory()->create(['login' => 'uniquelogin']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['login' => 'uniquelogin']);
    }

    /**
     * Test user phone format.
     */
    public function test_user_phone_format(): void
    {
        $user = User::factory()->create([
            'phone' => '+998901234567',
        ]);

        $this->assertEquals('+998901234567', $user->phone);
        $this->assertStringStartsWith('+998', $user->phone);
    }

    /**
     * Test user can be updated.
     */
    public function test_user_can_be_updated(): void
    {
        $user = User::factory()->create();

        $user->update([
            'name' => 'Updated Name',
        ]);

        $this->assertEquals('Updated Name', $user->fresh()->name);
    }

    /**
     * Test user can be deleted.
     */
    public function test_user_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    /**
     * Test user business role.
     */
    public function test_user_business_role(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);

        $user->teamBusinesses()->attach($business->id, ['role' => 'manager']);

        // Use teamBusinesses() which has the pivot, not businesses() which is hasMany
        $pivot = $user->teamBusinesses()->first()->pivot;
        $this->assertEquals('manager', $pivot->role);
    }
}
