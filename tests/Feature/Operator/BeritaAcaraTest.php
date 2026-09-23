<?php

namespace Tests\Feature\Operator;

use App\Models\User;
use App\Models\Unit;
use App\Models\SubUnit;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use App\Models\RbaSubmission;
use App\Models\AccountCode;
use App\Models\KelompokBelanja;
use App\Models\RbaDetail;
use App\Models\RbaAccountPagu;
use App\Models\RbaDeskVerification;
use App\Models\RbaDeskVerificationDocument;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BeritaAcaraTest extends TestCase
{
    use RefreshDatabase;

    protected $operator;
    protected $supervisor;
    protected $unit;
    protected $subUnit;
    protected $submission;
    protected $headerMurni;
    protected $headerPerubahan;
    protected $accountCode;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->unit = Unit::create(['code' => 'U01', 'name' => 'Unit PDE RSUD Kardinah']);
        $this->subUnit = SubUnit::create(['unit_id' => $this->unit->id, 'name' => 'Sub Bagian PDE']);

        $this->operator = User::create([
            'name' => 'Operator PDE',
            'email' => 'operator_pde@hospital.com',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
            'sub_unit_id' => $this->subUnit->id,
            'can_propose' => true,
        ]);

        $this->supervisor = User::create([
            'name' => 'Supervisor Rensar',
            'email' => 'supervisor_rensar@hospital.com',
            'password' => bcrypt('password'),
            'role' => 'Supervisor',
            'unit_id' => $this->unit->id,
        ]);

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'Administrator',
            'unit_id' => $this->unit->id,
        ]);

        $periodMurni = RbaPeriod::create(['name' => 'Murni']);
        $periodPerubahan = RbaPeriod::create(['name' => 'Perubahan']);

        $this->headerMurni = RbaHeader::create([
            'period_id' => $periodMurni->id,
            'year' => 2026,
            'admin_id' => $admin->id,
            'status_global' => 'Locked'
        ]);

        $this->headerPerubahan = RbaHeader::create([
            'period_id' => $periodPerubahan->id,
            'year' => 2026,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $kelompok = KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Operasional']);
        $this->accountCode = AccountCode::create([
            'kelompok_belanja_id' => $kelompok->id,
            'code' => '5.1.02.01.01.0001',
            'name' => 'Belanja Jasa Konversi Aplikasi'
        ]);

        // Submission Murni (Awal)
        $subMurni = RbaSubmission::create([
            'rba_header_id' => $this->headerMurni->id,
            'unit_id' => $this->unit->id,
            'status_submission' => 'Validated'
        ]);

        RbaDetail::create([
            'rba_submission_id' => $subMurni->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Murni Awal',
            'volume' => 1,
            'satuan' => 'Paket',
            'harga_satuan' => 300000000,
            'nominal_request' => 300000000,
            'created_by' => $this->operator->id,
            'is_submitted' => true,
            'is_validated' => true,
        ]);

        // Submission Perubahan (Saat Ini)
        $this->submission = RbaSubmission::create([
            'rba_header_id' => $this->headerPerubahan->id,
            'unit_id' => $this->unit->id,
            'status_submission' => 'Draft'
        ]);

        RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Perubahan Baru',
            'volume' => 1,
            'satuan' => 'Paket',
            'harga_satuan' => 450000000,
            'nominal_request' => 450000000,
            'created_by' => $this->operator->id,
            'is_submitted' => false,
            'is_validated' => false,
        ]);
    }

    public function test_operator_can_save_and_update_berita_acara_parameters()
    {
        $response = $this->actingAs($this->operator)
            ->post(route('operator.submissions.berita-acara.save', $this->submission), [
                'hari' => 'Sabtu',
                'tanggal_desk' => '2026-09-05',
                'tanggal_desk_spelled' => 'tanggal Lima Bulan September Tahun Dua Ribu Dua Puluh Enam',
                'ruang_desk' => 'Ruang RA. Kardinah',
                'sub_unit_name' => 'Sub Bagian PDE RSUD Kardinah',
                'catatan' => 'Catatan verifikasi desk lengkap.',
                'is_usulan_sipakar' => 'Ya',
                'kriteria_latar_belakang' => 'Perlu Perbaikan',
                'catatan_perbaikan_latar_belakang' => 'Mohon sertakan dasar regulasi Permenkes pada poin 2 latar belakang.',
                'is_dokumen_rab_uploaded' => 'Ya',
                'tim_asistensi' => ['M. Riza F., A.Md.', 'Ananta Bayu, S.Kom'],
                'anggota_sub_unit' => ['Operator PDE'],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rba_desk_verifications', [
            'rba_submission_id' => $this->submission->id,
            'user_id' => $this->operator->id,
            'hari' => 'Sabtu',
            'ruang_desk' => 'Ruang RA. Kardinah',
            'kriteria_latar_belakang' => 'Perlu Perbaikan',
            'catatan_perbaikan_latar_belakang' => 'Mohon sertakan dasar regulasi Permenkes pada poin 2 latar belakang.',
        ]);

        $ba = RbaDeskVerification::where('rba_submission_id', $this->submission->id)->first();
        $this->assertEquals(['M. Riza F., A.Md.', 'Ananta Bayu, S.Kom'], $ba->tim_asistensi);
    }

    public function test_operator_can_view_print_preview_berita_acara_with_correct_data()
    {
        // Buat data BA
        RbaDeskVerification::create([
            'rba_submission_id' => $this->submission->id,
            'user_id' => $this->operator->id,
            'hari' => 'Sabtu',
            'tanggal_desk' => '2026-09-05',
            'tanggal_desk_spelled' => 'tanggal Lima Bulan September Tahun Dua Ribu Dua Puluh Enam',
            'ruang_desk' => 'Ruang RA. Kardinah',
            'sub_unit_name' => 'Sub Bagian PDE RSUD Kardinah',
            'catatan' => 'Hasil verifikasi disetujui.',
            'is_usulan_sipakar' => 'Ya',
            'kriteria_latar_belakang' => 'Perlu Perbaikan',
            'catatan_perbaikan_latar_belakang' => 'Perbaiki uraian latar belakang pada poin 2.',
            'is_dokumen_rab_uploaded' => 'Ya',
            'tim_asistensi' => ['M. Riza F., A.Md.', 'Ananta Bayu, S.Kom'],
            'anggota_sub_unit' => ['Operator PDE'],
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->operator)
            ->get(route('operator.submissions.berita-acara.print', $this->submission));

        $response->assertStatus(200);
        $response->assertSee('BERITA ACARA ASISTENSI / DESK');
        $response->assertSee('RENCANA ANGGARAN BELANJA (RAB) PERUBAHAN');
        $response->assertSee('TAHUN ANGGARAN 2026');
        $response->assertSee('Sabtu');
        $response->assertSee('tanggal Lima Bulan September Tahun Dua Ribu Dua Puluh Enam');
        $response->assertSee('Ruang RA. Kardinah');
        $response->assertSee('Sub Bagian PDE RSUD Kardinah');
        $response->assertSee('Belanja Jasa Konversi Aplikasi');
        // Awal: 300.000.000, Perubahan: 450.000.000, Selisih: 150.000.000
        $response->assertSee('300.000.000');
        $response->assertSee('450.000.000');
        $response->assertSee('150.000.000');
        $response->assertSee('Perbaiki uraian latar belakang pada poin 2.');
        $response->assertSee('M. Riza F., A.Md.');
        $response->assertSee('Ananta Bayu, S.Kom');
        $response->assertSee('Operator PDE');
        $response->assertSee('sign-subtable');
        $response->assertSee('sign-name-col');
        $response->assertSee('sign-dots-col');
    }

    public function test_operator_print_preview_handles_long_names_and_titles_without_distortion()
    {
        $longSubUnitName = 'Sub Bagian Tata Usaha dan Kepegawaian serta Hukum dan Hubungan Masyarakat';
        $longOperator1 = 'dr. H. Muhammad Reza Pahlevi, Sp.A, M.Kes, FINASIM';
        $longOperator2 = 'Ns. Siti Fatimah Nurjanah, S.Kep., M.Kep., Sp.Kep.MB';
        $timLong = ['M. Riza Fauzi Rahman, S.Kom., M.Eng.', 'Ananta Bayu Pradana, S.Kom.', 'Nurul Lathifah Rahmawati, S.I.Pus.'];

        RbaDeskVerification::create([
            'rba_submission_id' => $this->submission->id,
            'user_id' => $this->operator->id,
            'hari' => 'Rabu',
            'tanggal_desk' => '2026-09-23',
            'tanggal_desk_spelled' => 'tanggal Dua Puluh Tiga Bulan September Tahun Dua Ribu Dua Puluh Enam',
            'ruang_desk' => 'Ruang Rapat Direksi RSUD Kardinah',
            'sub_unit_name' => $longSubUnitName,
            'catatan' => 'Verifikasi usulan belanja dengan nama operator dan gelar spesialis lengkap.',
            'is_usulan_sipakar' => 'Ya',
            'kriteria_latar_belakang' => 'Ya',
            'is_dokumen_rab_uploaded' => 'Ya',
            'tim_asistensi' => $timLong,
            'anggota_sub_unit' => [$longOperator1, $longOperator2],
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->operator)
            ->get(route('operator.submissions.berita-acara.print', $this->submission));

        $response->assertStatus(200);
        $response->assertSee($longSubUnitName);
        $response->assertSee($longOperator1);
        $response->assertSee($longOperator2);
        $response->assertSee('M. Riza Fauzi Rahman, S.Kom., M.Eng.');
        $response->assertSee('sign-column-left');
        $response->assertSee('sign-column-right');
        $response->assertSee('sign-subtable');
        $response->assertSee('sign-name-col');
        $response->assertSee('sign-dots-col');
    }

    public function test_operator_can_upload_signed_berita_acara_document_and_increments_version()
    {
        $file1 = UploadedFile::fake()->create('berita_acara_v1.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->operator)
            ->post(route('operator.submissions.berita-acara.upload', $this->submission), [
                'attachment' => $file1,
                'notes' => 'Tanda tangan lengkap gelombang 1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rba_desk_verification_documents', [
            'version_number' => 1,
            'original_filename' => 'berita_acara_v1.pdf',
            'notes' => 'Tanda tangan lengkap gelombang 1',
        ]);

        // Upload Versi 2 (Revisi)
        $file2 = UploadedFile::fake()->create('berita_acara_v2_revisi.pdf', 350, 'application/pdf');

        $response2 = $this->actingAs($this->operator)
            ->post(route('operator.submissions.berita-acara.upload', $this->submission), [
                'attachment' => $file2,
                'notes' => 'Revisi tanda tangan tim asistensi',
            ]);

        $response2->assertRedirect();
        $response2->assertSessionHas('success');

        $this->assertDatabaseHas('rba_desk_verification_documents', [
            'version_number' => 2,
            'original_filename' => 'berita_acara_v2_revisi.pdf',
            'notes' => 'Revisi tanda tangan tim asistensi',
        ]);

        // Verifikasi History Endpoint
        $historyResponse = $this->actingAs($this->operator)
            ->get(route('operator.submissions.berita-acara.history', $this->submission));

        $historyResponse->assertStatus(200);
        $historyResponse->assertSee('berita_acara_v1.pdf');
        $historyResponse->assertSee('berita_acara_v2_revisi.pdf');
        $historyResponse->assertSee('V1');
        $historyResponse->assertSee('V2');
    }

    public function test_activity_logs_records_berita_acara_creation_and_document_upload()
    {
        // 1. Simpan parameter BA
        $this->actingAs($this->operator)
            ->post(route('operator.submissions.berita-acara.save', $this->submission), [
                'hari' => 'Senin',
                'tanggal_desk' => '2026-09-07',
                'tanggal_desk_spelled' => 'tanggal Tujuh Bulan September Tahun Dua Ribu Dua Puluh Enam',
                'ruang_desk' => 'Ruang RA. Kardinah',
                'sub_unit_name' => 'Sub Bagian PDE',
                'catatan' => 'OK',
                'is_usulan_sipakar' => 'Ya',
                'kriteria_latar_belakang' => 'Ya',
                'is_dokumen_rab_uploaded' => 'Ya',
                'tim_asistensi' => ['M. Riza F.'],
                'anggota_sub_unit' => ['Operator PDE'],
            ]);

        // 2. Upload file scan
        $file = UploadedFile::fake()->create('ba_signed.pdf', 200, 'application/pdf');
        $this->actingAs($this->operator)
            ->post(route('operator.submissions.berita-acara.upload', $this->submission), [
                'attachment' => $file,
            ]);

        // Cek log tercatat di activity_logs
        $this->assertDatabaseHas('activity_logs', [
            'model_type' => RbaDeskVerification::class,
            'action' => 'created',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => RbaDeskVerificationDocument::class,
            'action' => 'created',
        ]);
    }

    public function test_supervisor_can_view_and_print_operator_berita_acara()
    {
        RbaDeskVerification::create([
            'rba_submission_id' => $this->submission->id,
            'user_id' => $this->operator->id,
            'hari' => 'Sabtu',
            'tanggal_desk' => '2026-09-05',
            'tanggal_desk_spelled' => 'tanggal Lima Bulan September Tahun Dua Ribu Dua Puluh Enam',
            'ruang_desk' => 'Ruang RA. Kardinah',
            'sub_unit_name' => 'Sub Bagian PDE RSUD Kardinah',
            'catatan' => 'Hasil verifikasi supervisor.',
            'is_usulan_sipakar' => 'Ya',
            'kriteria_latar_belakang' => 'Ya',
            'is_dokumen_rab_uploaded' => 'Ya',
            'tim_asistensi' => ['Supervisor Rensar'],
            'anggota_sub_unit' => ['Operator PDE'],
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('berita-acara.print', [
                'submission' => $this->submission->id,
                'user_id' => $this->operator->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('BERITA ACARA ASISTENSI / DESK');
        $response->assertSee('Supervisor Rensar');
    }
}
