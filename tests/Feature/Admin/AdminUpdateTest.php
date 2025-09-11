<?php

    namespace Tests\Feature\Admin;

    use App\Models\Admin;
    use App\Models\Restaurant;
    use Illuminate\Foundation\Testing\RefreshDatabase; // Add this if you don't have it
    use Illuminate\Support\Facades\Hash;
    use Laravel\Sanctum\Sanctum; // <-- 1. Import Sanctum
    use Tests\TestCase;

    class AdminUpdateTest extends TestCase
    {
        use RefreshDatabase; // Use this trait to ensure a clean database for every test

        private Admin $admin;

        protected function setUp(): void
        {
            parent::setUp();
            $restaurant = Restaurant::factory()->create();
            $this->admin = Admin::factory()->create([
                'restaurant_id' => $restaurant->id,
                'user_name' => 'old_username',
                'password' => Hash::make('old_password'),
            ]);
        }

        /** @test */
        public function it_successfully_updates_admin_profile(): void
        {
            // 2. Use Sanctum's helper to authenticate
            Sanctum::actingAs($this->admin, guard: 'admin-api');

            $payload = [
                'name' => 'New Name',
                'user_name' => 'new_username',
                'password' => 'new_strong_password_123',
            ];

            // 3. Make the API call (no need for actingAs here anymore)
            $response = $this->postJson(route('admin.profile.update'), $payload);

            $response->assertStatus(200);
            // ... rest of your assertions
        }

        /** @test */
        public function it_fails_validation_if_username_is_taken(): void
        {
            // Use Sanctum's helper here too
            Sanctum::actingAs($this->admin, guard: 'admin-api');

            Admin::factory()->create(['user_name' => 'existing_user']);
            $payload = ['user_name' => 'existing_user'];

            $response = $this->postJson(route('admin.profile.update'), $payload);

            $response->assertStatus(422)
                ->assertJsonValidationErrorFor('user_name');
        }
    }
