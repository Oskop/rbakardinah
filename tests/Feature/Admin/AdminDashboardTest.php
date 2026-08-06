<?php

namespace Tests\Feature\Admin;

use App\Models\User;
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
}
