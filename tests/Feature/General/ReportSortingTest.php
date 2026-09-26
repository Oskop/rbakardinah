<?php

namespace Tests\Feature\General;

use Tests\TestCase;
use App\Models\User;
use App\Models\Unit;
use App\Models\SubUnit;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use App\Models\RbaSubmission;
use App\Models\RbaDetail;
use App\Models\AccountCode;
use App\Models\KelompokBelanja;
use App\Models\RbaAccountPagu;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportSortingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $supervisor;
    protected $operator;
    protected $unit;
    protected $header;
    protected $submission;
    protected $acc1;
    protected $acc2;
    protected $detail1;
    protected $detail2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create(['name' => 'Unit Bedah', 'code' => 'UB01']);
        $subUnit = SubUnit::create(['unit_id' => $this->unit->id, 'name' => 'Sub Unit Bedah']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'Administrator',
            'is_active' => true,
        ]);

        $this->supervisor = User::create([
            'name' => 'Supervisor User',
            'email' => 'spv@test.com',
            'password' => bcrypt('password'),
            'role' => 'Supervisor',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $this->operator = User::create([
            'name' => 'Operator User',
            'email' => 'op@test.com',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
            'sub_unit_id' => $subUnit->id,
            'is_active' => true,
        ]);

        $period = RbaPeriod::create(['name' => 'RBA Murni 2027', 'is_active' => true]);
        $this->header = RbaHeader::create([
            'period_id' => $period->id,
            'admin_id' => $this->admin->id,
            'year' => '2027',
            'status_global' => 'Draft',
        ]);

        $this->submission = RbaSubmission::create([
            'rba_header_id' => $this->header->id,
            'unit_id' => $this->unit->id,
            'status_submission' => 'Draft',
        ]);

        $kelompok = KelompokBelanja::create(['kode' => '5.1', 'name' => 'Belanja Operasi']);

        // Akun 1: Code 5.1.02.01, Pagu 50.000.000
        $this->acc1 = AccountCode::create([
            'kelompok_belanja_id' => $kelompok->id,
            'code' => '5.1.02.01',
            'name' => 'Belanja Obat-Obatan',
            'is_active' => true,
        ]);

        // Akun 2: Code 5.1.01.01, Pagu 10.000.000 (Kode lebih kecil dari acc1, tapi usulan lebih besar)
        $this->acc2 = AccountCode::create([
            'kelompok_belanja_id' => $kelompok->id,
            'code' => '5.1.01.01',
            'name' => 'Belanja Gaji dan Tunjangan',
            'is_active' => true,
        ]);

        // Detail 1: Pada acc1, nominal 5.000.000, Uraian: "Z - Paracetamol"
        $this->detail1 = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->acc1->id,
            'description' => 'Z - Paracetamol Tablet',
            'volume' => 10,
            'satuan' => 'Box',
            'harga_satuan' => 500000,
            'nominal_request' => 5000000,
            'is_submitted' => true,
            'created_by' => $this->operator->id,
        ]);

        // Detail 2: Pada acc2, nominal 20.000.000, Uraian: "A - Honor Dokter"
        $this->detail2 = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->acc2->id,
            'description' => 'A - Honor Dokter Spesialis',
            'volume' => 4,
            'satuan' => 'Bulan',
            'harga_satuan' => 5000000,
            'nominal_request' => 20000000,
            'is_submitted' => true,
            'created_by' => $this->operator->id,
        ]);

        RbaAccountPagu::create([
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->acc1->id,
            'nominal_pagu' => 50000000,
        ]);

        RbaAccountPagu::create([
            'rba_header_id' => $this->header->id,
            'account_code_id' => $this->acc2->id,
            'nominal_pagu' => 10000000,
        ]);
    }

    public function test_default_sorting_is_account_code_asc()
    {
        // 5.1.01.01 (acc2) harus muncul sebelum 5.1.02.01 (acc1) secara default
        $response = $this->actingAs($this->operator)
            ->get(route('operator.submissions.print-preview', $this->submission->id));

        $response->assertStatus(200);
        $response->assertSee('Urutan Data: Nomor Rekening Belanja');

        $html = $response->getContent();
        $posAcc2 = strpos($html, '5.1.01.01');
        $posAcc1 = strpos($html, '5.1.02.01');

        $this->assertTrue($posAcc2 !== false && $posAcc1 !== false);
        $this->assertTrue($posAcc2 < $posAcc1, 'Akun dengan kode lebih kecil harus muncul terlebih dahulu pada default sort.');
    }

    public function test_sorting_by_nominal_request_desc()
    {
        // Detail 2 (20jt) harus muncul sebelum Detail 1 (5jt) saat sort by nominal_request desc
        $response = $this->actingAs($this->operator)
            ->get(route('operator.submissions.print-preview', [
                'submission' => $this->submission->id,
                'sort_by' => 'nominal_request',
                'sort_dir' => 'desc',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Urutan Data: Total Usulan Belanja (Rp)');

        $html = $response->getContent();
        $posDetail2 = strpos($html, 'A - Honor Dokter Spesialis');
        $posDetail1 = strpos($html, 'Z - Paracetamol Tablet');

        $this->assertTrue($posDetail2 < $posDetail1, 'Detail dengan nominal lebih besar harus muncul di atas saat sort desc.');
    }

    public function test_sorting_by_description_asc()
    {
        // Detail 2 ("A - Honor") harus muncul sebelum Detail 1 ("Z - Paracetamol")
        $response = $this->actingAs($this->supervisor)
            ->get(route('supervisor.submissions.print-preview', [
                'submission' => $this->submission->id,
                'sort_by' => 'description',
                'sort_dir' => 'asc',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Urutan Data: Uraian &amp; Spesifikasi Belanja', false);

        $html = $response->getContent();
        $posDetail2 = strpos($html, 'A - Honor Dokter Spesialis');
        $posDetail1 = strpos($html, 'Z - Paracetamol Tablet');

        $this->assertTrue($posDetail2 < $posDetail1);
    }

    public function test_sorting_by_pagu_final_desc_in_rba_final()
    {
        // Acc 1 punya pagu 50jt, Acc 2 punya pagu 10jt
        // Saat sort by pagu_final desc, Acc 1 (Obat) harus muncul sebelum Acc 2 (Gaji)
        $response = $this->actingAs($this->admin)
            ->get(route('admin.headers.print-preview-final', [
                'header' => $this->header->id,
                'grouping' => 'flat',
                'sort_by' => 'pagu_final',
                'sort_dir' => 'desc',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Urutan Data: Nominal Pagu Final (Rp)');

        $html = $response->getContent();
        $posAcc1 = strpos($html, 'Belanja Obat-Obatan');
        $posAcc2 = strpos($html, 'Belanja Gaji dan Tunjangan');

        $this->assertTrue($posAcc1 < $posAcc2, 'Pagu 50jt harus muncul sebelum pagu 10jt saat sort pagu_final desc.');
    }

    public function test_all_pages_contain_sorting_controls()
    {
        // 1. Menu Laporan
        $resReports = $this->actingAs($this->admin)->get(route('reports.index'));
        $resReports->assertStatus(200);
        $resReports->assertSee('Pengurutan Kolom Laporan');
        $resReports->assertSee('name="sort_by"', false);
        $resReports->assertSee('name="sort_dir"', false);

        // 2. Supervisor Show Page
        $resSpv = $this->actingAs($this->supervisor)->get(route('supervisor.submissions.show', $this->submission->id));
        $resSpv->assertStatus(200);
        $resSpv->assertSee('Pengurutan Kolom Laporan');
        $resSpv->assertSee('name="sort_by"', false);

        // 3. Admin Show Page
        $resAdm = $this->actingAs($this->admin)->get(route('admin.headers.show', $this->header->id));
        $resAdm->assertStatus(200);
        $resAdm->assertSee('Pengurutan Kolom Laporan');
        $resAdm->assertSee('name="sort_by"', false);

        // 4. Operator Show Page
        $resOp = $this->actingAs($this->operator)->get(route('operator.submissions.show', $this->submission->id));
        $resOp->assertStatus(200);
        $resOp->assertSee('Kustomisasi Urutan Kolom');
        $resOp->assertSee('name="sort_by"', false);
    }
}
