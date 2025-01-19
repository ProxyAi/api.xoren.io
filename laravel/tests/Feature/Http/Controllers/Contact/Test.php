<?php

namespace Tests\Feature\Http\Controllers\Contact;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class Test extends \Tests\TestCase
{
    use RefreshDatabase, WithFaker;
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Run the database migrations
        $this->artisan('migrate');
        // Run the DatabaseSeeder...
        $this->seed();
    }
    /**
     * A feature test for testing /contact web endpoint.
     */
    public function testContactEndpoint(): void
    {
        // Arrange: Set up the necessary data for the test
        $data = [
            'name' => $this->faker->name,
            'phone' => $this->faker->e164PhoneNumber,
            'email' => $this->faker->email,
            'message' => $this->faker->text,
        ];

        // Act: Send a POST request to the /contact endpoint with the given data
        $response = $this
            ->withoutMiddleware()
            ->postJson('/contact', $data);

        // Assert: Perform assertions to verify the expected behavior
        $response->assertStatus(201); // Assuming a successful response status code

        // Assert the database has the record
        $this->assertDatabaseHas('contact_requests', $data);

    }
    /**
     * Test the contact endpoint with missing data.
     */
    public function testContactApiWithMissingData()
    {
        // Create the data payload with missing name
        $data = [
            'email' => $this->faker->email,
            'message' => $this->faker->text,
        ];

        // Make a POST request to the /api/contact endpoint
        $response = $this->postJson('/contact', $data);

        // Assert the response status
        $response->assertStatus(422);
    }

    /**
     * Test the contact endpoint with invalid email.
     */
    public function testContactApiWithInvalidEmail()
    {
        // Create the data payload with an invalid email
        $data = [
            'name' => $this->faker->name,
            'email' => 'invalid-email',
            'message' => $this->faker->text,
        ];

        // Make a POST request to the /contact endpoint
        $response = $this->postJson('/contact', $data);

        // Assert the response status
        $response->assertStatus(422);
    }
}
