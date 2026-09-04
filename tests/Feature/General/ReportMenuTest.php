<?php

namespace Tests\Feature\General;

use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use App\Models\RbaSubmission;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportMenuTest extends TestCase
{
    use RefreshDatabase;

    protected Unit $unit;
    protected User $admin;
    protected User $supervisor;
    protected User $operator;
    protected RbaPeriod $period;
    protected RbaHeader $header;
    protected RbaSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create([
            'name' => 'Instalasi Teknologi Informasi',
            'code' => 'ITI',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'Administrator',
            'is_active' => true,
        ]);

        $this->supervisor = User::factory()->create([
            'role' => 'Supervisor',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $this->period = RbaPeriod::create([
            'name' => 'RBA Murni 2026',
            'is_active' => true,
        ]);

        $this->header = RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'open',
        ]);

        $this->submission = RbaSubmission::create([
            'rba_header_id' => $this->header->id,
            'unit_id' => $this->unit->id,
            'status_submission' => 'draft',
        ]);
    }

    public function test_unauthenticated_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('reports.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_administrator_can_access_reports_and_see_headers_and_options(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Pusat Laporan &amp; Cetak RBA', false);
        $response->assertSee('TA 2026');
        $response->assertSee('RBA Murni 2026');
        $response->assertSee('Seluruh RSUD');
        $response->assertSee('Filter Per Unit');
        $response->assertSee('Filter Operator');
        $response->assertSee('Buka Pratinjau Cetak');
    }

    public function test_supervisor_can_access_reports_and_see_their_unit_submissions_and_operators(): void
    {
        $response = $this->actingAs($this->supervisor)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Pusat Laporan &amp; Cetak RBA', false);
        $response->assertSee('TA 2026');
        $response->assertSee('Instalasi Teknologi Informasi');
        $response->assertSee('Cetak Semua Operator');
        $response->assertSee($this->operator->name);
        $response->assertSee('Buka Pratinjau Cetak');
    }

    public function test_operator_can_access_reports_and_see_their_unit_submissions(): void
    {
        $response = $this->actingAs($this->operator)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Pusat Laporan &amp; Cetak RBA', false);
        $response->assertSee('TA 2026');
        $response->assertSee('Instalasi Teknologi Informasi');
        $response->assertSee('Cakupan Otomatis Operator');
        $response->assertSee('Buka Pratinjau Cetak');
    }

    public function test_user_can_access_print_preview_endpoints_from_reports_menu(): void
    {
        // Admin
        $adminRes = $this->actingAs($this->admin)->get(route('admin.headers.print-preview', [
            'header' => $this->header->id,
            'include_background' => 1,
        ]));
        $adminRes->assertOk();

        $adminFinalRes = $this->actingAs($this->admin)->get(route('admin.headers.print-preview-final', [
            'header' => $this->header->id,
            'include_background' => 1,
        ]));
        $adminFinalRes->assertOk();

        // Supervisor
        $supRes = $this->actingAs($this->supervisor)->get(route('supervisor.submissions.print-preview', [
            'submission' => $this->submission->id,
            'include_background' => 1,
        ]));
        $supRes->assertOk();

        // Operator
        $opRes = $this->actingAs($this->operator)->get(route('operator.submissions.print-preview', [
            'submission' => $this->submission->id,
            'include_background' => 1,
        ]));
        $opRes->assertOk();
    }
}
