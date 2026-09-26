<?php

namespace Tests\Feature;

use App\Models\Hpp;
use App\Models\Products;
use App\Models\Reports;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Unit $sachetUnit;
    protected Unit $gelasUnit;
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

        $this->sachetUnit = Unit::create([
            'unit_name' => 'Sachet',
            'type' => 'Kemasan',
            'short_name' => 'sct',
        ]);

        $this->gelasUnit = Unit::create([
            'unit_name' => 'Gelas',
            'type' => 'Porsi',
            'short_name' => 'gls',
        ]);

        $this->productService = app(ProductService::class);
        $this->reportService  = app(ReportService::class);
    }

    /**
     * Test report creation matching Excalidraw example:
     * Product: Nutrisari Cincau (initial_stock: 40)
     * - Config 1 (Gelas): Qty 8 x Selling 5.000 = 40.000, HPP 2.950 -> Total HPP 23.600, Margin 16.400
     * - Config 2 (Sachet): Qty 4 x Selling 1.800 = 7.200, HPP 1.400 -> Total HPP 5.600, Margin 1.600
     * Header Totals: Qty 12, Sales 47.200, HPP 29.200, Margin 18.000
     * Stock Deduction: 40 - 8 - 4 = 28
     */
    public function test_create_report_calculates_sales_hpp_margin_and_deducts_stock(): void
    {
        $this->actingAs($this->user);

        $hppRacikan = Hpp::create(['name' => 'Racikan Es & Gula', 'unit' => 'Porsi', 'unit_cost' => 1550]);

        $nutrisari = $this->productService->createProduct([
            'prod_name'     => 'Nutrisari Cincau',
            'unit_id'       => $this->sachetUnit->id,
            'hpp_method'    => 'CALCULATED',
            'initial_stock' => 40,
            'current_stock' => 40,
            'min_stock'     => 5,
            'configurations' => [
                [
                    'selling_unit_id' => $this->sachetUnit->id,
                    'hpp_id'          => null,
                    'current_hpp'     => 1400,
                    'selling_price'   => 1800,
                ],
                [
                    'selling_unit_id' => $this->gelasUnit->id,
                    'hpp_id'          => $hppRacikan->id,
                    'current_hpp'     => 2950,
                    'selling_price'   => 5000,
                ],
            ],
        ]);

        $payload = [
            'report_date' => now()->toDateString(),
            'notes'       => 'Laporan penjualan harian',
            'items'       => [
                [
                    'product_id'      => $nutrisari->id,
                    'selling_unit_id' => $this->gelasUnit->id,
                    'quantity'        => 8,
                ],
                [
                    'product_id'      => $nutrisari->id,
                    'selling_unit_id' => $this->sachetUnit->id,
                    'quantity'        => 4,
                ],
            ],
        ];

        $response = $this->postJson(route('reports.store'), $payload);
        $response->assertStatus(201);

        $reportId = $response->json('data.id');
        $report   = $this->reportService->getReportById($reportId);

        // Assert Header Totals
        // total_quantity = 8 + 4 = 12
        $this->assertEquals(12, $report->total_quantity);
        // total_sales = 40000 + 7200 = 47200
        $this->assertEquals(47200.000, (float) $report->total_sales);
        // total_hpp = 23600 + 5600 = 29200
        $this->assertEquals(29200.000, (float) $report->total_hpp);
        // total_margin = 47200 - 29200 = 18000
        $this->assertEquals(18000.000, (float) $report->total_margin);

        // Assert Product Stock Deduction: 40 - 8 - 4 = 28
        $nutrisari->refresh();
        $this->assertEquals(28, $nutrisari->current_stock);

        // Assert Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'action'    => 'CREATE',
            'module'    => 'REPORT',
            'entity_id' => $report->id,
        ]);
    }

    /**
     * Test multi-day period summary recap.
     */
    public function test_report_period_summary_recap(): void
    {
        $this->actingAs($this->user);

        $aqua = $this->productService->createProduct([
            'prod_name'     => 'Aqua Botol 600ml',
            'unit_id'       => $this->sachetUnit->id,
            'hpp_method'    => 'MANUAL',
            'initial_stock' => 50,
            'current_stock' => 50,
            'min_stock'     => 5,
            'configurations' => [
                [
                    'selling_unit_id' => $this->sachetUnit->id,
                    'hpp_id'          => null,
                    'current_hpp'     => 2084,
                    'selling_price'   => 5000,
                ]
            ]
        ]);

        // Report Day 1 (Yesterday): 10 botol -> Sales: 50000, HPP: 20840, Margin: 29160
        $this->reportService->createReport([
            'report_date' => now()->subDay()->toDateString(),
            'items'       => [
                [
                    'product_id'      => $aqua->id,
                    'selling_unit_id' => $this->sachetUnit->id,
                    'quantity'        => 10,
                ]
            ]
        ], $this->user->id);

        // Report Day 2 (Today): 5 botol -> Sales: 25000, HPP: 10420, Margin: 14580
        $this->reportService->createReport([
            'report_date' => now()->toDateString(),
            'items'       => [
                [
                    'product_id'      => $aqua->id,
                    'selling_unit_id' => $this->sachetUnit->id,
                    'quantity'        => 5,
                ]
            ]
        ], $this->user->id);

        $startDate = now()->subDays(2)->toDateString();
        $endDate   = now()->toDateString();

        $response = $this->getJson(route('reports.summary', [
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]));

        $response->assertStatus(200);

        $data = $response->json('data');

        // Total quantity = 10 + 5 = 15
        $this->assertEquals(15, $data['overall']['total_quantity']);
        // Total sales = 50000 + 25000 = 75000
        $this->assertEquals(75000.000, (float) $data['overall']['total_sales']);
        // Total hpp = 20840 + 10420 = 31260
        $this->assertEquals(31260.000, (float) $data['overall']['total_hpp']);
        // Total margin = 29160 + 14580 = 43740
        $this->assertEquals(43740.000, (float) $data['overall']['total_margin']);
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
            'prod_name'     => 'White Coffee',
            'unit_id'       => $this->sachetUnit->id,
            'hpp_method'    => 'MANUAL',
            'initial_stock' => 20,
            'current_stock' => 20,
            'min_stock'     => 5,
            'configurations' => [
                [
                    'selling_unit_id' => $this->sachetUnit->id,
                    'hpp_id'          => null,
                    'current_hpp'     => 2850,
                    'selling_price'   => 5000,
                ]
            ]
        ]);

        // Create report with 6 items sold -> stock becomes 14
        $report = $this->reportService->createReport([
            'report_date' => now()->toDateString(),
            'items'       => [
                [
                    'product_id'      => $product->id,
                    'selling_unit_id' => $this->sachetUnit->id,
                    'quantity'        => 6,
                ]
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
            'action'    => 'DELETE',
            'module'    => 'REPORT',
            'entity_id' => $report->id,
        ]);
    }
}
