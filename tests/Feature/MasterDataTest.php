<?php

namespace Tests\Feature;


use App\Models\Hpp;
use App\Models\Products;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

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
     * Test Product Creation with Calculated HPP method (Nutrisari Example).
     */
    public function test_product_calculated_hpp_method(): void
    {
        $this->actingAs($this->adminUser);

        $hppAirEs = Hpp::create(['name' => 'Air dan Es', 'unit' => 'Porsi', 'unit_cost' => 900]);
        $hppGula = Hpp::create(['name' => 'Gula', 'unit' => 'Porsi', 'unit_cost' => 300]);
        $hppKemasan = Hpp::create(['name' => 'Kemasan', 'unit' => 'Pcs', 'unit_cost' => 350]);

        $payload = [
            'prod_name' => 'Nutrisari Florida Orange',
            'satuan' => 'Sachet',
            'selling_price' => 5000,
            'unit_price' => 1400,
            'hpp_method' => 'calculated',
            'min_stock' => 10,
            'current_stock' => 50,
            'description' => 'Nutrisari dingin segar',
            'components' => [
                ['hpp_id' => $hppAirEs->id, 'cost' => 900],
                ['hpp_id' => $hppGula->id, 'cost' => 300],
                ['hpp_id' => $hppKemasan->id, 'cost' => 350],
            ],
        ];

        $response = $this->postJson(route('products.store'), $payload);
        $response->assertStatus(201);

        $product = Products::where('prod_name', 'Nutrisari Florida Orange')->first();
        $this->assertNotNull($product);

        // Expected HPP = 1400 (unit_price) + 900 + 300 + 350 = 2950
        $this->assertEquals(2950.000, (float)$product->current_hpp);
        $this->assertCount(3, $product->productHpps);

        // Verify Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'module' => 'PRODUCT',
            'entity_id' => $product->id,
        ]);
    }

    /**
     * Test Product Creation with Manual HPP method (Gorengan Example).
     */
    public function test_product_manual_hpp_method(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'prod_name' => 'Gorengan Ote-ote',
            'satuan' => 'Porsi',
            'selling_price' => 101500,
            'unit_price' => 53000,
            'hpp_method' => 'manual',
            'current_hpp' => 53000,
            'min_stock' => 2,
            'current_stock' => 10,
            'description' => 'Gorengan tanpa breakdown HPP',
        ];

        $response = $this->postJson(route('products.store'), $payload);
        $response->assertStatus(201);

        $product = Products::where('prod_name', 'Gorengan Ote-ote')->first();
        $this->assertNotNull($product);
        $this->assertEquals(53000.000, (float)$product->current_hpp);
        $this->assertCount(0, $product->productHpps);
    }

    /**
     * Test Live HPP Calculation AJAX endpoint.
     */
    public function test_live_calculate_hpp_endpoint(): void
    {
        $this->actingAs($this->adminUser);

        $hppAir = Hpp::create(['name' => 'Air', 'unit' => 'Porsi', 'unit_cost' => 500]);
        $hppGula = Hpp::create(['name' => 'Gula', 'unit' => 'Porsi', 'unit_cost' => 300]);
        $hppKemasan = Hpp::create(['name' => 'Kemasan', 'unit' => 'Pcs', 'unit_cost' => 350]);
        $hppGas = Hpp::create(['name' => 'Gas', 'unit' => 'Porsi', 'unit_cost' => 200]);

        // Energen Kacang Ijo: Unit Price 2050 + Air 500 + Gula 300 + Kemasan 350 + Gas 200 = 3400
        $response = $this->postJson(route('products.calculate_hpp'), [
            'unit_price' => 2050,
            'components' => [
                ['hpp_id' => $hppAir->id, 'cost' => 500],
                ['hpp_id' => $hppGula->id, 'cost' => 300],
                ['hpp_id' => $hppKemasan->id, 'cost' => 350],
                ['hpp_id' => $hppGas->id, 'cost' => 200],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'total_hpp' => 3400,
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