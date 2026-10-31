<?php

namespace Tests\Feature\General;

use App\Models\MasterBarang;
use App\Models\RkbmdItem;
use App\Models\RkbmdSubmission;
use App\Models\SubUnit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RkbmdReadOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Unit $unitA;
    protected Unit $unitB;
    protected SubUnit $subUnitA;
    protected SubUnit $subUnitB;
    protected User $admin;
    protected User $supervisorA;
    protected User $operatorPemohonA;
    protected User $operatorPengusulA;
    protected User $operatorPemohonB;
    protected User $operatorPengusulB;
    protected MasterBarang $barang;
    protected RkbmdSubmission $subFromAtoB;
    protected RkbmdSubmission $subFromBtoA;
    protected RkbmdSubmission $subInternalB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unitA = Unit::create(['name' => 'Unit Pelayanan Medis', 'code' => 'UPM']);
        $this->unitB = Unit::create(['name' => 'Bagian Keuangan & Sarpras', 'code' => 'BKS']);

        $this->subUnitA = SubUnit::create([
            'unit_id' => $this->unitA->id,
            'name' => 'Instalasi Rawat Inap',
            'code' => 'IRNA',
        ]);

        $this->subUnitB = SubUnit::create([
            'unit_id' => $this->unitB->id,
            'name' => 'Sub Bagian Rumah Tangga',
            'code' => 'RT',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Administrator Rumah Sakit',
            'email' => 'admin_rkbmd@hospital.com',
            'role' => 'Administrator',
            'unit_id' => null,
        ]);

        $this->supervisorA = User::factory()->create([
            'name' => 'Supervisor Unit Medis',
            'email' => 'spv_a@hospital.com',
            'role' => 'Supervisor',
            'unit_id' => $this->unitA->id,
        ]);

        $this->operatorPemohonA = User::factory()->create([
            'name' => 'Operator Pemohon IRNA',
            'email' => 'op_pemohon_a@hospital.com',
            'role' => 'Operator',
            'unit_id' => $this->unitA->id,
            'sub_unit_id' => $this->subUnitA->id,
            'can_propose' => false,
        ]);

        $this->operatorPengusulA = User::factory()->create([
            'name' => 'Operator Pengusul Unit Medis',
            'email' => 'op_pengusul_a@hospital.com',
            'role' => 'Operator',
            'unit_id' => $this->unitA->id,
            'sub_unit_id' => $this->subUnitA->id,
            'can_propose' => true,
        ]);

        $this->operatorPemohonB = User::factory()->create([
            'name' => 'Operator Pemohon RT',
            'email' => 'op_pemohon_b@hospital.com',
            'role' => 'Operator',
            'unit_id' => $this->unitB->id,
            'sub_unit_id' => $this->subUnitB->id,
            'can_propose' => false,
        ]);

        $this->operatorPengusulB = User::factory()->create([
            'name' => 'Operator Pengusul Bagian Sarpras',
            'email' => 'op_pengusul_b@hospital.com',
            'role' => 'Operator',
            'unit_id' => $this->unitB->id,
            'sub_unit_id' => $this->subUnitB->id,
            'can_propose' => true,
        ]);

        $this->barang = MasterBarang::create([
            'kode_barang' => '1.3.2.05.02.04.001',
            'nama_barang' => 'Air Conditioner (AC) Split Wall 2 PK',
            'satuan' => 'Unit',
            'is_active' => true,
        ]);

        // 1. Permohonan dari Sub-Unit A diajukan ke Operator Pengusul B
        $this->subFromAtoB = RkbmdSubmission::create([
            'nomor_permohonan' => 'RKBMD/2026/10/0001',
            'year' => 2026,
            'user_id' => $this->operatorPemohonA->id,
            'unit_id' => $this->unitA->id,
            'sub_unit_id' => $this->subUnitA->id,
            'target_operator_id' => $this->operatorPengusulB->id,
            'original_operator_id' => $this->operatorPengusulB->id,
            'title' => 'Pengadaan AC Ruang Rawat Inap',
            'notes' => 'Permohonan penggantian AC rusak di paviliun',
            'status' => 'Diajukan',
            'created_by' => $this->operatorPemohonA->id,
            'updated_by' => $this->operatorPemohonA->id,
        ]);

        RkbmdItem::create([
            'rkbmd_submission_id' => $this->subFromAtoB->id,
            'master_barang_id' => $this->barang->id,
            'volume' => 2,
            'satuan' => 'Unit',
            'spesifikasi' => 'AC Inverter hemat energi',
            'created_by' => $this->operatorPemohonA->id,
            'updated_by' => $this->operatorPemohonA->id,
        ]);

        // 2. Permohonan dari Sub-Unit B ditujukan ke Operator Pengusul A
        $this->subFromBtoA = RkbmdSubmission::create([
            'nomor_permohonan' => 'RKBMD/2026/10/0002',
            'year' => 2026,
            'user_id' => $this->operatorPemohonB->id,
            'unit_id' => $this->unitB->id,
            'sub_unit_id' => $this->subUnitB->id,
            'target_operator_id' => $this->operatorPengusulA->id,
            'original_operator_id' => $this->operatorPengusulA->id,
            'title' => 'Usulan Alkes Dukungan Rawat Medis',
            'notes' => 'Pengadaan koordinasi antar-unit',
            'status' => 'Diajukan',
            'created_by' => $this->operatorPemohonB->id,
            'updated_by' => $this->operatorPemohonB->id,
        ]);

        // 3. Permohonan internal Unit B (B ke B, tidak ada kaitan dengan Unit A)
        $this->subInternalB = RkbmdSubmission::create([
            'nomor_permohonan' => 'RKBMD/2026/10/0003',
            'year' => 2026,
            'user_id' => $this->operatorPemohonB->id,
            'unit_id' => $this->unitB->id,
            'sub_unit_id' => $this->subUnitB->id,
            'target_operator_id' => $this->operatorPengusulB->id,
            'original_operator_id' => $this->operatorPengusulB->id,
            'title' => 'Pengadaan ATK Khusus Keuangan',
            'notes' => 'Kebutuhan rutin internal',
            'status' => 'Diajukan',
            'created_by' => $this->operatorPemohonB->id,
            'updated_by' => $this->operatorPemohonB->id,
        ]);
    }

    public function test_admin_can_view_rkbmd_index_and_see_all_hospital_submissions(): void
    {
        $response = $this->actingAs($this->admin)->get(route('rkbmd.index'));

        $response->assertOk();
        $response->assertSee('Daftar Seluruh Permohonan RKBMD (Rumah Sakit)');
        $response->assertSee('Mode Peninjauan (Read-Only)');
        $response->assertDontSee('Buat Permohonan Baru');

        // Admin sees all 3 submissions across units
        $response->assertSee('RKBMD/2026/10/0001');
        $response->assertSee('RKBMD/2026/10/0002');
        $response->assertSee('RKBMD/2026/10/0003');
    }

    public function test_supervisor_can_view_rkbmd_index_and_see_submissions_originated_from_and_targeted_to_own_unit(): void
    {
        $response = $this->actingAs($this->supervisorA)->get(route('rkbmd.index'));

        $response->assertOk();
        $response->assertSee('Daftar Permohonan RKBMD Terkait Unit');
        $response->assertSee('terkait Unit ' . $this->unitA->name);
        $response->assertSee('Mode Peninjauan (Read-Only)');
        $response->assertDontSee('Buat Permohonan Baru');

        // 1. Must see submission originated from Unit A
        $response->assertSee('RKBMD/2026/10/0001');
        $response->assertSee('Diajukan dari Sub-Unit Kita');

        // 2. Must see submission targeted to Operator in Unit A
        $response->assertSee('RKBMD/2026/10/0002');
        $response->assertSee('Ditujukan ke Operator Kita');

        // 3. Must NOT see unrelated submission (Unit B to Unit B)
        $response->assertDontSee('RKBMD/2026/10/0003');
    }

    public function test_supervisor_cannot_view_unrelated_submission_in_show(): void
    {
        // Supervisor A attempts to view submission 3 (Unit B -> Unit B)
        $response = $this->actingAs($this->supervisorA)->get(route('rkbmd.show', $this->subInternalB));

        $response->assertForbidden();
    }

    public function test_admin_and_supervisor_can_view_related_submission_show_page_read_only(): void
    {
        // 1. Admin views detail
        $adminResp = $this->actingAs($this->admin)->get(route('rkbmd.show', $this->subFromAtoB));
        $adminResp->assertOk();
        $adminResp->assertSee('Mode Peninjauan (Read-Only)');
        $adminResp->assertSee('READ-ONLY');
        $adminResp->assertDontSee('Edit Permohonan');
        $adminResp->assertDontSee('Tanggapi / Berikan Balasan');
        $adminResp->assertDontSee('Alihkan ke Pengusul Lain');

        // 2. Supervisor A views detail of subFromAtoB (originated from their unit)
        $spvResp = $this->actingAs($this->supervisorA)->get(route('rkbmd.show', $this->subFromAtoB));
        $spvResp->assertOk();
        $spvResp->assertSee('Mode Peninjauan (Read-Only)');
        $spvResp->assertSee('READ-ONLY');
        $spvResp->assertDontSee('Edit Permohonan');
        $spvResp->assertDontSee('Tanggapi / Berikan Balasan');
        $spvResp->assertDontSee('Alihkan ke Pengusul Lain');
    }

    public function test_admin_and_supervisor_cannot_access_create_form(): void
    {
        $this->actingAs($this->admin)->get(route('operator.rkbmd.create'))
            ->assertForbidden();

        $this->actingAs($this->supervisorA)->get(route('operator.rkbmd.create'))
            ->assertForbidden();
    }

    public function test_admin_and_supervisor_cannot_submit_store_request(): void
    {
        $payload = [
            'target_operator_id' => $this->operatorPengusulB->id,
            'title' => 'Pengajuan Tidak Sah',
            'year' => 2026,
            'items' => [
                [
                    'master_barang_id' => $this->barang->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ]
            ],
        ];

        $this->actingAs($this->admin)->post(route('operator.rkbmd.store'), $payload)
            ->assertForbidden();

        $this->actingAs($this->supervisorA)->post(route('operator.rkbmd.store'), $payload)
            ->assertForbidden();
    }

    public function test_admin_and_supervisor_cannot_access_edit_form_or_submit_update(): void
    {
        $this->actingAs($this->admin)->get(route('operator.rkbmd.edit', $this->subFromAtoB))
            ->assertForbidden();

        $this->actingAs($this->supervisorA)->get(route('operator.rkbmd.edit', $this->subFromAtoB))
            ->assertForbidden();

        $this->actingAs($this->admin)->put(route('operator.rkbmd.update', $this->subFromAtoB), ['title' => 'Hack'])
            ->assertForbidden();

        $this->actingAs($this->supervisorA)->put(route('operator.rkbmd.update', $this->subFromAtoB), ['title' => 'Hack'])
            ->assertForbidden();
    }

    public function test_admin_and_supervisor_cannot_reply_or_forward_submission(): void
    {
        $replyPayload = [
            'status' => 'Dipenuhi',
            'reply_notes' => 'Balasan tidak sah',
            'items' => [
                $this->subFromAtoB->items->first()->id => [
                    'volume_disetujui' => 2,
                    'status_item' => 'Dipenuhi',
                ]
            ]
        ];

        $this->actingAs($this->admin)->post(route('operator.rkbmd.reply', $this->subFromAtoB), $replyPayload)
            ->assertForbidden();

        $this->actingAs($this->supervisorA)->post(route('operator.rkbmd.reply', $this->subFromAtoB), $replyPayload)
            ->assertForbidden();

        $forwardPayload = [
            'new_target_operator_id' => $this->operatorPengusulA->id,
            'forward_reason' => 'Pengalihan tidak sah',
        ];

        $this->actingAs($this->admin)->post(route('operator.rkbmd.forward', $this->subFromAtoB), $forwardPayload)
            ->assertForbidden();

        $this->actingAs($this->supervisorA)->post(route('operator.rkbmd.forward', $this->subFromAtoB), $forwardPayload)
            ->assertForbidden();
    }

    public function test_operator_retains_full_operational_access_without_regression(): void
    {
        // 1. Operator accesses index
        $response = $this->actingAs($this->operatorPemohonA)->get(route('rkbmd.index'));
        $response->assertOk();
        $response->assertSee('Buat Permohonan Baru');
        $response->assertSee('Permohonan Saya');

        // 2. Operator accesses show
        $responseShow = $this->actingAs($this->operatorPemohonA)->get(route('rkbmd.show', $this->subFromAtoB));
        $responseShow->assertOk();
        $responseShow->assertSee('Edit Permohonan'); // can edit because it's status Diajukan and applicant

        // 3. Backward-compatible operator.rkbmd.index and show still work
        $this->actingAs($this->operatorPemohonA)->get(route('operator.rkbmd.index'))->assertOk();
        $this->actingAs($this->operatorPemohonA)->get(route('operator.rkbmd.show', $this->subFromAtoB))->assertOk();
    }
}
