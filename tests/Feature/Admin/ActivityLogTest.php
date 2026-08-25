<?php

namespace Tests\Feature\Admin;

use App\Models\AccountCode;
use App\Models\ActivityLog;
use App\Models\KelompokBelanja;
use App\Models\RbaDetail;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use App\Models\RbaSubmission;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $supervisor;
    protected $operator;
    protected $unit;
    protected $header;
    protected $accountCode;
    protected $submission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create(['code' => 'U01', 'name' => 'Unit Pelayanan']);
        $group = KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Belanja Operasional']);
        $this->accountCode = AccountCode::create([
            'kelompok_belanja_id' => $group->id,
            'code' => '5.1.01',
            'name' => 'Belanja Gaji'
        ]);

        $this->admin = User::factory()->create(['role' => 'Administrator', 'name' => 'Admin Utama']);
        $this->supervisor = User::factory()->create(['role' => 'Supervisor', 'unit_id' => $this->unit->id, 'name' => 'Supervisor Unit']);
        $this->operator = User::factory()->create(['role' => 'Operator', 'unit_id' => $this->unit->id, 'name' => 'Operator Unit']);

        $period = RbaPeriod::create(['name' => 'Murni']);
        $this->header = RbaHeader::create([
            'period_id' => $period->id,
            'year' => 2026,
            'admin_id' => $this->admin->id,
            'status_global' => 'Active'
        ]);

        $this->submission = RbaSubmission::create([
            'rba_header_id' => $this->header->id,
            'unit_id' => $this->unit->id,
            'status_submission' => 'Draft',
            'background' => 'Latar belakang pengusulan'
        ]);
    }

    public function test_activity_is_logged_when_operator_creates_and_updates_detail()
    {
        // 1. Operator creates detail
        $detail = null;
        $this->actingAs($this->operator);
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Pengadaan Komputer Kantor',
            'volume' => 2,
            'satuan' => 'Unit',
            'harga_satuan' => 10000000,
            'nominal_request' => 20000000,
            'is_submitted' => false,
            'created_by' => $this->operator->id
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'model_type' => RbaDetail::class,
            'model_id' => $detail->id,
            'user_name' => 'Operator Unit',
            'user_role' => 'Operator'
        ]);

        // 2. Operator updates detail
        $detail->update([
            'volume' => 3,
            'nominal_request' => 30000000,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'model_type' => RbaDetail::class,
            'model_id' => $detail->id,
            'user_name' => 'Operator Unit',
        ]);

        // Verify old and new values in log
        $updateLog = ActivityLog::where('model_type', RbaDetail::class)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertEquals(2, $updateLog->old_values['volume']);
        $this->assertEquals(3, $updateLog->new_values['volume']);
    }

    public function test_activity_is_logged_when_supervisor_validates_detail()
    {
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Obat-obatan Farmasi',
            'volume' => 10,
            'satuan' => 'Kotak',
            'harga_satuan' => 50000,
            'nominal_request' => 500000,
            'is_submitted' => true,
            'created_by' => $this->operator->id
        ]);

        // Supervisor validates detail
        $this->actingAs($this->supervisor);
        $detail->update([
            'is_validated' => true,
            'validated_at' => now(),
            'validated_by' => $this->supervisor->id
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'model_type' => RbaDetail::class,
            'model_id' => $detail->id,
            'user_name' => 'Supervisor Unit',
            'user_role' => 'Supervisor'
        ]);

        $valLog = ActivityLog::where('model_type', RbaDetail::class)
            ->where('user_role', 'Supervisor')
            ->latest('id')
            ->first();

        $this->assertStringContainsString('memvalidasi', $valLog->description);
    }

    public function test_activity_is_logged_when_admin_sets_pagu()
    {
        $this->actingAs($this->admin);
        $pagu = $this->header->accountPagus()->create([
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 50000000
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'model_type' => \App\Models\RbaAccountPagu::class,
            'model_id' => $pagu->id,
            'user_name' => 'Admin Utama',
            'user_role' => 'Administrator'
        ]);
    }

    public function test_admin_can_access_log_data_menu()
    {
        // Generate some sample logs
        RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Testing Log',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 100000,
            'nominal_request' => 100000,
            'created_by' => $this->operator->id
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Log Data &amp; Riwayat Transaksi Database', false);
        $response->assertSee('Total Transaksi');
        $response->assertSee('Item Testing Log');
    }

    public function test_supervisor_and_operator_cannot_access_log_data_menu()
    {
        // Supervisor -> 403
        $resSupervisor = $this->actingAs($this->supervisor)->get(route('admin.logs.index'));
        $resSupervisor->assertStatus(403);

        // Operator -> 403
        $resOperator = $this->actingAs($this->operator)->get(route('admin.logs.index'));
        $resOperator->assertStatus(403);

        // Guest -> Redirect to login
        auth()->logout();
        $resGuest = $this->get(route('admin.logs.index'));
        $resGuest->assertRedirect(route('login'));
    }

    public function test_admin_can_filter_logs()
    {
        $this->actingAs($this->operator);
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Special Unique Description Z999',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 100000,
            'nominal_request' => 100000,
            'created_by' => $this->operator->id
        ]);

        // Filter by role = Operator
        $resRole = $this->actingAs($this->admin)->get(route('admin.logs.index', ['role' => 'Operator']));
        $resRole->assertStatus(200);
        $resRole->assertSee('Special Unique Description Z999');

        // Filter by search keyword
        $resSearch = $this->actingAs($this->admin)->get(route('admin.logs.index', ['search' => 'Z999']));
        $resSearch->assertStatus(200);
        $resSearch->assertSee('Special Unique Description Z999');

        // Filter by non-matching search keyword
        $resNoMatch = $this->actingAs($this->admin)->get(route('admin.logs.index', ['search' => 'NONEXISTENTKEYWORD123']));
        $resNoMatch->assertStatus(200);
        $resNoMatch->assertDontSee('Special Unique Description Z999');
    }
}
