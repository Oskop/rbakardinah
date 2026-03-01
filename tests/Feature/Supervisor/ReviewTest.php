<?php

namespace Tests\Feature\Supervisor;

use App\Models\User;
use App\Models\Unit;
use App\Models\RbaPeriod;
use App\Models\RbaHeader;
use App\Models\RbaSubmission;
use App\Models\AccountCode;
use App\Models\KelompokBelanja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected $supervisor;
    protected $submission;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = Unit::create(['code' => 'U01', 'name' => 'Unit Test']);
        $group = \App\Models\KelompokBelanja::create(['name' => 'Test Group']);
        $accountCode = AccountCode::create([
            'kelompok_belanja_id' => $group->id,
            'code' => '5.1.01',
            'name' => 'Belanja Gaji'
        ]);
        $this->supervisor = User::factory()->create(['role' => 'Supervisor', 'unit_id' => $unit->id]);

        $admin = User::factory()->create(['role' => 'Administrator']);
        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'period_id' => $period->id,
            'year' => 2026,
            'admin_id' => $admin->id,
            'status_global' => 'Active'
        ]);

        $this->submission = RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $unit->id,
            'status_submission' => 'Pending Supervisor'
        ]);
    }

    public function test_supervisor_can_view_their_unit_submissions()
    {
        $response = $this->actingAs($this->supervisor)->get(route('supervisor.submissions.index'));
        $response->assertStatus(200);
        $response->assertSee('Murni');
    }

    public function test_supervisor_can_validate_submission()
    {
        $response = $this->actingAs($this->supervisor)->post(route('supervisor.submissions.validate', $this->submission));

        $response->assertRedirect(route('supervisor.submissions.index'));
        $this->assertEquals('Validated', $this->submission->fresh()->status_submission);
    }
}
