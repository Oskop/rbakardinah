<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Unit;
use App\Models\RbaPeriod;
use App\Models\RbaHeader;
use App\Models\AccountCode;
use App\Models\RbaSubmission;
use App\Models\RbaDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaguTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $operator;
    protected $header;
    protected $accountCode;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = Unit::create(['code' => 'U01', 'name' => 'Unit Test']);
        $this->admin = User::factory()->create(['role' => 'Administrator']);
        $this->operator = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit->id]);

        $period = RbaPeriod::create(['name' => 'Murni']);
        $this->header = RbaHeader::create([
            'period_id' => $period->id,
            'year' => 2026,
            'admin_id' => $this->admin->id,
            'status_global' => 'Active'
        ]);

        $group = \App\Models\KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Test Group']);
        $this->accountCode = AccountCode::create([
            'kelompok_belanja_id' => $group->id,
            'code' => '5.1.01',
            'name' => 'Belanja Gaji'
        ]);
    }

    public function test_admin_can_set_pagu_per_account_code()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.headers.pagu.store', $this->header), [
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 1000000
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rba_account_pagus', [
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 1000000
        ]);
    }

    public function test_admin_can_set_pagu_zero_and_it_is_considered_established()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.headers.pagu.store', $this->header), [
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 0
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rba_account_pagus', [
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 0
        ]);
    }

    public function test_setting_pagu_zero_locks_operator_from_creating_detail()
    {
        // 1. Set Pagu = 0
        $this->header->accountPagus()->create([
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 0
        ]);

        // 2. Try to create detail as Operator for this account
        $submission = RbaSubmission::create([
            'rba_header_id' => $this->header->id,
            'unit_id' => $this->operator->unit_id,
            'status_submission' => 'Draft',
            'background' => 'Latar belakang unit testing'
        ]);

        $response = $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Test',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 100,
            'attachment' => \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 100)
        ]);

        // Should be forbidden by Policy even when nominal_pagu is 0
        $response->assertStatus(403);
    }

    public function test_admin_cannot_set_pagu_if_operator_details_not_validated_by_supervisor()
    {
        $supervisor = User::factory()->create([
            'name' => 'Budi Santoso',
            'role' => 'Supervisor',
            'unit_id' => $this->operator->unit_id
        ]);

        $submission = RbaSubmission::create([
            'rba_header_id' => $this->header->id,
            'unit_id' => $this->operator->unit_id,
            'status_submission' => 'Draft',
            'background' => 'Latar belakang unit testing'
        ]);

        $detail = RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Pengadaan Obat Paracetamol',
            'volume' => 10,
            'satuan' => 'Box',
            'harga_satuan' => 50000,
            'nominal_request' => 500000,
            'is_submitted' => true,
            'is_validated' => false,
            'created_by' => $this->operator->id
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.headers.pagu.store', $this->header), [
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 1000000
        ]);

        $response->assertSessionHas('error');
        $errorMessage = session('error');
        $this->assertStringContainsString($this->operator->name, $errorMessage);
        $this->assertStringContainsString($supervisor->name, $errorMessage);
        $this->assertStringContainsString('Pengadaan Obat Paracetamol', $errorMessage);

        $this->assertDatabaseMissing('rba_account_pagus', [
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->accountCode->id,
        ]);
    }

    public function test_admin_can_set_pagu_when_all_operator_details_are_validated()
    {
        $supervisor = User::factory()->create([
            'name' => 'Budi Santoso',
            'role' => 'Supervisor',
            'unit_id' => $this->operator->unit_id
        ]);

        $submission = RbaSubmission::create([
            'rba_header_id' => $this->header->id,
            'unit_id' => $this->operator->unit_id,
            'status_submission' => 'Validated',
            'background' => 'Latar belakang unit testing'
        ]);

        $detail = RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Pengadaan Obat Paracetamol',
            'volume' => 10,
            'satuan' => 'Box',
            'harga_satuan' => 50000,
            'nominal_request' => 500000,
            'is_submitted' => true,
            'is_validated' => true,
            'validated_at' => now(),
            'validated_by' => $supervisor->id,
            'created_by' => $this->operator->id
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.headers.pagu.store', $this->header), [
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 1000000
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('rba_account_pagus', [
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 1000000
        ]);
    }

    public function test_admin_can_cancel_pagu_for_account_code()
    {
        $this->header->accountPagus()->create([
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 500000
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.headers.pagu.destroy', [$this->header, $this->accountCode]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('rba_account_pagus', [
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->accountCode->id,
        ]);
    }

    public function test_admin_cannot_set_pagu_after_operator_edits_validated_detail_until_revalidated()
    {
        $supervisor = User::factory()->create([
            'name' => 'Budi Santoso',
            'role' => 'Supervisor',
            'unit_id' => $this->operator->unit_id
        ]);

        $submission = RbaSubmission::create([
            'rba_header_id' => $this->header->id,
            'unit_id' => $this->operator->unit_id,
            'status_submission' => 'Validated',
            'background' => 'Latar belakang unit testing'
        ]);

        // 1. Initial validated detail
        $detail = RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Pengadaan Obat Paracetamol',
            'volume' => 10,
            'satuan' => 'Box',
            'harga_satuan' => 50000,
            'nominal_request' => 500000,
            'is_submitted' => true,
            'is_validated' => true,
            'validated_at' => now(),
            'validated_by' => $supervisor->id,
            'created_by' => $this->operator->id
        ]);

        // 2. Operator edits the detail
        $this->actingAs($this->operator)->put(route('operator.details.update', $detail), [
            'account_code_id' => $this->accountCode->id,
            'description' => 'Pengadaan Obat Paracetamol Extra',
            'volume' => 15,
            'satuan' => 'Box',
            'harga_satuan' => 60000,
        ]);

        $this->assertFalse($detail->fresh()->is_validated);

        // 3. Admin attempts to set pagu -> should be rejected because detail is back to unvalidated
        $response = $this->actingAs($this->admin)
            ->from(route('admin.headers.pagu.index', $this->header))
            ->post(route('admin.headers.pagu.store', $this->header), [
                'account_code_id' => $this->accountCode->id,
                'nominal_pagu' => 1000000
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('rba_account_pagus', [
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->accountCode->id,
        ]);

        // 4. Supervisor re-validates the edited detail
        $detail->fresh()->update([
            'is_validated' => true,
            'validated_at' => now(),
            'validated_by' => $supervisor->id,
        ]);

        // 5. Admin sets pagu -> should succeed
        $responseSuccess = $this->actingAs($this->admin)
            ->from(route('admin.headers.pagu.index', $this->header))
            ->post(route('admin.headers.pagu.store', $this->header), [
                'account_code_id' => $this->accountCode->id,
                'nominal_pagu' => 1000000
            ]);

        $responseSuccess->assertSessionHas('success');
        $this->assertDatabaseHas('rba_account_pagus', [
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 1000000
        ]);
    }

    public function test_admin_can_save_pagu_via_ajax_without_page_reload()
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.headers.pagu.store', $this->header), [
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 25000000
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'account_code_id' => $this->accountCode->id,
                'nominal_pagu' => 25000000,
                'nominal_formatted' => '25.000.000',
            ]
        ]);

        $this->assertDatabaseHas('rba_account_pagus', [
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 25000000
        ]);
    }

    public function test_admin_can_delete_pagu_via_ajax_without_page_reload()
    {
        $this->header->accountPagus()->create([
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 5000000
        ]);

        $response = $this->actingAs($this->admin)->deleteJson(route('admin.headers.pagu.destroy', [$this->header, $this->accountCode]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'account_code_id' => $this->accountCode->id,
            ]
        ]);

        $this->assertDatabaseMissing('rba_account_pagus', [
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->accountCode->id,
        ]);
    }

    public function test_ajax_save_pagu_fails_with_422_when_unvalidated_details_exist()
    {
        $submission = RbaSubmission::create([
            'rba_header_id' => $this->header->id,
            'unit_id' => $this->operator->unit_id,
            'status_submission' => 'Draft',
            'background' => 'Testing Unvalidated'
        ]);

        RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Unvalidated Detail Item',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 100000,
            'nominal_request' => 100000,
            'is_submitted' => true,
            'is_validated' => false,
            'created_by' => $this->operator->id
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.headers.pagu.store', $this->header), [
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 1000000
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false
        ]);
        $this->assertStringContainsString('Unvalidated Detail Item', $response->json('message'));
    }
}
