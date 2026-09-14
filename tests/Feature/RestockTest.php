<?php

namespace Tests\Feature;

use App\Models\ActivityLogs;
use App\Models\Hpp;
use App\Models\Products;
use App\Models\Restock;
use App\Models\User;
use App\Services\ProductService;
use App\Services\RestockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestockTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ProductService $productService;
    protected RestockService $restockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'nrk' => 'ADM001',
            'email' => 'admin@warjok.com',
            'employee_name' => 'Admin Warjok',
            'password' => 'secret123',
            'is_admin' => true,
        ]);

        $this->productService = app(ProductService::class);
        $this->restockService = app(RestockService::class);
    }

    /**
     * Test restock for product with calculated HPP (e.g., Nutrisari).
     * Rule: User enters quantity, price is automatically derived from HPP (Rp 2.950).
     */
    public function test_restock_calculated_hpp_product_increments_stock_and_uses_hpp(): void
    {
        $this->actingAs($this->user);

        $hppAirEs = Hpp::create(['name' => 'Air dan Es', 'unit' => 'Porsi', 'unit_cost' => 900]);
        $hppGula = Hpp::create(['name' => 'Gula', 'unit' => 'Porsi', 'unit_cost' => 300]);
        $hppKemasan = Hpp::create(['name' => 'Kemasan', 'unit' => 'Pcs', 'unit_cost' => 350]);

        $product = $this->productService->createProduct([
            'prod_name' => 'Nutrisari Florida Orange',
            'satuan' => 'Sachet',
            'selling_price' => 5000,
            'unit_price' => 1400,
            'hpp_method' => 'calculated',
            'min_stock' => 10,
            'current_stock' => 10,
            'description' => 'Nutrisari dingin',
            'components' => [
                ['hpp_id' => $hppAirEs->id, 'cost' => 900],
                ['hpp_id' => $hppGula->id, 'cost' => 300],
                ['hpp_id' => $hppKemasan->id, 'cost' => 350],
            ],
        ]);

        $this->assertEquals(2950.000, (float)$product->current_hpp);
        $this->assertEquals(10, $product->current_stock);

        // Perform Restock with Quantity = 25
        $payload = [
            'supplier_name' => 'Toko Sembako Makmur',
            'restock_date' => now()->toDateString(),
            'notes' => 'Restock mingguan minuman sachet',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 25,
                ]
            ]
        ];

        $response = $this->postJson(route('restock.store'), $payload);
        $response->assertStatus(201);

        // Assert database records
        $this->assertDatabaseHas('restock', [
            'supplier_name' => 'Toko Sembako Makmur',
            'created_by' => $this->user->id,
        ]);

        $restock = Restock::where('supplier_name', 'Toko Sembako Makmur')->first();

        $this->assertDatabaseHas('restock_items', [
            'restock_id' => $restock->id,
            'product_id' => $product->id,
            'quantity' => 25,
        ]);

        // Assert stock incremented: 10 + 25 = 35
        $product->refresh();
        $this->assertEquals(35, $product->current_stock);

        // Assert Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'module' => 'RESTOCK',
            'entity_id' => $restock->id,
        ]);
    }

    /**
     * Test restock for product with manual HPP (e.g., Gorengan).
     * Rule: User enters quantity and can input/update manual purchase price.
     */
    public function test_restock_manual_hpp_product_allows_custom_price(): void
    {
        $this->actingAs($this->user);

        $product = $this->productService->createProduct([
            'prod_name' => 'Gorengan Ote-ote',
            'satuan' => 'Porsi',
            'selling_price' => 101500,
            'unit_price' => 50000,
            'hpp_method' => 'manual',
            'current_hpp' => 50000,
            'min_stock' => 2,
            'current_stock' => 5,
            'description' => 'Gorengan titipan',
        ]);

        $this->assertEquals(5, $product->current_stock);

        // Restock with updated manual price = 53000, quantity = 10
        $payload = [
            'supplier_name' => 'Bu Retno Gorengan',
            'notes' => 'Restock sore hari',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 53000,
                ]
            ]
        ];

        $response = $this->postJson(route('restock.store'), $payload);
        $response->assertStatus(201);

        // Assert stock updated: 5 + 10 = 15
        $product->refresh();
        $this->assertEquals(15, $product->current_stock);
        $this->assertEquals(53000.000, (float)$product->unit_price);
        $this->assertEquals(53000.000, (float)$product->current_hpp);
    }

    /**
     * Test restock multi-items in a single transaction and verify total value calculation.
     */
    public function test_restock_multi_items_and_total_value_calculation(): void
    {
        $this->actingAs($this->user);

        // Product 1: Aqua (manual HPP 2084)
        $aqua = $this->productService->createProduct([
            'prod_name' => 'Aqua Botol 600ml',
            'satuan' => 'Botol',
            'selling_price' => 5000,
            'unit_price' => 2084,
            'hpp_method' => 'manual',
            'current_hpp' => 2084,
            'min_stock' => 10,
            'current_stock' => 20,
        ]);

        // Product 2: Beng Beng (manual HPP 2206)
        $bengbeng = $this->productService->createProduct([
            'prod_name' => 'Beng Beng',
            'satuan' => 'Pcs',
            'selling_price' => 3000,
            'unit_price' => 2206,
            'hpp_method' => 'manual',
            'current_hpp' => 2206,
            'min_stock' => 10,
            'current_stock' => 15,
        ]);

        $payload = [
            'supplier_name' => 'Agen Minuman & Snack Subur',
            'items' => [
                ['product_id' => $aqua->id, 'quantity' => 24, 'unit_price' => 2084], // Subtotal: 24 * 2084 = 50016
                ['product_id' => $bengbeng->id, 'quantity' => 20, 'unit_price' => 2206], // Subtotal: 20 * 2206 = 44120
            ]
        ];

        $response = $this->postJson(route('restock.store'), $payload);
        $response->assertStatus(201);

        $restockId = $response->json('data.id');
        $detailedRestock = $this->restockService->getRestockById($restockId);

        // Total quantity: 24 + 20 = 44
        $this->assertEquals(44, $detailedRestock->total_quantity);
        // Total value: 50016 + 44120 = 94136
        $this->assertEquals(94136.000, (float)$detailedRestock->total_value);

        // Check stock updates
        $aqua->refresh();
        $bengbeng->refresh();
        $this->assertEquals(44, $aqua->current_stock); // 20 + 24
        $this->assertEquals(35, $bengbeng->current_stock); // 15 + 20
    }

    /**
     * Test validation on restock store request.
     */
    public function test_restock_validation_fails_on_invalid_data(): void
    {
        $this->actingAs($this->user);

        // Missing supplier and empty items
        $response = $this->postJson(route('restock.store'), [
            'supplier_name' => '',
            'items' => []
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['supplier_name', 'items']);
    }

    /**
     * Test rollback of product stock when a restock transaction is deleted / cancelled.
     */
    public function test_delete_restock_rolls_back_product_stock(): void
    {
        $this->actingAs($this->user);

        $product = $this->productService->createProduct([
            'prod_name' => 'Good Day Freeze',
            'satuan' => 'Sachet',
            'selling_price' => 6000,
            'unit_price' => 2400,
            'hpp_method' => 'manual',
            'current_hpp' => 2400,
            'min_stock' => 5,
            'current_stock' => 10,
        ]);

        // Restock +30 items -> stock becomes 40
        $restock = $this->restockService->createRestock([
            'supplier_name' => 'Distributor Kopi',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 30]
            ]
        ], $this->user->id);

        $product->refresh();
        $this->assertEquals(40, $product->current_stock);

        // Delete restock
        $deleteResponse = $this->deleteJson(route('restock.destroy', $restock->id));
        $deleteResponse->assertStatus(200);

        // Product stock rolled back from 40 to 10
        $product->refresh();
        $this->assertEquals(10, $product->current_stock);

        // Assert database record deleted
        $this->assertDatabaseMissing('restock', ['id' => $restock->id]);
        $this->assertDatabaseMissing('restock_items', ['restock_id' => $restock->id]);

        // Assert Activity Log for rollback
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'DELETE',
            'module' => 'RESTOCK',
            'entity_id' => $restock->id,
        ]);
    }
}
