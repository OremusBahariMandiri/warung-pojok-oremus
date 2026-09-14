<?php

namespace Tests\Feature;

use App\Models\ActivityLogs;
use App\Models\Hpp;
use App\Models\Products;
use App\Models\Reports;
use App\Models\User;
use App\Services\ProductService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ProductService $productService;
    protected ReportService $reportService;

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
        $this->reportService = app(ReportService::class);
    }

    /**
     * Test report creation with accurate sales, HPP, margin, and stock deduction.
     * Based on Excel sheet example:
     * - Nutrisari: Qty 3, Selling 5.000, HPP 2.950 -> Sales 15.000, HPP 8.850, Margin 6.150
     * - Beng Beng: Qty 1, Selling 3.000, HPP 2.206 -> Sales 3.000, HPP 2.206, Margin 794
     * - Gorengan: Qty 1, Selling 101.500, HPP 53.000 -> Sales 101.500, HPP 53.000, Margin 48.500
     */
    public function test_create_report_calculates_sales_hpp_margin_and_deducts_stock(): void
    {
        $this->actingAs($this->user);

        // Setup 1: Nutrisari (Calculated HPP)
        $hppAirEs = Hpp::create(['name' => 'Air dan Es', 'unit' => 'Porsi', 'unit_cost' => 900]);
        $hppGula = Hpp::create(['name' => 'Gula', 'unit' => 'Porsi', 'unit_cost' => 300]);
        $hppKemasan = Hpp::create(['name' => 'Kemasan', 'unit' => 'Pcs', 'unit_cost' => 350]);

        $nutrisari = $this->productService->createProduct([
            'prod_name' => 'Nutrisari Florida Orange',
            'satuan' => 'Sachet',
            'selling_price' => 5000,
            'unit_price' => 1400,
            'hpp_method' => 'calculated',
            'min_stock' => 5,
            'current_stock' => 20,
            'components' => [
                ['hpp_id' => $hppAirEs->id, 'cost' => 900],
                ['hpp_id' => $hppGula->id, 'cost' => 300],
                ['hpp_id' => $hppKemasan->id, 'cost' => 350],
            ],
        ]);

        // Setup 2: Beng Beng (Manual HPP)
        $bengbeng = $this->productService->createProduct([
            'prod_name' => 'Beng Beng',
            'satuan' => 'Pcs',
            'selling_price' => 3000,
            'unit_price' => 2206,
            'hpp_method' => 'manual',
            'current_hpp' => 2206,
            'min_stock' => 5,
            'current_stock' => 15,
        ]);

        // Setup 3: Gorengan (Manual HPP)
        $gorengan = $this->productService->createProduct([
            'prod_name' => 'Gorengan Ote-ote',
            'satuan' => 'Porsi',
            'selling_price' => 101500,
            'unit_price' => 53000,
            'hpp_method' => 'manual',
            'current_hpp' => 53000,
            'min_stock' => 1,
            'current_stock' => 10,
        ]);

        $payload = [
            'report_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $nutrisari->id, 'quantity' => 3],
                ['product_id' => $bengbeng->id, 'quantity' => 1],
                ['product_id' => $gorengan->id, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson(route('reports.store'), $payload);
        $response->assertStatus(201);

        $reportId = $response->json('data.id');
        $report = $this->reportService->getReportById($reportId);

        // Assert Header Totals
        // total_quantity = 3 + 1 + 1 = 5
        $this->assertEquals(5, $report->total_quantity);
        // total_sales = 15000 + 3000 + 101500 = 119500
        $this->assertEquals(119500.000, (float)$report->total_sales);
        // total_hpp = (3*2950=8850) + (1*2206=2206) + (1*53000=53000) = 64056
        $this->assertEquals(64056.000, (float)$report->total_hpp);
        // total_margin = 119500 - 64056 = 55444
        $this->assertEquals(55444.000, (float)$report->total_margin);

        // Assert Product Stock Deductions
        $nutrisari->refresh();
        $bengbeng->refresh();
        $gorengan->refresh();

        $this->assertEquals(17, $nutrisari->current_stock); // 20 - 3
        $this->assertEquals(14, $bengbeng->current_stock); // 15 - 1
        $this->assertEquals(9, $gorengan->current_stock);  // 10 - 1

        // Assert Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'module' => 'REPORT',
            'entity_id' => $report->id,
        ]);
    }

    /**
     * Test multi-day period summary recap (Excel Total Margin tab).
     */
    public function test_report_period_summary_recap(): void
    {
        $this->actingAs($this->user);

        $aqua = $this->productService->createProduct([
            'prod_name' => 'Aqua Botol 600ml',
            'satuan' => 'Botol',
            'selling_price' => 5000,
            'unit_price' => 2084,
            'hpp_method' => 'manual',
            'current_hpp' => 2084,
            'min_stock' => 5,
            'current_stock' => 50,
        ]);

        // Report Day 1 (Yesterday)
        $this->reportService->createReport([
            'report_date' => now()->subDay()->toDateString(),
            'items' => [
                ['product_id' => $aqua->id, 'quantity' => 10], // Sales: 50000, HPP: 20840, Margin: 29160
            ]
        ], $this->user->id);

        // Report Day 2 (Today)
        $this->reportService->createReport([
            'report_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $aqua->id, 'quantity' => 5], // Sales: 25000, HPP: 10420, Margin: 14580
            ]
        ], $this->user->id);

        $startDate = now()->subDays(2)->toDateString();
        $endDate = now()->toDateString();

        $response = $this->getJson(route('reports.summary', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertStatus(200);

        $data = $response->json('data');

        // Total quantity = 10 + 5 = 15
        $this->assertEquals(15, $data['overall']['total_quantity']);
        // Total sales = 50000 + 25000 = 75000
        $this->assertEquals(75000.000, (float)$data['overall']['total_sales']);
        // Total hpp = 20840 + 10420 = 31260
        $this->assertEquals(31260.000, (float)$data['overall']['total_hpp']);
        // Total margin = 29160 + 14580 = 43740
        $this->assertEquals(43740.000, (float)$data['overall']['total_margin']);
    }

    /**
     * Test validation on report creation.
     */
    public function test_report_validation_fails_on_empty_items(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('reports.store'), [
            'items' => []
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    /**
     * Test deleting a report rolls back product stock.
     */
    public function test_delete_report_rolls_back_product_stock(): void
    {
        $this->actingAs($this->user);

        $product = $this->productService->createProduct([
            'prod_name' => 'White Coffee',
            'satuan' => 'Sachet',
            'selling_price' => 5000,
            'unit_price' => 2850,
            'hpp_method' => 'manual',
            'current_hpp' => 2850,
            'min_stock' => 5,
            'current_stock' => 20,
        ]);

        // Create report with 6 items sold -> stock becomes 14
        $report = $this->reportService->createReport([
            'report_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 6]
            ]
        ], $this->user->id);

        $product->refresh();
        $this->assertEquals(14, $product->current_stock);

        // Delete report
        $deleteResponse = $this->deleteJson(route('reports.destroy', $report->id));
        $deleteResponse->assertStatus(200);

        // Stock restored to 20
        $product->refresh();
        $this->assertEquals(20, $product->current_stock);

        // Database records removed
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
        $this->assertDatabaseMissing('report_details', ['report_id' => $report->id]);

        // Activity log for DELETE recorded
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'DELETE',
            'module' => 'REPORT',
            'entity_id' => $report->id,
        ]);
    }
}
