<?php

namespace Database\Seeders;

use App\Models\Hpp;
use App\Models\Products;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductService;
use App\Services\UserService;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $userService = app(UserService::class);
        $productService = app(ProductService::class);

        // 1. Seed Users & Access
        $admin = $userService->createUser([
            'nrk' => 'ADM001',
            'email' => 'admin@oremus.com',
            'employee_name' => 'Administrator Warjok',
            'password' => 'admin123',
            'is_admin' => true,
            'accesses' => [
                'dashboard' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'products' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'units' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'hpp' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'restock' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'reports' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'users' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
                'activity_logs' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 1, 'delete' => 1],
            ],
        ]);

        $kasir = $userService->createUser([
            'nrk' => 'KAS001',
            'email' => 'kasir@oremus.com',
            'employee_name' => 'Petugas Kasir',
            'password' => 'kasir123',
            'is_admin' => false,
            'accesses' => [
                'dashboard' => ['index' => 1, 'show' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'products' => ['index' => 1, 'show' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'units' => ['index' => 1, 'show' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'hpp' => ['index' => 1, 'show' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0],
                'restock' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 0, 'delete' => 0],
                'reports' => ['index' => 1, 'show' => 1, 'create' => 1, 'edit' => 0, 'delete' => 0],
            ],
        ]);

        // 2. Seed Units (Satuan)
        $unitSachet = Unit::create(['unit_name' => 'Sachet', 'type' => 'Kemasan Minuman', 'short_name' => 'sct']);
        $unitGelas = Unit::create(['unit_name' => 'Gelas', 'type' => 'Porsi Minuman', 'short_name' => 'gls']);
        $unitPorsi = Unit::create(['unit_name' => 'Porsi', 'type' => 'Porsi Makanan', 'short_name' => 'prs']);
        $unitBotol = Unit::create(['unit_name' => 'Botol', 'type' => 'Kemasan Minuman', 'short_name' => 'btl']);
        $unitBungkus = Unit::create(['unit_name' => 'Bungkus', 'type' => 'Kemasan Makanan', 'short_name' => 'bgk']);
        $unitPcs = Unit::create(['unit_name' => 'Pcs', 'type' => 'Satuan Item', 'short_name' => 'pcs']);
        $unitKg = Unit::create(['unit_name' => 'Kilogram', 'type' => 'Berat', 'short_name' => 'kg']);
        $unitLiter = Unit::create(['unit_name' => 'Liter', 'type' => 'Volume', 'short_name' => 'ltr']);

        // 3. Seed Master Komponen HPP
        $hppAirEs = Hpp::create(['name' => 'Air dan Es', 'unit' => 'Porsi', 'unit_cost' => 900]);
        $hppAir = Hpp::create(['name' => 'Air', 'unit' => 'Porsi', 'unit_cost' => 500]);
        $hppGula = Hpp::create(['name' => 'Gula', 'unit' => 'Porsi', 'unit_cost' => 300]);
        $hppKemasan = Hpp::create(['name' => 'Kemasan', 'unit' => 'Pcs', 'unit_cost' => 350]);
        $hppGas = Hpp::create(['name' => 'Gas', 'unit' => 'Porsi', 'unit_cost' => 200]);

        // 4. Seed Products (Calculated & Manual)
        // Minuman Ice (Calculated)
        $iceComponents = [
            ['hpp_id' => $hppAirEs->id, 'cost' => 900],
            ['hpp_id' => $hppGula->id, 'cost' => 300],
            ['hpp_id' => $hppKemasan->id, 'cost' => 350],
        ];

        $productService->createProduct([
            'prod_name' => 'Nutrisari Florida Orange',
            'unit_id' => $unitSachet->id,
            'selling_price' => 5000,
            'unit_price' => 1400,
            'hpp_method' => 'calculated',
            'min_stock' => 10,
            'current_stock' => 50,
            'description' => 'Minuman instan rasa jeruk Florida Orange dingin',
            'components' => $iceComponents,
        ]);

        $productService->createProduct([
            'prod_name' => 'Good Day Freeze',
            'unit_id' => $unitSachet->id,
            'selling_price' => 6000,
            'unit_price' => 2400,
            'hpp_method' => 'calculated',
            'min_stock' => 10,
            'current_stock' => 40,
            'description' => 'Kopi dingin Good Day Freeze',
            'components' => $iceComponents,
        ]);

        $productService->createProduct([
            'prod_name' => 'Good Day Cappucino',
            'unit_id' => $unitSachet->id,
            'selling_price' => 6000,
            'unit_price' => 2150,
            'hpp_method' => 'calculated',
            'min_stock' => 10,
            'current_stock' => 35,
            'description' => 'Kopi Good Day Cappucino',
            'components' => $iceComponents,
        ]);

        $productService->createProduct([
            'prod_name' => 'Milo',
            'unit_id' => $unitSachet->id,
            'selling_price' => 6000,
            'unit_price' => 1950,
            'hpp_method' => 'calculated',
            'min_stock' => 10,
            'current_stock' => 25,
            'description' => 'Susu coklat Milo dingin',
            'components' => $iceComponents,
        ]);

        // Minuman Hot (Calculated)
        $hotComponents = [
            ['hpp_id' => $hppAir->id, 'cost' => 500],
            ['hpp_id' => $hppGula->id, 'cost' => 300],
            ['hpp_id' => $hppKemasan->id, 'cost' => 350],
            ['hpp_id' => $hppGas->id, 'cost' => 200],
        ];

        $productService->createProduct([
            'prod_name' => 'Energen Kacang Ijo',
            'unit_id' => $unitSachet->id,
            'selling_price' => 5000,
            'unit_price' => 2050,
            'hpp_method' => 'calculated',
            'min_stock' => 10,
            'current_stock' => 30,
            'description' => 'Sereal Energen rasa kacang ijo hangat',
            'components' => $hotComponents,
        ]);

        $productService->createProduct([
            'prod_name' => 'White Coffee',
            'unit_id' => $unitSachet->id,
            'selling_price' => 5000,
            'unit_price' => 1500,
            'hpp_method' => 'calculated',
            'min_stock' => 10,
            'current_stock' => 30,
            'description' => 'Kopi Luwak White Coffee hangat',
            'components' => $hotComponents,
        ]);

        // Produk Manual HPP
        $productService->createProduct([
            'prod_name' => 'Aqua Botol 600ml',
            'unit_id' => $unitBotol->id,
            'selling_price' => 5000,
            'unit_price' => 2084,
            'hpp_method' => 'manual',
            'current_hpp' => 2084,
            'min_stock' => 12,
            'current_stock' => 48,
            'description' => 'Air mineral Aqua botol 600ml',
        ]);

        $productService->createProduct([
            'prod_name' => 'Beng Beng',
            'unit_id' => $unitPcs->id,
            'selling_price' => 3000,
            'unit_price' => 2206,
            'hpp_method' => 'manual',
            'current_hpp' => 2206,
            'min_stock' => 10,
            'current_stock' => 20,
            'description' => 'Snack wafer coklat Beng Beng',
        ]);

        $productService->createProduct([
            'prod_name' => 'Nasi Bungkus Pak Ali',
            'unit_id' => $unitBungkus->id,
            'selling_price' => 8000,
            'unit_price' => 6000,
            'hpp_method' => 'manual',
            'current_hpp' => 6000,
            'min_stock' => 5,
            'current_stock' => 15,
            'description' => 'Nasi bungkus titipan Pak Ali',
        ]);

        $productService->createProduct([
            'prod_name' => 'Gorengan Ote-ote',
            'unit_id' => $unitPorsi->id,
            'selling_price' => 101500,
            'unit_price' => 53000,
            'hpp_method' => 'manual',
            'current_hpp' => 53000,
            'min_stock' => 1,
            'current_stock' => 5,
            'description' => 'Gorengan aneka macam',
        ]);
    }
}
