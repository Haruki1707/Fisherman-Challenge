<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Customer;
use App\Models\Street;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_with_costumers_is_displayed(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_customers_can_be_filtered(): void
    {
        Street::create(['name' => 'test street']);
        City::create(['name' => 'test city']);

        Customer::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'address' => '587',
            'street_id' => '1',
            'city_id' => '1',
            'phone_number' => 123456789
        ]);

        $response = $this->get('/', [
            'search' => 'john@example.com',
            'search_type' => 'Email'
        ]);

        $response->assertInertia(fn(Assert $page) => $page->has('customers', 1)
            ->where('customers.0.email', 'john@example.com'));
    }
}
