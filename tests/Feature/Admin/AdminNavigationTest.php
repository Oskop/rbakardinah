<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Unit;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Administrator',
            'is_active' => true,
        ]);
    }

    public function test_admin_sees_master_data_dropdown_in_navigation(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Verify Master Data label and icon
        $response->assertSee('Master Data');

        // Verify sub-links in Master Data dropdown
        $response->assertSee(route('admin.units.index'));
        $response->assertSee(route('admin.users.index'));
        $response->assertSee(route('admin.kelompok-belanja.index'));
        $response->assertSee(route('admin.account-codes.index'));
        $response->assertSee(route('admin.periods.index'));

        // Verify standalone menus are still present
        $response->assertSee(route('dashboard'));
        $response->assertSee(route('admin.headers.index'));
        $response->assertSee(route('admin.logs.index'));
        $response->assertSee(route('documentation.index'));
    }

    public function test_master_data_dropdown_shows_active_state_when_sub_route_is_accessed(): void
    {
        $period = RbaPeriod::create(['name' => 'Murni 2026', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->get(route('admin.periods.index'));

        $response->assertStatus(200);

        // When accessing admin.periods.index, the Master Data trigger should have active border class
        $response->assertSee('border-indigo-400 text-gray-900 font-semibold', false);
    }

    public function test_supervisor_and_operator_do_not_see_admin_master_data_dropdown(): void
    {
        $unit = Unit::create(['code' => 'U01', 'name' => 'Unit Farmasi', 'is_active' => true]);

        $supervisor = User::factory()->create([
            'role' => 'Supervisor',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $operator = User::factory()->create([
            'role' => 'Operator',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $responseSupervisor = $this->actingAs($supervisor)->get(route('supervisor.submissions.index'));
        $responseSupervisor->assertStatus(200);
        $responseSupervisor->assertDontSee('Master Data');

        $responseOperator = $this->actingAs($operator)->get(route('operator.submissions.index'));
        $responseOperator->assertStatus(200);
        $responseOperator->assertDontSee('Master Data');
    }
}
