<?php

namespace Tests\Feature\Supervisor;

use App\Models\User;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_access_dashboard_and_see_rba_list()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $supervisor = User::factory()->create(['role' => 'Supervisor']);
        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $response = $this->actingAs($supervisor)->get(route('supervisor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Supervisor Dashboard - SIPAKAR RBA');
        $response->assertSee('Daftar RBA Historis');
        $response->assertSee('Review Usulan RBA');
        $response->assertSee('Kelola User');
    }
}
