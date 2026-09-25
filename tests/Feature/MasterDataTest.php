<?php

namespace Tests\Feature;

use App\Models\Hpp;
use App\Models\Products;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Unit $defaultUnit;
    protected Unit $gelasUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'nrk' => 'ADM001',
            'email' => 'admin@warjok.com',
            'employee_name' => 'Admin Warjok',
            'password' => 'secret123',
            'is_admin' => true,
        ]);

        $this->defaultUnit = Unit::create([
            'unit_name' => 'Sachet',
            'type' => 'Kemasan',
            'short_name' => 'sct',
        ]);

        $this->gelasUnit = Unit::create([
            'unit_name' => 'Gelas',
            'type' => 'Porsi',
            'short_name' => 'gls',
        ]);
    }

    /**
     * Test HPP CRUD and Activity Log creation.
     */
    public function test_hpp_crud_and_activity_logging(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create HPP
        $response = $this->postJson(route('hpp.store'), [
            'name' => 'Air dan Es',
            'unit' => 'Porsi',
            'unit_cost' => 900,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('hpp', [
            'name' => 'Air dan Es',
            'unit_cost' => 900,
        ]);

        $hpp = Hpp::where('name', 'Air dan Es')->first();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'module' => 'HPP',
            'entity_id' => $hpp->id,
        ]);

        // 2. Update HPP
        $updateResponse = $this->putJson(route('hpp.update', $hpp->id), [
            'name' => 'Air dan Es Segar',
            'unit' => 'Porsi',
            'unit_cost' => 950,
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('hpp', [
            'id' => $hpp->id,
            'name' => 'Air dan Es Segar',
            'unit_cost' => 950,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'UPDATE',
            'module' => 'HPP',
            'entity_id' => $hpp->id,
        ]);

        // 3. Delete HPP
        $deleteResponse = $this->deleteJson(route('hpp.destroy', $hpp->id));
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('hpp', ['id' => $hpp->id]);
    }

    /**
     * Test Product Creation with multiple sales configurations (Nutrisari Sachet & Gelas).
     */
    public function test_product_with_multiple_sales_configurations(): void
    {
        $this->actingAs($this->adminUser);

        $hppRacikanEs = Hpp::create(['name' => 'Racikan Es & Gula', 'unit' => 'Porsi', 'unit_cost' => 1550]);

        $payload = [
            'prod_name' => 'Nutrisari Cincau',
            'unit_id' => $this->defaultUnit->id,
            'hpp_method' => 'CALCULATED',
            'initial_stock' => 40,
            'min_stock' => 5,
            'description' => 'Nutrisari rasa cincau',
            'configurations' => [
                [
                    'selling_unit_id' => $this->defaultUnit->id,
                    'hpp_id' => null,
                    'current_hpp' => 1400,
                    'selling_price' => 1800,
                ],
                [
                    'selling_unit_id' => $this->gelasUnit->id,
                    'hpp_id' => $hppRacikanEs->id,
                    'current_hpp' => 2950,
                    'selling_price' => 5000,
                ],
            ],
        ];

        $response = $this->postJson(route('products.store'), $payload);
        $response->assertStatus(201);

        $product = Products::where('prod_name', 'Nutrisari Cincau')->first();
        $this->assertNotNull($product);
        $this->assertEquals(40, $product->initial_stock);
        $this->assertEquals(40, $product->current_stock);
        $this->assertCount(2, $product->productHpps);

        // Verify Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'module' => 'PRODUCT',
            'entity_id' => $product->id,
        ]);
    }

    /**
     * Test Live HPP Calculation AJAX endpoint.
     */
    public function test_live_calculate_hpp_endpoint(): void
    {
        $this->actingAs($this->adminUser);

        $hppAir = Hpp::create(['name' => 'Air & Es', 'unit' => 'Porsi', 'unit_cost' => 1550]);

        $response = $this->postJson(route('products.calculate_hpp'), [
            'base_purchase_price' => 1400,
            'hpp_id' => $hppAir->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'base_purchase_price' => 1400,
                'additional_cost'     => 1550,
                'current_hpp'         => 2950,
            ],
        ]);
    }

    /**
     * Test User CRUD and Access Matrix.
     */
    public function test_user_crud_and_access_permissions(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'nrk' => 'KAS002',
            'email' => 'kasir2@warjok.com',
            'employee_name' => 'Kasir Dua',
            'password' => 'password123',
            'is_admin' => false,
            'accesses' => [
                'products' => ['index' => 1, 'show' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'restock' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 0, 'delete' => 0],
            ],
        ];

        $response = $this->postJson(route('users.store'), $payload);
        $response->assertStatus(201);

        $user = User::where('email', 'kasir2@warjok.com')->first();
        $this->assertNotNull($user);
        $this->assertCount(2, $user->accesses);

        $productAccess = $user->accesses()->where('menu_access', 'products')->first();
        $this->assertEquals('1', $productAccess->index_acs);
        $this->assertEquals('0', $productAccess->create_acs);
    }
}