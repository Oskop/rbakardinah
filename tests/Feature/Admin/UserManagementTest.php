<?php

namespace Tests\Feature\Admin;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Unit $unitA;
    protected Unit $unitB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unitA = Unit::create(['name' => 'Instalasi Bedah Sentral', 'code' => 'IBS']);
        $this->unitB = Unit::create(['name' => 'Instalasi Rawat Jalan', 'code' => 'IRJ']);

        $this->admin = User::factory()->create([
            'role' => 'Administrator',
            'email' => 'admin.test@hospital.com',
            'auth_provider' => 'local',
        ]);
    }

    public function test_admin_can_view_user_management_index_with_filters()
    {
        // Seed diverse users
        User::factory()->create([
            'name' => 'Dr. Supervisor Bedah',
            'role' => 'Supervisor',
            'unit_id' => $this->unitA->id,
            'is_active' => true,
            'auth_provider' => 'local',
        ]);

        User::factory()->create([
            'name' => 'Operator SSO Belum Ditugaskan',
            'nip' => '199001012020011001',
            'role' => 'Operator',
            'unit_id' => null,
            'is_active' => true,
            'auth_provider' => 'simrs_oidc',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
        $response->assertViewHas('users');
        $response->assertViewHas('units');

        // Verify column filter UI elements exist
        $response->assertSee('Filter Kolom Pengguna');
        $response->assertSee('id="filter-role"', false);
        $response->assertSee('id="filter-unit"', false);
        $response->assertSee('id="filter-status"', false);
        $response->assertSee('id="filter-provider"', false);
        $response->assertSee('id="btn-reset-filters"', false);

        // Verify unit options in filter
        $response->assertSee('Instalasi Bedah Sentral');
        $response->assertSee('Instalasi Rawat Jalan');
        $response->assertSee('Belum Ditugaskan / Tanpa Unit');

        // Verify user list displays
        $response->assertSee('Dr. Supervisor Bedah');
        $response->assertSee('Operator SSO Belum Ditugaskan');
        $response->assertSee('199001012020011001');
        $response->assertSee('SSO SIMRS');

        // Verify data-search and data-filter attributes are rendered properly for DataTables filtering
        $response->assertSee('data-search="Operator"', false);
        $response->assertSee('data-search="Supervisor"', false);
        $response->assertSee('data-search="Active"', false);
        $response->assertSee('data-search="Belum Ditugaskan"', false);
        $response->assertSee('data-search="SSO SIMRS"', false);
    }

    public function test_non_admin_cannot_access_admin_user_management()
    {
        $operator = User::factory()->create([
            'role' => 'Operator',
            'unit_id' => $this->unitA->id,
        ]);

        $response = $this->actingAs($operator)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }
}
