<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UsersAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful login with NRK and password.
     */
    public function test_user_can_login_with_valid_nrk_and_password(): void
    {
        $user = User::create([
            'nrk' => 'ADM001',
            'email' => 'admin@warjok.com',
            'employee_name' => 'Admin Utama',
            'password' => Hash::make('rahasia123'),
            'is_admin' => true,
        ]);

        $response = $this->post(route('login.post'), [
            'nrk' => 'ADM001',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertAuthenticatedAs($user);

        // Verify activity log recorded
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'LOGIN',
            'module' => 'AUTH',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test login failure with wrong password.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::create([
            'nrk' => 'ADM001',
            'email' => 'admin@warjok.com',
            'employee_name' => 'Admin Utama',
            'password' => Hash::make('rahasia123'),
            'is_admin' => true,
        ]);

        $response = $this->post(route('login.post'), [
            'nrk' => 'ADM001',
            'password' => 'password_salah',
        ]);

        $response->assertSessionHasErrors(['nrk']);
        $this->assertGuest();
    }

    /**
     * Test logout clears session and records activity log.
     */
    public function test_user_can_logout_and_session_is_cleared(): void
    {
        $user = User::create([
            'nrk' => 'ADM001',
            'email' => 'admin@warjok.com',
            'employee_name' => 'Admin Utama',
            'password' => Hash::make('rahasia123'),
            'is_admin' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();

        // Verify logout activity log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'LOGOUT',
            'module' => 'AUTH',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test Super Admin (is_admin == true) has full access to all routes.
     */
    public function test_admin_user_has_full_access_to_all_modules(): void
    {
        $admin = User::create([
            'nrk' => 'ADM999',
            'email' => 'superadmin@warjok.com',
            'employee_name' => 'Super Admin',
            'password' => Hash::make('secret123'),
            'is_admin' => true,
        ]);

        $this->actingAs($admin);

        // Can access all resources
        $this->getJson(route('products.index'))->assertStatus(200);
        $this->getJson(route('hpp.index'))->assertStatus(200);
        $this->getJson(route('restock.index'))->assertStatus(200);
        $this->getJson(route('reports.index'))->assertStatus(200);
        $this->getJson(route('users.index'))->assertStatus(200);
        $this->getJson(route('activity_logs.index'))->assertStatus(200);
    }

    /**
     * Test non-admin user RBAC matrix (allowed on '1', forbidden 403 on '0').
     */
    public function test_non_admin_user_rbac_matrix_permissions(): void
    {
        $kasir = User::create([
            'nrk' => 'KAS001',
            'email' => 'kasir@warjok.com',
            'employee_name' => 'Staff Kasir',
            'password' => Hash::make('kasir123'),
            'is_admin' => false,
        ]);

        // Kasir only allowed to index & show products, but forbidden to create/delete products or access user management
        UsersAccess::create([
            'user_id' => $kasir->id,
            'menu_access' => 'products',
            'index_acs' => '1',
            'show_acs' => '1',
            'create_acs' => '0',
            'edit_acs' => '0',
            'delete_acs' => '0',
        ]);

        $this->actingAs($kasir);

        // 1. Allowed to view products index
        $this->getJson(route('products.index'))->assertStatus(200);

        // 2. Forbidden (403) to create products
        $this->postJson(route('products.store'), [
            'prod_name' => 'Produk Ilegal',
            'satuan' => 'Pcs',
            'selling_price' => 5000,
            'unit_price' => 2000,
            'hpp_method' => 'manual',
            'min_stock' => 5,
        ])->assertStatus(403);

        // 3. Forbidden (403) to access users management module (no permission record)
        $this->get(route('users.index'))->assertStatus(403);
    }

    /**
     * Test unauthenticated guest is redirected to login.
     */
    public function test_unauthenticated_guest_is_redirected_to_login(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get(route('restock.index'))->assertRedirect(route('login'));
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }
}