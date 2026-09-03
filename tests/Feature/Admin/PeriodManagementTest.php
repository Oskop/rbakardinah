<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodManagementTest extends TestCase
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

    public function test_admin_can_view_periods_index_with_status_and_filters(): void
    {
        $activePeriod = RbaPeriod::create([
            'name' => 'Perencanaan Murni 2026',
            'is_active' => true,
        ]);

        $inactivePeriod = RbaPeriod::create([
            'name' => 'Perubahan Masa Lalu Nonaktif',
            'is_active' => false,
        ]);

        RbaHeader::create([
            'period_id' => $activePeriod->id,
            'year' => 2026,
            'admin_id' => $this->admin->id,
            'status_global' => 'Draft',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.periods.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Periode RBA');
        $response->assertSee('Perencanaan Murni 2026');
        $response->assertSee('Perubahan Masa Lalu Nonaktif');
        $response->assertSee('Active');
        $response->assertSee('Inactive');
        $response->assertSee('Nonaktifkan');
        $response->assertSee('Aktifkan');

        // Verify DataTables & Filter Toolbar UI
        $response->assertSee('Filter Kolom Periode RBA');
        $response->assertSee('id="periods-table"', false);
        $response->assertSee('id="filter-status"', false);
        $response->assertSee('id="filter-headers"', false);
        $response->assertSee('id="btn-reset-filters"', false);
        $response->assertSee('data-search="Active"', false);
        $response->assertSee('data-search="Inactive"', false);
        $response->assertSee('data-search="Used"', false);
        $response->assertSee('data-search="Unused"', false);

        // Verify there is no "Delete" button
        $response->assertDontSee('Delete');
    }

    public function test_admin_can_deactivate_period_instead_of_deleting(): void
    {
        $period = RbaPeriod::create([
            'name' => 'Pergeseran Anggaran I',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.periods.destroy', $period));

        $response->assertRedirect(route('admin.periods.index'));
        $response->assertSessionHas('success');

        // Verify record is NOT deleted from database
        $this->assertDatabaseHas('rba_periods', [
            'id' => $period->id,
            'name' => 'Pergeseran Anggaran I',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_reactivate_period(): void
    {
        $period = RbaPeriod::create([
            'name' => 'Pergeseran Anggaran II',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.periods.destroy', $period));

        $response->assertRedirect(route('admin.periods.index'));
        $response->assertSessionHas('success');

        // Verify record is reactivated
        $this->assertDatabaseHas('rba_periods', [
            'id' => $period->id,
            'name' => 'Pergeseran Anggaran II',
            'is_active' => true,
        ]);
    }

    public function test_period_deactivation_is_recorded_in_activity_log(): void
    {
        $period = RbaPeriod::create([
            'name' => 'Perubahan APBD 2026',
            'is_active' => true,
        ]);

        // 1. Deactivate
        $this->actingAs($this->admin)->delete(route('admin.periods.destroy', $period));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'model_type' => RbaPeriod::class,
            'model_id' => $period->id,
            'action' => 'updated',
        ]);

        $deactivateLog = ActivityLog::where('model_type', RbaPeriod::class)
            ->where('model_id', $period->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($deactivateLog);
        $this->assertStringContainsString('menonaktifkan Periode RBA: "Perubahan APBD 2026"', $deactivateLog->description);

        // 2. Reactivate
        $this->actingAs($this->admin)->delete(route('admin.periods.destroy', $period));

        $reactivateLog = ActivityLog::where('model_type', RbaPeriod::class)
            ->where('model_id', $period->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($reactivateLog);
        $this->assertStringContainsString('mengaktifkan Periode RBA: "Perubahan APBD 2026"', $reactivateLog->description);
    }

    public function test_inactive_period_cannot_be_selected_when_creating_new_rba_header(): void
    {
        $activePeriod = RbaPeriod::create([
            'name' => 'Murni Aktif 2026',
            'is_active' => true,
        ]);

        $inactivePeriod = RbaPeriod::create([
            'name' => 'Murni Nonaktif 2025',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.headers.create'));

        $response->assertStatus(200);
        $response->assertSee('Murni Aktif 2026');
        $response->assertDontSee('Murni Nonaktif 2025');
    }

    public function test_admin_can_create_and_update_period_with_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.periods.store'), [
            'name' => 'Periode Baru Nonaktif',
            'is_active' => 0,
        ]);

        $response->assertRedirect(route('admin.periods.index'));
        $this->assertDatabaseHas('rba_periods', [
            'name' => 'Periode Baru Nonaktif',
            'is_active' => false,
        ]);

        $period = RbaPeriod::where('name', 'Periode Baru Nonaktif')->first();

        $responseUpdate = $this->actingAs($this->admin)->put(route('admin.periods.update', $period), [
            'name' => 'Periode Baru Diaktifkan',
            'is_active' => 1,
        ]);

        $responseUpdate->assertRedirect(route('admin.periods.index'));
        $this->assertDatabaseHas('rba_periods', [
            'id' => $period->id,
            'name' => 'Periode Baru Diaktifkan',
            'is_active' => true,
        ]);
    }
}
