<?php

namespace Tests\Feature;

use App\Models\Products;
use App\Models\StockOpname;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductService;
use App\Services\StockOpnameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Unit $unit;
    protected ProductService $productService;
    protected StockOpnameService $opnameService;

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

        $this->unit = Unit::create([
            'unit_name' => 'Sachet',
            'type' => 'Kemasan',
            'short_name' => 'sct',
        ]);

        $this->productService = app(ProductService::class);
        $this->opnameService  = app(StockOpnameService::class);
    }

    /**
     * Test Stock Opname DRAFT status calculates difference without altering stock.
     */
    public function test_stock_opname_draft_calculates_difference_without_updating_stock(): void
    {
        $this->actingAs($this->user);

        // Product with system stock = 30
        $product = $this->productService->createProduct([
            'prod_name' => 'Nutrisari Cincau',
            'unit_id' => $this->unit->id,
            'hpp_method' => 'CALCULATED',
            'initial_stock' => 30,
            'current_stock' => 30,
            'min_stock' => 5,
        ]);

        // Audit physical stock = 26 (difference = 26 - 30 = -4)
        $payload = [
            'opname_date'   => now()->toDateString(),
            'status_opname' => 'DRAFT',
            'notes'         => 'Audit akhir bulan',
            'items'         => [
                [
                    'product_id'     => $product->id,
                    'physical_stock' => 26,
                ]
            ]
        ];

        $response = $this->postJson(route('stock-opname.store'), $payload);
        $response->assertStatus(201);

        $opnameId = $response->json('data.id');
        $opname   = StockOpname::with('items')->find($opnameId);

        $this->assertEquals('DRAFT', $opname->status_opname);
        $this->assertCount(1, $opname->items);

        $item = $opname->items->first();
        $this->assertEquals(30, $item->system_stock);
        $this->assertEquals(26, $item->physical_stock);
        $this->assertEquals(-4, $item->difference);

        // Stock in master product remains 30 because DRAFT
        $product->refresh();
        $this->assertEquals(30, $product->current_stock);
    }

    /**
     * Test Stock Opname CONFIRMED updates current_stock to physical_stock.
     */
    public function test_stock_opname_confirmed_updates_current_stock_to_physical_stock(): void
    {
        $this->actingAs($this->user);

        $product = $this->productService->createProduct([
            'prod_name' => 'Teh Botol Sosro',
            'unit_id' => $this->unit->id,
            'hpp_method' => 'MANUAL',
            'initial_stock' => 30,
            'current_stock' => 30,
            'min_stock' => 5,
        ]);

        $payload = [
            'opname_date'   => now()->toDateString(),
            'status_opname' => 'CONFIRMED',
            'notes'         => 'Penyesuaian stok rusak 4 botol',
            'items'         => [
                [
                    'product_id'     => $product->id,
                    'physical_stock' => 26,
                ]
            ]
        ];

        $response = $this->postJson(route('stock-opname.store'), $payload);
        $response->assertStatus(201);

        // Current stock should be updated from 30 to 26
        $product->refresh();
        $this->assertEquals(26, $product->current_stock);

        // Assert Activity Log created
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'module' => 'STOCK_OPNAME',
        ]);
    }
}
