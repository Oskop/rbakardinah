<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\KelompokBelanja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelompokBelanjaTest extends TestCase
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

    public function test_admin_can_create_kelompok_belanja_with_kode_and_name(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->post(route('admin.kelompok-belanja.store'), [
                'kode' => '5.1.04',
                'name' => 'Belanja Lain-lain',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.kelompok-belanja.index'));

        $this->assertDatabaseHas('kelompok_belanjas', [
            'kode' => '5.1.04',
            'name' => 'Belanja Lain-lain',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_kelompok_belanja_kode_and_name(): void
    {
        $group = KelompokBelanja::create(['kode' => '5.1.04', 'name' => 'Belanja Temp', 'is_active' => true]);

        $response = $this
            ->actingAs($this->admin)
            ->put(route('admin.kelompok-belanja.update', $group), [
                'kode' => '5.1.05',
                'name' => 'Belanja Lain-lain Updated',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.kelompok-belanja.index'));

        $this->assertDatabaseHas('kelompok_belanjas', [
            'id' => $group->id,
            'kode' => '5.1.05',
            'name' => 'Belanja Lain-lain Updated',
        ]);
    }

    public function test_admin_can_view_kelompok_belanja_index_with_status_and_filters(): void
    {
        $activeGroup = KelompokBelanja::create(['kode' => '5.1.01', 'name' => 'Belanja Operasi Aktif', 'is_active' => true]);
        $inactiveGroup = KelompokBelanja::create(['kode' => '5.1.02', 'name' => 'Belanja Modal Nonaktif', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->get(route('admin.kelompok-belanja.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Kelompok Belanja');
        $response->assertSee('Status');
        $response->assertSee('Rekening Terdaftar');
        $response->assertSee('Belanja Operasi Aktif');
        $response->assertSee('Belanja Modal Nonaktif');
        $response->assertSee('Active');
        $response->assertSee('Inactive');
        $response->assertSee('Nonaktifkan');
        $response->assertSee('Aktifkan');

        // Verify DataTables & Filter Toolbar UI
        $response->assertSee('Filter Kolom Kelompok Belanja');
        $response->assertSee('id="kelompok-belanja-table"', false);
        $response->assertSee('id="filter-status"', false);
        $response->assertSee('id="filter-accounts"', false);
        $response->assertSee('id="btn-reset-filters"', false);
        $response->assertSee('data-search="Active"', false);
        $response->assertSee('data-search="Inactive"', false);

        // Verify there is no "Delete" button
        $response->assertDontSee('Delete');
    }

    public function test_admin_can_deactivate_kelompok_belanja_instead_of_deleting(): void
    {
        $group = KelompokBelanja::create(['kode' => '5.1.03', 'name' => 'Belanja Tidak Terduga', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->delete(route('admin.kelompok-belanja.destroy', $group));

        $response->assertRedirect(route('admin.kelompok-belanja.index'));
        $response->assertSessionHas('success');

        // Verify record is NOT deleted from database
        $this->assertDatabaseHas('kelompok_belanjas', [
            'id' => $group->id,
            'kode' => '5.1.03',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_reactivate_kelompok_belanja(): void
    {
        $group = KelompokBelanja::create(['kode' => '5.1.04', 'name' => 'Belanja Transfer', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->delete(route('admin.kelompok-belanja.destroy', $group));

        $response->assertRedirect(route('admin.kelompok-belanja.index'));
        $response->assertSessionHas('success');

        // Verify record is reactivated
        $this->assertDatabaseHas('kelompok_belanjas', [
            'id' => $group->id,
            'kode' => '5.1.04',
            'is_active' => true,
        ]);
    }

    public function test_kelompok_belanja_deactivation_is_recorded_in_activity_log(): void
    {
        $group = KelompokBelanja::create(['kode' => '5.1.05', 'name' => 'Belanja Hibah', 'is_active' => true]);

        // 1. Deactivate
        $this->actingAs($this->admin)->delete(route('admin.kelompok-belanja.destroy', $group));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'model_type' => KelompokBelanja::class,
            'model_id' => $group->id,
            'action' => 'updated',
        ]);

        $deactivateLog = ActivityLog::where('model_type', KelompokBelanja::class)
            ->where('model_id' , $group->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($deactivateLog);
        $this->assertStringContainsString('menonaktifkan Kelompok Belanja: "5.1.05 - Belanja Hibah"', $deactivateLog->description);

        // 2. Reactivate
        $this->actingAs($this->admin)->delete(route('admin.kelompok-belanja.destroy', $group));

        $reactivateLog = ActivityLog::where('model_type', KelompokBelanja::class)
            ->where('model_id', $group->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($reactivateLog);
        $this->assertStringContainsString('mengaktifkan Kelompok Belanja: "5.1.05 - Belanja Hibah"', $reactivateLog->description);
    }

    public function test_inactive_kelompok_belanja_cannot_be_selected_for_new_account_code(): void
    {
        $activeGroup = KelompokBelanja::create(['kode' => '5.1.06', 'name' => 'Kelompok Aktif Valid', 'is_active' => true]);
        $inactiveGroup = KelompokBelanja::create(['kode' => '5.1.07', 'name' => 'Kelompok Nonaktif Tersembunyi', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->get(route('admin.account-codes.create'));

        $response->assertStatus(200);
        $response->assertSee('Kelompok Aktif Valid');
        $response->assertDontSee('Kelompok Nonaktif Tersembunyi');
    }
}
