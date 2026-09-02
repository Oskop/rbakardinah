<?php

namespace Tests\Feature\Supervisor;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $supervisor;
    protected Unit $unit;
    protected Unit $otherUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create(['name' => 'Instalasi Rawat Inap', 'code' => 'IRNA']);
        $this->otherUnit = Unit::create(['name' => 'Instalasi Farmasi', 'code' => 'IF']);

        $this->supervisor = User::factory()->create([
            'role' => 'Supervisor',
            'unit_id' => $this->unit->id,
            'name' => 'Supervisor Rawat Inap',
        ]);
    }

    public function test_supervisor_can_view_user_management_index_with_filters()
    {
        $operatorInUnit = User::factory()->create([
            'name' => 'Operator Perawat',
            'nip' => '199202022019012002',
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
            'is_active' => true,
            'auth_provider' => 'simrs_oidc',
        ]);

        $operatorOtherUnit = User::factory()->create([
            'name' => 'Operator Apoteker',
            'role' => 'Operator',
            'unit_id' => $this->otherUnit->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->supervisor)->get(route('supervisor.users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('supervisor.users.index');
        $response->assertViewHas('users');

        // Verify column filter UI elements exist
        $response->assertSee('Filter Kolom Pengguna Unit');
        $response->assertSee('id="filter-role"', false);
        $response->assertSee('id="filter-status"', false);
        $response->assertSee('id="filter-provider"', false);
        $response->assertSee('id="btn-reset-filters"', false);

        // Verify operator in this unit is visible
        $response->assertSee('Operator Perawat');
        $response->assertSee('199202022019012002');
        $response->assertSee('SSO SIMRS');

        // Verify data-search attributes for DataTables filtering
        $response->assertSee('data-search="Operator"', false);
        $response->assertSee('data-search="Active"', false);
        $response->assertSee('data-search="SSO SIMRS"', false);

        // Verify operator in other unit is not visible
        $response->assertDontSee('Operator Apoteker');
    }

    public function test_operator_cannot_access_supervisor_user_management()
    {
        $operator = User::factory()->create([
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->actingAs($operator)->get(route('supervisor.users.index'));
        $response->assertStatus(403);
    }
}
