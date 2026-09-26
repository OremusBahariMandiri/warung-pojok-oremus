<?php

namespace Tests\Feature;

use App\Models\Hpp;
use App\Models\Products;
use App\Models\Restock;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductService;
use App\Services\RestockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestockTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Unit $sachetUnit;
    protected Unit $boxUnit;
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

        $this->sachetUnit = Unit::create([
            'unit_name' => 'Sachet',
            'type' => 'Kemasan',
            'short_name' => 'sct',
        ]);

        $this->boxUnit = Unit::create([
            'unit_name' => 'Box',
            'type' => 'Kemasan',
            'short_name' => 'box',
        ]);

        $this->productService = app(ProductService::class);
        $this->restockService = app(RestockService::class);
    }

    /**
     * Test Restock DRAFT vs CONFIRMED status behavior.
     * DRAFT: stock is NOT incremented.
     * CONFIRMED: stock IS incremented.
     */
    public function test_restock_draft_does_not_increment_stock_until_confirmed(): void
    {
        $this->actingAs($this->user);

        $product = $this->productService->createProduct([
            'prod_name' => 'Nutrisari Cincau',
            'unit_id' => $this->sachetUnit->id,
            'hpp_method' => 'CALCULATED',
            'initial_stock' => 40,
            'current_stock' => 40,
            'min_stock' => 5,
        ]);

        // 1. Create DRAFT Restock with Qty = 20
        $payload = [
            'supplier_name'  => 'Toko Sembako Makmur',
            'invoice_number' => 'INV-20260925-001',
            'restock_date'   => now()->toDateString(),
            'status_restock' => 'DRAFT',
            'discount'       => 2000,
            'notes'          => 'Restock mingguan minuman sachet',
            'items'          => [
                [
                    'product_id'      => $product->id,
                    'restock_unit_id' => $this->boxUnit->id,
                    'quantity'        => 20,
                    'purchase_price'  => 1400,
                ]
            ]
        ];

        $response = $this->postJson(route('restock.store'), $payload);
        $response->assertStatus(201);

        $restockId = $response->json('data.id');
        $restock   = Restock::find($restockId);

        // Subtotal = 20 * 1400 = 28000, Grand Total = 28000 - 2000 = 26000
        $this->assertEquals(28000.000, (float) $restock->subtotal);
        $this->assertEquals(26000.000, (float) $restock->grand_total);
        $this->assertEquals('DRAFT', $restock->status_restock);

        // Stock should remain 40 because transaction is DRAFT
        $product->refresh();
        $this->assertEquals(40, $product->current_stock);

        // 2. Update status to CONFIRMED
        $statusResponse = $this->patchJson(route('restock.update_status', $restock->id), [
            'status_restock' => 'CONFIRMED',
        ]);
        $statusResponse->assertStatus(200);

        // Stock should now be incremented: 40 + 20 = 60
        $product->refresh();
        $this->assertEquals(60, $product->current_stock);
    }

    /**
     * Test Restock directly CONFIRMED on creation.
     */
    public function test_restock_confirmed_directly_increments_stock(): void
    {
        $this->actingAs($this->user);

        $product = $this->productService->createProduct([
            'prod_name' => 'Aqua Botol 600ml',
            'unit_id' => $this->sachetUnit->id,
            'hpp_method' => 'MANUAL',
            'initial_stock' => 10,
            'current_stock' => 10,
            'min_stock' => 5,
        ]);

        $payload = [
            'supplier_name'  => 'Distributor Air',
            'restock_date'   => now()->toDateString(),
            'status_restock' => 'CONFIRMED',
            'items'          => [
                [
                    'product_id'      => $product->id,
                    'restock_unit_id' => $this->sachetUnit->id,
                    'quantity'        => 30,
                    'purchase_price'  => 2000,
                ]
            ]
        ];

        $response = $this->postJson(route('restock.store'), $payload);
        $response->assertStatus(201);

        $product->refresh();
        $this->assertEquals(40, $product->current_stock);
    }

    /**
     * Test rollback of product stock when a confirmed restock is deleted.
     */
    public function test_delete_confirmed_restock_rolls_back_product_stock(): void
    {
        $this->actingAs($this->user);

        $product = $this->productService->createProduct([
            'prod_name' => 'Good Day Freeze',
            'unit_id' => $this->sachetUnit->id,
            'hpp_method' => 'MANUAL',
            'initial_stock' => 10,
            'current_stock' => 10,
            'min_stock' => 5,
        ]);

        $restock = $this->restockService->createRestock([
            'supplier_name'  => 'Distributor Kopi',
            'restock_date'   => now()->toDateString(),
            'status_restock' => 'CONFIRMED',
            'items'          => [
                [
                    'product_id'      => $product->id,
                    'restock_unit_id' => $this->sachetUnit->id,
                    'quantity'        => 30,
                    'purchase_price'  => 2400,
                ]
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

        $this->assertDatabaseMissing('restock', ['id' => $restock->id]);
    }
}
