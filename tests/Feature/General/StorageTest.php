<?php

namespace Tests\Feature\General;

use App\Models\User;
use App\Models\Unit;
use App\Models\RbaPeriod;
use App\Models\RbaHeader;
use App\Models\RbaSubmission;
use App\Models\AccountCode;
use App\Models\KelompokBelanja;
use App\Models\RbaDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_attachment_can_be_accessed_via_storage_url()
    {
        Storage::fake('public');

        $unit = Unit::create(['code' => 'U01', 'name' => 'Unit Test']);
        $group = KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Test Group']);
        $accountCode = AccountCode::create([
            'kelompok_belanja_id' => $group->id,
            'code' => '5.1.01',
            'name' => 'Belanja Gaji'
        ]);

        $admin = User::factory()->create(['role' => 'Administrator']);
        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'period_id' => $period->id,
            'year' => 2026,
            'admin_id' => $admin->id,
            'status_global' => 'Active'
        ]);

        $submission = RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $unit->id,
            'status_submission' => 'Draft',
            'background' => 'Latar belakang testing'
        ]);

        $operator = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit->id]);

        $detail = RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $accountCode->id,
            'description' => 'Test Detail Item',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 500000,
            'nominal_request' => 500000,
            'is_submitted' => false,
            'created_by' => $operator->id,
        ]);

        // Upload revision V2
        $fileV2 = UploadedFile::fake()->create('revisi_v2.pdf', 100, 'application/pdf');
        $this->actingAs($operator)->post(route('operator.details.upload-version', $detail), [
            'attachment' => $fileV2,
        ]);

        $latestAttachment = $detail->latestAttachment();
        $this->assertNotNull($latestAttachment);
        $this->assertEquals(1, $latestAttachment->version_number);

        // Put physical file in disk or fake disk
        $filePath = storage_path('app/public/' . $latestAttachment->file_path);
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        file_put_contents($filePath, '%PDF-1.4 Fake PDF Content');

        // Access via /storage/... URL
        $response = $this->get('/storage/' . $latestAttachment->file_path);
        $response->assertStatus(200);

        // Clean up created file
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function test_accessing_non_existent_storage_file_returns_404()
    {
        $response = $this->get('/storage/attachments/non_existent_file_xyz_9999.pdf');
        $response->assertStatus(404);
    }
}
