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

    public function test_admin_can_set_pagu_global()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.headers.pagu.store', $this->header), [
            'pagus' => [
                $this->accountCode->id => 1000000
            ]
        ]);

        $response->assertRedirect(route('admin.headers.show', $this->header));
        $this->assertDatabaseHas('rba_account_pagus', [
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 1000000
        ]);
    }

    public function test_setting_pagu_locks_operator_nominal_edits()
    {
        // 1. Set Pagu
        $this->header->accountPagus()->create([
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 500000
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

        // Should be forbidden by Policy
        $response->assertStatus(403);
    }
}
