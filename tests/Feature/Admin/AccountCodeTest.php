<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\AccountCode;
use App\Models\KelompokBelanja;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use App\Models\RbaSubmission;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected KelompokBelanja $kelompok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Administrator',
            'is_active' => true,
        ]);

        $this->kelompok = KelompokBelanja::create([
            'kode' => '5.1.02',
            'name' => 'Belanja Barang dan Jasa',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_account_codes_index_with_status_and_filters(): void
    {
        $activeCode = AccountCode::create([
            'kelompok_belanja_id' => $this->kelompok->id,
            'code' => '5.1.02.01.01.0001',
            'name' => 'Belanja Alat Tulis Kantor',
            'is_active' => true,
        ]);

        $inactiveCode = AccountCode::create([
            'kelompok_belanja_id' => $this->kelompok->id,
            'code' => '5.1.02.01.01.0002',
            'name' => 'Belanja Kertas Cetak Nonaktif',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.account-codes.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Nomor Rekening');
        $response->assertSee('Status');
        $response->assertSee('Kelompok Belanja');
        $response->assertSee('Belanja Alat Tulis Kantor');
        $response->assertSee('Belanja Kertas Cetak Nonaktif');
        $response->assertSee('Active');
        $response->assertSee('Inactive');
        $response->assertSee('Nonaktifkan');
        $response->assertSee('Aktifkan');

        // Verify DataTables & Filter Toolbar UI
        $response->assertSee('Filter Kolom Nomor Rekening');
        $response->assertSee('id="account-codes-table"', false);
        $response->assertSee('id="filter-status"', false);
        $response->assertSee('id="filter-kelompok"', false);
        $response->assertSee('id="btn-reset-filters"', false);
        $response->assertSee('data-search="Active"', false);
        $response->assertSee('data-search="Inactive"', false);

        // Verify there is no "Delete" button
        $response->assertDontSee('Delete');
    }

    public function test_admin_can_deactivate_account_code_instead_of_deleting(): void
    {
        $code = AccountCode::create([
            'kelompok_belanja_id' => $this->kelompok->id,
            'code' => '5.1.02.01.01.0003',
            'name' => 'Belanja Bahan Kimia',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.account-codes.destroy', $code));

        $response->assertRedirect(route('admin.account-codes.index'));
        $response->assertSessionHas('success');

        // Verify record is NOT deleted from database
        $this->assertDatabaseHas('account_codes', [
            'id' => $code->id,
            'code' => '5.1.02.01.01.0003',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_reactivate_account_code(): void
    {
        $code = AccountCode::create([
            'kelompok_belanja_id' => $this->kelompok->id,
            'code' => '5.1.02.01.01.0004',
            'name' => 'Belanja Pemeliharaan Genset',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.account-codes.destroy', $code));

        $response->assertRedirect(route('admin.account-codes.index'));
        $response->assertSessionHas('success');

        // Verify record is reactivated
        $this->assertDatabaseHas('account_codes', [
            'id' => $code->id,
            'code' => '5.1.02.01.01.0004',
            'is_active' => true,
        ]);
    }

    public function test_account_code_deactivation_is_recorded_in_activity_log(): void
    {
        $code = AccountCode::create([
            'kelompok_belanja_id' => $this->kelompok->id,
            'code' => '5.1.02.01.01.0005',
            'name' => 'Belanja Logistik Farmasi',
            'is_active' => true,
        ]);

        // 1. Deactivate
        $this->actingAs($this->admin)->delete(route('admin.account-codes.destroy', $code));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'model_type' => AccountCode::class,
            'model_id' => $code->id,
            'action' => 'updated',
        ]);

        $deactivateLog = ActivityLog::where('model_type', AccountCode::class)
            ->where('model_id', $code->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($deactivateLog);
        $this->assertStringContainsString('menonaktifkan Nomor Rekening: "5.1.02.01.01.0005 - Belanja Logistik Farmasi"', $deactivateLog->description);

        // 2. Reactivate
        $this->actingAs($this->admin)->delete(route('admin.account-codes.destroy', $code));

        $reactivateLog = ActivityLog::where('model_type', AccountCode::class)
            ->where('model_id', $code->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($reactivateLog);
        $this->assertStringContainsString('mengaktifkan Nomor Rekening: "5.1.02.01.01.0005 - Belanja Logistik Farmasi"', $reactivateLog->description);
    }

    public function test_inactive_account_code_cannot_be_selected_by_operator_for_new_rba_detail(): void
    {
        $unit = Unit::create(['name' => 'Instalasi Farmasi', 'code' => 'IFAR', 'is_active' => true]);
        $operator = User::factory()->create([
            'role' => 'Operator',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'period_id' => $period->id,
            'year' => 2026,
            'admin_id' => $this->admin->id,
            'status_global' => 'Draft',
        ]);
        $submission = RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $unit->id,
            'status_submission' => 'Draft',
            'background' => 'Latar Belakang Operasional Farmasi',
        ]);

        \App\Models\RbaSubmissionOperatorBackground::create([
            'rba_submission_id' => $submission->id,
            'user_id' => $operator->id,
            'background' => 'Latar Belakang Operator Farmasi',
        ]);

        $activeCode = AccountCode::create([
            'kelompok_belanja_id' => $this->kelompok->id,
            'code' => '5.1.02.01.01.0006',
            'name' => 'Belanja Obat Aktif',
            'is_active' => true,
        ]);

        $inactiveCode = AccountCode::create([
            'kelompok_belanja_id' => $this->kelompok->id,
            'code' => '5.1.02.01.01.0007',
            'name' => 'Belanja Obat Kadaluarsa Nonaktif',
            'is_active' => false,
        ]);

        $response = $this->actingAs($operator)->get(route('operator.details.create', ['submission_id' => $submission->id]));

        $response->assertStatus(200);
        $response->assertSee('Belanja Obat Aktif');
        $response->assertDontSee('Belanja Obat Kadaluarsa Nonaktif');
    }

    public function test_admin_can_create_and_update_account_code_with_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.account-codes.store'), [
            'kelompok_belanja_id' => $this->kelompok->id,
            'code' => '5.1.02.01.01.0008',
            'name' => 'Belanja Bahan Makanan',
            'is_active' => 0,
        ]);

        $response->assertRedirect(route('admin.account-codes.index'));
        $this->assertDatabaseHas('account_codes', [
            'code' => '5.1.02.01.01.0008',
            'is_active' => false,
        ]);

        $code = AccountCode::where('code', '5.1.02.01.01.0008')->first();

        $responseUpdate = $this->actingAs($this->admin)->put(route('admin.account-codes.update', $code), [
            'kelompok_belanja_id' => $this->kelompok->id,
            'code' => '5.1.02.01.01.0008',
            'name' => 'Belanja Bahan Makanan Updated',
            'is_active' => 1,
        ]);

        $responseUpdate->assertRedirect(route('admin.account-codes.index'));
        $this->assertDatabaseHas('account_codes', [
            'id' => $code->id,
            'name' => 'Belanja Bahan Makanan Updated',
            'is_active' => true,
        ]);
    }
}
