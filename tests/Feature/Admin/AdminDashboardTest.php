<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Unit;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard_and_see_rba_list()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Administrator Dashboard - SIPAKAR RBA');
        $response->assertSee('Daftar RBA Historis');
        $response->assertSee('Users');
        $response->assertSee('Units');
    }

    public function test_admin_can_preview_print_report_with_unit_and_operator_filters()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $unit1 = \App\Models\Unit::create(['code' => 'U01', 'name' => 'Unit Rawat Inap']);
        $unit2 = \App\Models\Unit::create(['code' => 'U02', 'name' => 'Unit Farmasi']);

        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $sub1 = \App\Models\RbaSubmission::create(['rba_header_id' => $header->id, 'unit_id' => $unit1->id]);
        $sub2 = \App\Models\RbaSubmission::create(['rba_header_id' => $header->id, 'unit_id' => $unit2->id]);

        $group = \App\Models\KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Group']);
        $ac = \App\Models\AccountCode::create(['kelompok_belanja_id' => $group->id, 'code' => '5.1.01', 'name' => 'Belanja Gaji']);

        $op1 = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit1->id, 'name' => 'Op Inap']);
        $op2 = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit2->id, 'name' => 'Op Farmasi']);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $sub1->id,
            'account_code_id' => $ac->id,
            'description' => 'Item Inap',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 1000,
            'nominal_request' => 1000,
            'created_by' => $op1->id
        ]);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $sub2->id,
            'account_code_id' => $ac->id,
            'description' => 'Item Farmasi',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 2000,
            'nominal_request' => 2000,
            'created_by' => $op2->id
        ]);

        // 1. Test Admin Print All
        $resAll = $this->actingAs($admin)->get(route('admin.headers.print-preview', ['header' => $header->id]));
        $resAll->assertStatus(200);
        $resAll->assertSee('Item Inap');
        $resAll->assertSee('Item Farmasi');
        $resAll->assertSee('Seluruh Unit Kerja');

        // 2. Test Admin Print Filter Unit 1
        $resUnit1 = $this->actingAs($admin)->get(route('admin.headers.print-preview', [
            'header' => $header->id,
            'unit_ids' => [$unit1->id]
        ]));
        $resUnit1->assertStatus(200);
        $resUnit1->assertSee('Item Inap');
        $resUnit1->assertDontSee('Item Farmasi');
        $resUnit1->assertSee('Unit Rawat Inap');

        // 3. Test Admin Print Filter Operator 2
        $resOp2 = $this->actingAs($admin)->get(route('admin.headers.print-preview', [
            'header' => $header->id,
            'operator_ids' => [$op2->id]
        ]));
        $resOp2->assertStatus(200);
        $resOp2->assertDontSee('Item Inap');
        $resOp2->assertSee('Item Farmasi');
        $resOp2->assertSee('Op Farmasi');
    }

    public function test_admin_can_preview_rba_final_print_report_with_pagu_and_unit_operator_filters()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $unit1 = \App\Models\Unit::create(['code' => 'U01', 'name' => 'Unit Rawat Inap']);
        $unit2 = \App\Models\Unit::create(['code' => 'U02', 'name' => 'Unit Farmasi']);

        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $sub1 = \App\Models\RbaSubmission::create(['rba_header_id' => $header->id, 'unit_id' => $unit1->id]);
        $sub2 = \App\Models\RbaSubmission::create(['rba_header_id' => $header->id, 'unit_id' => $unit2->id]);

        $group = \App\Models\KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Group']);
        $ac = \App\Models\AccountCode::create(['kelompok_belanja_id' => $group->id, 'code' => '5.1.01', 'name' => 'Belanja Gaji']);

        \App\Models\RbaAccountPagu::create([
            'rba_header_id' => $header->id,
            'account_code_id' => $ac->id,
            'nominal_pagu' => 100000000
        ]);

        $op1 = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit1->id, 'name' => 'Op Inap']);
        $op2 = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit2->id, 'name' => 'Op Farmasi']);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $sub1->id,
            'account_code_id' => $ac->id,
            'description' => 'Item Inap',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 1000,
            'nominal_request' => 1000,
            'created_by' => $op1->id
        ]);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $sub2->id,
            'account_code_id' => $ac->id,
            'description' => 'Item Farmasi',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 2000,
            'nominal_request' => 2000,
            'created_by' => $op2->id
        ]);

        // 1. Test Admin Print RBA Final All
        $resAll = $this->actingAs($admin)->get(route('admin.headers.print-preview-final', ['header' => $header->id]));
        $resAll->assertStatus(200);
        $resAll->assertSee('USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)');
        $resAll->assertSee('PAGU FINAL (Rp)');
        $resAll->assertSee('100.000.000');
        $resAll->assertSee('Item Inap');
        $resAll->assertSee('Item Farmasi');
        $resAll->assertSee('Unit Rawat Inap');
        $resAll->assertSee('Unit Farmasi');

        // 2. Test Admin Print RBA Final Filter Unit 1
        $resUnit1 = $this->actingAs($admin)->get(route('admin.headers.print-preview-final', [
            'header' => $header->id,
            'unit_ids' => [$unit1->id]
        ]));
        $resUnit1->assertStatus(200);
        $resUnit1->assertSee('Item Inap');
        $resUnit1->assertDontSee('Item Farmasi');
        $resUnit1->assertSee('Unit Rawat Inap');
    }

    public function test_admin_can_view_unit_monitoring_with_supervisor_and_operator_progress()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $unit = \App\Models\Unit::create(['code' => 'U01', 'name' => 'Unit Pelayanan Medis']);

        $supervisor = User::factory()->create([
            'role' => 'Supervisor',
            'unit_id' => $unit->id,
            'name' => 'Dr. Supervisor Medis',
            'is_active' => true,
        ]);

        $operator = User::factory()->create([
            'role' => 'Operator',
            'unit_id' => $unit->id,
            'name' => 'Operator Medis Alfa',
            'is_active' => true,
        ]);

        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $submission = \App\Models\RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $unit->id,
            'status_submission' => 'Pending Supervisor',
        ]);

        // Operator background
        \App\Models\RbaSubmissionOperatorBackground::create([
            'rba_submission_id' => $submission->id,
            'user_id' => $operator->id,
            'background' => 'Latar Belakang Pelayanan Medis Unit Alfa',
        ]);

        // KAK document
        $doc = \App\Models\RbaSubmissionDocument::create([
            'rba_submission_id' => $submission->id,
            'user_id' => $operator->id,
            'type' => 'KAK',
        ]);
        \App\Models\RbaSubmissionDocumentVersion::create([
            'rba_submission_document_id' => $doc->id,
            'file_path' => 'docs/kak.pdf',
            'version_number' => 1,
            'uploaded_by' => $operator->id,
        ]);

        $group = \App\Models\KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Group']);
        $ac = \App\Models\AccountCode::create(['kelompok_belanja_id' => $group->id, 'code' => '5.1.01', 'name' => 'Belanja Barang']);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $ac->id,
            'description' => 'Alat Medis Alfa',
            'volume' => 1,
            'satuan' => 'Set',
            'harga_satuan' => 15000000,
            'nominal_request' => 15000000,
            'created_by' => $operator->id,
            'is_submitted' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.headers.show', $header->id));

        $response->assertStatus(200);

        // Verify monitoring panel
        $response->assertSee('Monitoring Penginputan Unit dan Progres RBA');
        $response->assertSee('Unit Pelayanan Medis');
        $response->assertSee('Dr. Supervisor Medis');
        $response->assertSee('15.000.000');

        // Verify operator metrics & interactive background modal
        $response->assertSee('Operator Medis Alfa');
        $response->assertSee('Sudah Diisi');
        $response->assertSee('Klik untuk melihat isi latar belakang');
        $response->assertSee('Latar Belakang Pelayanan Medis Unit Alfa');
        $response->assertSee('Latar Belakang Usulan RBA');
        $response->assertSee('KAK');
        $response->assertSee('RAK');
        $response->assertSee('RTP');

        // Verify underlying RBA tree table is completely intact
        $response->assertSee('KODE REKENING');
        $response->assertSee('URAIAN BELANJA');
        $response->assertSee('USULAN (Rp)');

        // Verify Document and Proposal PDF modals triggers & titles
        $response->assertSee('Dokumen Pokok RBA (KAK / RAK / RTP)');
        $response->assertSee('PDF Lampiran Usulan Belanja');
        $response->assertSee('showDocuments');
        $response->assertSee('showProposalPdfs');
    }

    public function test_admin_can_view_document_and_proposal_pdf_modals_with_versioning()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $unit = \App\Models\Unit::create(['code' => 'U01', 'name' => 'Unit Bedah Sentral']);
        $supervisor = User::factory()->create(['role' => 'Supervisor', 'unit_id' => $unit->id, 'name' => 'Dr. Supervisor Bedah']);
        $operator = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit->id, 'name' => 'Operator Bedah']);

        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $submission = \App\Models\RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $unit->id,
            'status_submission' => 'Pending Supervisor',
        ]);

        // 1. Setup KAK Document with Version 1 and Version 2
        $kakDoc = \App\Models\RbaSubmissionDocument::create([
            'rba_submission_id' => $submission->id,
            'user_id' => $operator->id,
            'type' => 'KAK',
        ]);
        \App\Models\RbaSubmissionDocumentVersion::create([
            'rba_submission_document_id' => $kakDoc->id,
            'file_path' => 'documents/kak_v1.pdf',
            'version_number' => 1,
            'uploaded_by' => $operator->id,
        ]);
        \App\Models\RbaSubmissionDocumentVersion::create([
            'rba_submission_document_id' => $kakDoc->id,
            'file_path' => 'documents/kak_v2.pdf',
            'version_number' => 2,
            'uploaded_by' => $operator->id,
        ]);

        // 2. Setup Detail with Attachments V1 and V2
        $group = \App\Models\KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Group']);
        $ac = \App\Models\AccountCode::create(['kelompok_belanja_id' => $group->id, 'code' => '5.1.02', 'name' => 'Belanja Pemeliharaan Bedah']);

        $detail = \App\Models\RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $ac->id,
            'description' => 'Servis Meja Operasi',
            'volume' => 1,
            'satuan' => 'Unit',
            'harga_satuan' => 25000000,
            'nominal_request' => 25000000,
            'created_by' => $operator->id,
            'is_submitted' => true,
        ]);

        \App\Models\RbaAttachment::create([
            'rba_detail_id' => $detail->id,
            'file_path' => 'attachments/servis_v1.pdf',
            'version_number' => 1,
            'uploaded_by' => $operator->id,
        ]);
        \App\Models\RbaAttachment::create([
            'rba_detail_id' => $detail->id,
            'file_path' => 'attachments/servis_v2.pdf',
            'version_number' => 2,
            'uploaded_by' => $operator->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.headers.show', $header->id));

        $response->assertStatus(200);

        // Assert KAK Versioning in JSON payload
        $response->assertSee('documents/kak_v1.pdf');
        $response->assertSee('documents/kak_v2.pdf');
        $response->assertSee('2 versi');

        // Assert Proposal PDF Versioning in JSON payload
        $response->assertSee('attachments/servis_v1.pdf');
        $response->assertSee('attachments/servis_v2.pdf');
        $response->assertSee('Servis Meja Operasi');
        $response->assertSee('5.1.02');
        $response->assertSee('1/1 PDF');
    }

    public function test_admin_can_sync_all_unit_statuses_under_header()
    {
        $admin = User::factory()->create(['role' => 'Administrator', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Instalasi Bedah', 'code' => 'IB', 'is_active' => true]);
        $operator = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit->id, 'is_active' => true]);
        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $submission = \App\Models\RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $unit->id,
            'status_submission' => 'Pending Supervisor',
        ]);

        $group = \App\Models\KelompokBelanja::create(['kode' => 'KB03', 'name' => 'Belanja Modal']);
        $ac = \App\Models\AccountCode::create(['kelompok_belanja_id' => $group->id, 'code' => '5.2.01', 'name' => 'Modal Alat']);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $ac->id,
            'description' => 'Lampu Operasi',
            'volume' => 1,
            'satuan' => 'Unit',
            'harga_satuan' => 20000000,
            'nominal_request' => 20000000,
            'created_by' => $operator->id,
            'is_submitted' => true,
            'is_validated' => true,
        ]);

        $this->assertEquals('Pending Supervisor', $submission->status_submission);

        $response = $this->actingAs($admin)->post(route('admin.headers.sync-unit-statuses', $header));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('Validated', $submission->fresh()->status_submission);
    }

    public function test_admin_can_sync_single_submission_status()
    {
        $admin = User::factory()->create(['role' => 'Administrator', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Laboratorium', 'code' => 'LAB', 'is_active' => true]);
        $operator = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit->id, 'is_active' => true]);
        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $submission = \App\Models\RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $unit->id,
            'status_submission' => 'Draft',
        ]);

        $group = \App\Models\KelompokBelanja::create(['kode' => 'KB04', 'name' => 'Belanja Bahan']);
        $ac = \App\Models\AccountCode::create(['kelompok_belanja_id' => $group->id, 'code' => '5.1.03', 'name' => 'Reagen']);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $ac->id,
            'description' => 'Reagen Kimia Darah',
            'volume' => 5,
            'satuan' => 'Kit',
            'harga_satuan' => 1000000,
            'nominal_request' => 5000000,
            'created_by' => $operator->id,
            'is_submitted' => true,
            'is_validated' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.submissions.sync-status', $submission));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('Validated', $submission->fresh()->status_submission);
    }

    public function test_non_admin_cannot_access_sync_endpoints()
    {
        $admin = User::factory()->create(['role' => 'Administrator', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Unit Test', 'code' => 'UT', 'is_active' => true]);
        $operator = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit->id, 'is_active' => true]);
        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);
        $submission = \App\Models\RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $unit->id,
            'status_submission' => 'Draft',
        ]);

        $res1 = $this->actingAs($operator)->post(route('admin.headers.sync-unit-statuses', $header));
        $res1->assertStatus(403);

        $res2 = $this->actingAs($operator)->post(route('admin.submissions.sync-status', $submission));
        $res2->assertStatus(403);
    }
}
