<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Administrator',
            'email' => 'admin.unit@hospital.com',
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'role' => 'Operator',
            'email' => 'operator.unit@hospital.com',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_units_index_with_status_column()
    {
        $activeUnit = Unit::create(['code' => 'U01', 'name' => 'Unit Pelayanan Aktif', 'is_active' => true]);
        $inactiveUnit = Unit::create(['code' => 'U02', 'name' => 'Unit Pelayanan Nonaktif', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->get(route('admin.units.index'));

        $response->assertStatus(200);
        $response->assertSee('Unit Management');
        $response->assertSee('Status');
        $response->assertSee('Unit Pelayanan Aktif');
        $response->assertSee('Unit Pelayanan Nonaktif');
        $response->assertSee('Active');
        $response->assertSee('Inactive');
        $response->assertSee('Nonaktifkan');
        $response->assertSee('Aktifkan');

        // Verify there is no "Delete" button text
        $response->assertDontSee('Delete');
    }

    public function test_admin_can_deactivate_unit_instead_of_deleting()
    {
        $unit = Unit::create(['code' => 'U03', 'name' => 'Unit Rawat Bedah', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->delete(route('admin.units.destroy', $unit));

        $response->assertRedirect(route('admin.units.index'));
        $response->assertSessionHas('success');

        // Verify record is NOT deleted from database
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'code' => 'U03',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_reactivate_unit()
    {
        $unit = Unit::create(['code' => 'U04', 'name' => 'Unit Pemeliharaan', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->delete(route('admin.units.destroy', $unit));

        $response->assertRedirect(route('admin.units.index'));
        $response->assertSessionHas('success');

        // Verify record is reactivated
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'code' => 'U04',
            'is_active' => true,
        ]);
    }

    public function test_unit_deactivation_is_recorded_in_activity_log()
    {
        $unit = Unit::create(['code' => 'U05', 'name' => 'Unit Laboratorium', 'is_active' => true]);

        // 1. Deactivate
        $this->actingAs($this->admin)->delete(route('admin.units.destroy', $unit));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'model_type' => Unit::class,
            'model_id' => $unit->id,
            'action' => 'updated',
        ]);

        $deactivateLog = ActivityLog::where('model_type', Unit::class)
            ->where('model_id', $unit->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($deactivateLog);
        $this->assertStringContainsString('menonaktifkan Unit Kerja: "Unit Laboratorium"', $deactivateLog->description);

        // 2. Reactivate
        $this->actingAs($this->admin)->delete(route('admin.units.destroy', $unit));

        $reactivateLog = ActivityLog::where('model_type', Unit::class)
            ->where('model_id', $unit->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($reactivateLog);
        $this->assertStringContainsString('mengaktifkan Unit Kerja: "Unit Laboratorium"', $reactivateLog->description);
    }

    public function test_non_admin_cannot_manage_units()
    {
        $unit = Unit::create(['code' => 'U06', 'name' => 'Unit Sterilisasi', 'is_active' => true]);

        $response = $this->actingAs($this->operator)->get(route('admin.units.index'));
        $response->assertStatus(403);

        $responseDelete = $this->actingAs($this->operator)->delete(route('admin.units.destroy', $unit));
        $responseDelete->assertStatus(403);
    }
}
