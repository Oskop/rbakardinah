<?php

namespace Tests\Feature\Admin;

use App\Models\PerformanceIndicator;
use App\Models\PerformanceIndicatorTarget;
use App\Models\PerformanceIndicatorTargetHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceIndicatorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Administrator',
            'is_active' => true,
        ]);

        $this->supervisor = User::factory()->create([
            'role' => 'Supervisor',
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'role' => 'Operator',
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_performance_indicators(): void
    {
        // Guest
        $this->get(route('admin.performance-indicators.index'))
            ->assertRedirect(route('login'));

        // Operator
        $this->actingAs($this->operator)
            ->get(route('admin.performance-indicators.index'))
            ->assertStatus(403);

        // Supervisor
        $this->actingAs($this->supervisor)
            ->get(route('admin.performance-indicators.index'))
            ->assertStatus(403);
    }

    public function test_admin_can_view_performance_indicators_index_with_matrix(): void
    {
        $indicator = PerformanceIndicator::create([
            'code' => 'IK-01',
            'name' => 'Tingkat Akreditasi RSUD Kardinah',
            'category' => 'Mutu & Keselamatan',
            'unit' => 'Predikat',
            'description' => 'Target penilaian akreditasi rumah sakit',
            'order' => 1,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        PerformanceIndicatorTarget::create([
            'performance_indicator_id' => $indicator->id,
            'year' => now()->year,
            'target_value' => 'Paripurna',
            'current_version' => 1,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.performance-indicators.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Indikator Kinerja RSUD Kardinah');
        $response->assertSee('IK-01');
        $response->assertSee('Tingkat Akreditasi RSUD Kardinah');
        $response->assertSee('Mutu & Keselamatan');
        $response->assertSee('Paripurna');
        $response->assertSee('V1');
    }

    public function test_admin_can_create_performance_indicator_parent(): void
    {
        $payload = [
            'code' => 'IK-02',
            'name' => 'Kepatuhan Hand Hygiene Tenaga Medis',
            'category' => 'Pelayanan Medik Bebas',
            'unit' => '%',
            'description' => 'Persentase kepatuhan cuci tangan dokter dan perawat',
            'order' => 2,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.performance-indicators.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('performance_indicators', [
            'code' => 'IK-02',
            'name' => 'Kepatuhan Hand Hygiene Tenaga Medis',
            'category' => 'Pelayanan Medik Bebas',
            'unit' => '%',
            'is_active' => 1,
            'created_by' => $this->admin->id,
        ]);

        $indicator = PerformanceIndicator::where('code', 'IK-02')->first();
        $this->assertNotNull($indicator->created_at);
        $this->assertNotNull($indicator->updated_at);
        $this->assertNull($indicator->deleted_at);
    }

    public function test_admin_can_update_performance_indicator(): void
    {
        $indicator = PerformanceIndicator::create([
            'code' => 'IK-03',
            'name' => 'Indeks Kepuasan Lama',
            'category' => 'Layanan Publik',
            'unit' => 'Skor',
            'order' => 3,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.performance-indicators.update', $indicator), [
                'code' => 'IK-03-REV',
                'name' => 'Indeks Kepuasan Masyarakat (IKM)',
                'category' => 'Layanan Pelanggan & Mutu',
                'unit' => 'Skala 100',
                'order' => 5,
                'is_active' => '1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('performance_indicators', [
            'id' => $indicator->id,
            'code' => 'IK-03-REV',
            'name' => 'Indeks Kepuasan Masyarakat (IKM)',
            'category' => 'Layanan Pelanggan & Mutu',
        ]);
    }

    public function test_admin_can_toggle_indicator_status(): void
    {
        $indicator = PerformanceIndicator::create([
            'name' => 'Indikator Uji Coba Status',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // Toggle to Inactive
        $this->actingAs($this->admin)
            ->post(route('admin.performance-indicators.toggle-status', $indicator))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($indicator->fresh()->is_active);

        // Toggle back to Active
        $this->actingAs($this->admin)
            ->post(route('admin.performance-indicators.toggle-status', $indicator))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($indicator->fresh()->is_active);
    }

    public function test_admin_can_soft_delete_performance_indicator(): void
    {
        $indicator = PerformanceIndicator::create([
            'name' => 'Indikator Dihapus',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $target = PerformanceIndicatorTarget::create([
            'performance_indicator_id' => $indicator->id,
            'year' => 2026,
            'target_value' => '85%',
            'current_version' => 1,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.performance-indicators.destroy', $indicator))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Pastikan soft delete (tidak hard delete)
        $this->assertSoftDeleted('performance_indicators', ['id' => $indicator->id]);
        $this->assertSoftDeleted('performance_indicator_targets', ['id' => $target->id]);
    }

    public function test_admin_can_create_and_version_target_with_audit_trail(): void
    {
        $indicator = PerformanceIndicator::create([
            'name' => 'Tingkat Kelulusan Standar RS',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $year = 2025;

        // 1. Input Nilai Target Pertama Kali (Versi 1)
        $response1 = $this->actingAs($this->admin)
            ->post(route('admin.performance-indicators.targets.update', [
                'performance_indicator' => $indicator->id,
                'year' => $year,
            ]), [
                'target_value' => 'Madya',
                'change_note' => 'Penetapan target awal Renstra 2025',
            ]);

        $response1->assertRedirect();

        $this->assertDatabaseHas('performance_indicator_targets', [
            'performance_indicator_id' => $indicator->id,
            'year' => $year,
            'target_value' => 'Madya',
            'current_version' => 1,
        ]);

        $this->assertDatabaseHas('performance_indicator_target_histories', [
            'performance_indicator_id' => $indicator->id,
            'year' => $year,
            'version_number' => 1,
            'old_value' => null,
            'new_value' => 'Madya',
            'change_note' => 'Penetapan target awal Renstra 2025',
            'user_id' => $this->admin->id,
        ]);

        // 2. Edit Nilai Target (Menghasilkan Versi 2)
        $response2 = $this->actingAs($this->admin)
            ->post(route('admin.performance-indicators.targets.update', [
                'performance_indicator' => $indicator->id,
                'year' => $year,
            ]), [
                'target_value' => 'Paripurna',
                'change_note' => 'Revisi peningkatan target sesuai SK Direktur',
            ]);

        $response2->assertRedirect();

        $this->assertDatabaseHas('performance_indicator_targets', [
            'performance_indicator_id' => $indicator->id,
            'year' => $year,
            'target_value' => 'Paripurna',
            'current_version' => 2,
            'updated_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('performance_indicator_target_histories', [
            'performance_indicator_id' => $indicator->id,
            'year' => $year,
            'version_number' => 2,
            'old_value' => 'Madya',
            'new_value' => 'Paripurna',
            'change_note' => 'Revisi peningkatan target sesuai SK Direktur',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_batch_update_targets(): void
    {
        $indicator = PerformanceIndicator::create([
            'name' => 'Waktu Tunggu Rawat Jalan',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $payload = [
            'targets' => [
                2024 => '≤ 60 Menit',
                2025 => '≤ 55 Menit',
                2026 => '≤ 50 Menit',
                2027 => '≤ 45 Menit',
                2028 => '≤ 40 Menit',
            ],
            'change_note' => 'Batch setup target 5 tahunan',
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.performance-indicators.targets.batch', $indicator), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ($payload['targets'] as $yr => $val) {
            $this->assertDatabaseHas('performance_indicator_targets', [
                'performance_indicator_id' => $indicator->id,
                'year' => $yr,
                'target_value' => $val,
            ]);
        }
    }

    public function test_admin_can_fetch_target_history_json(): void
    {
        $indicator = PerformanceIndicator::create([
            'code' => 'IK-99',
            'name' => 'Audit Riwayat',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $target = PerformanceIndicatorTarget::create([
            'performance_indicator_id' => $indicator->id,
            'year' => 2025,
            'target_value' => 'Versi 2 Value',
            'current_version' => 2,
            'created_by' => $this->admin->id,
        ]);

        PerformanceIndicatorTargetHistory::create([
            'target_id' => $target->id,
            'performance_indicator_id' => $indicator->id,
            'year' => 2025,
            'version_number' => 1,
            'old_value' => null,
            'new_value' => 'Versi 1 Value',
            'change_note' => 'Awal',
            'user_id' => $this->admin->id,
        ]);

        PerformanceIndicatorTargetHistory::create([
            'target_id' => $target->id,
            'performance_indicator_id' => $indicator->id,
            'year' => 2025,
            'version_number' => 2,
            'old_value' => 'Versi 1 Value',
            'new_value' => 'Versi 2 Value',
            'change_note' => 'Edit kedua',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.performance-indicators.targets.history', [
                'performance_indicator' => $indicator->id,
                'year' => 2025,
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'indicator_name' => 'Audit Riwayat',
            'year' => 2025,
            'current_value' => 'Versi 2 Value',
            'current_version' => 2,
        ]);
        $response->assertJsonCount(2, 'histories');
    }
}
