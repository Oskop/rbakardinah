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
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_pdf_version_history()
    {
        $unit = Unit::create(['code' => 'U01', 'name' => 'Unit Test']);
        $operator = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit->id]);

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
            'status_submission' => 'Draft'
        ]);

        $group = KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Test Group']);
        $accountCode = AccountCode::create([
            'kelompok_belanja_id' => $group->id,
            'code' => '5.1.01',
            'name' => 'Belanja ATK'
        ]);
        $detail = RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $accountCode->id,
            'description' => 'Test Item',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 1000,
            'nominal_request' => 1000,
            'created_by' => $operator->id
        ]);

        // Add versions
        $detail->attachments()->create(['file_path' => 'v1.pdf', 'version_number' => 1, 'uploaded_by' => $operator->id]);
        $detail->attachments()->create(['file_path' => 'v2.pdf', 'version_number' => 2, 'uploaded_by' => $operator->id]);

        $response = $this->actingAs($operator)->get(route('history.show', $detail));

        $response->assertStatus(200);
        $response->assertSee('Versi 1');
        $response->assertSee('Versi 2');
    }
}
