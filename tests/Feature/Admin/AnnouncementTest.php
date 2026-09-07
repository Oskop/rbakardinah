<?php

namespace Tests\Feature\Admin;

use App\Models\Announcement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $operator;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create([
            'code' => 'UPT01',
            'name' => 'Unit Pelayanan Teknis',
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
    }

    public function test_admin_can_view_announcements_index(): void
    {
        $announcement = Announcement::create([
            'title' => 'Pengumuman Maintenance Server',
            'content' => 'Server akan dimaintenance pada malam hari ini.',
            'type' => 'warning',
            'target_type' => 'all',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHours(2),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.announcements.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Pengumuman Sistem');
        $response->assertSee('Pengumuman Maintenance Server');
        $response->assertSee('Sedang Tayang');
    }

    public function test_admin_can_filter_announcements_by_status(): void
    {
        // 1. Sedang tayang (target all_operators agar tidak ter-render di banner admin layout)
        Announcement::create([
            'title' => 'Pengumuman Sedang Tayang',
            'content' => 'Isi pesan tayang',
            'type' => 'info',
            'target_type' => 'all_operators',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHours(2),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // 2. Terjadwal masa depan
        Announcement::create([
            'title' => 'Pengumuman Masa Depan',
            'content' => 'Isi pesan masa depan',
            'type' => 'info',
            'target_type' => 'all',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // 3. Telah berakhir
        Announcement::create([
            'title' => 'Pengumuman Telah Berakhir',
            'content' => 'Isi pesan telah lewat',
            'type' => 'danger',
            'target_type' => 'all',
            'start_at' => now()->subDays(3),
            'end_at' => now()->subDay(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // Filter: running
        $responseRunning = $this->actingAs($this->admin)->get(route('admin.announcements.index', ['status' => 'running']));
        $responseRunning->assertStatus(200);
        $responseRunning->assertSee('Pengumuman Sedang Tayang');
        $responseRunning->assertDontSee('Pengumuman Masa Depan');
        $responseRunning->assertDontSee('Pengumuman Telah Berakhir');

        // Filter: scheduled
        $responseScheduled = $this->actingAs($this->admin)->get(route('admin.announcements.index', ['status' => 'scheduled']));
        $responseScheduled->assertStatus(200);
        $responseScheduled->assertSee('Pengumuman Masa Depan');
        $responseScheduled->assertDontSee('Pengumuman Sedang Tayang');

        // Filter: expired
        $responseExpired = $this->actingAs($this->admin)->get(route('admin.announcements.index', ['status' => 'expired']));
        $responseExpired->assertStatus(200);
        $responseExpired->assertSee('Pengumuman Telah Berakhir');
    }

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.announcements.create'));

        $response->assertStatus(200);
        $response->assertSee('Buat Pengumuman Baru');
        $response->assertSee('Pintasan Durasi Cepat');
        $response->assertSee('+15 Menit');
        $response->assertSee('+1 Jam');
    }

    public function test_admin_can_create_announcement_with_all_target(): void
    {
        $payload = [
            'title' => 'Info Pembaruan Aplikasi',
            'content' => 'Sistem telah diperbarui ke versi terbaru.',
            'type' => 'info',
            'target_type' => 'all',
            'start_at' => now()->format('Y-m-d H:i:s'),
            'end_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.announcements.store'), $payload);

        $response->assertRedirect(route('admin.announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'title' => 'Info Pembaruan Aplikasi',
            'target_type' => 'all',
            'type' => 'info',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_create_announcement_with_specific_users(): void
    {
        $payload = [
            'title' => 'Peringatan Khusus Supervisor & Operator Tertentu',
            'content' => 'Harap segera melakukan submit rincian belanja.',
            'type' => 'warning',
            'target_type' => 'specific_users',
            'start_at' => now()->format('Y-m-d H:i:s'),
            'end_at' => now()->addHours(5)->format('Y-m-d H:i:s'),
            'is_active' => 1,
            'target_user_ids' => [$this->supervisor->id, $this->operator->id],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.announcements.store'), $payload);

        $response->assertRedirect(route('admin.announcements.index'));

        $announcement = Announcement::where('title', 'Peringatan Khusus Supervisor & Operator Tertentu')->first();
        $this->assertNotNull($announcement);
        $this->assertEquals(2, $announcement->targetUsers()->count());
        $this->assertTrue($announcement->targetUsers->contains($this->supervisor->id));
        $this->assertTrue($announcement->targetUsers->contains($this->operator->id));
    }

    public function test_admin_can_view_and_update_announcement(): void
    {
        $announcement = Announcement::create([
            'title' => 'Judul Awal',
            'content' => 'Isi awal',
            'type' => 'info',
            'target_type' => 'all_supervisors',
            'start_at' => now(),
            'end_at' => now()->addDay(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.announcements.edit', $announcement));
        $response->assertStatus(200);
        $response->assertSee('Edit Pengumuman');
        $response->assertSee('Judul Awal');

        $updatePayload = [
            'title' => 'Judul Telah Direvisi',
            'content' => 'Isi telah diperbarui',
            'type' => 'danger',
            'target_type' => 'all_operators',
            'start_at' => now()->format('Y-m-d H:i:s'),
            'end_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'is_active' => 1,
        ];

        $updateResponse = $this->actingAs($this->admin)->put(route('admin.announcements.update', $announcement), $updatePayload);
        $updateResponse->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Judul Telah Direvisi',
            'type' => 'danger',
            'target_type' => 'all_operators',
        ]);
    }

    public function test_admin_can_toggle_active_status(): void
    {
        $announcement = Announcement::create([
            'title' => 'Pengumuman Uji Coba Toggle',
            'content' => 'Isi',
            'type' => 'info',
            'target_type' => 'all',
            'start_at' => now(),
            'end_at' => now()->addDay(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // Toggle to inactive (sembunyikan)
        $response1 = $this->actingAs($this->admin)->post(route('admin.announcements.toggle-active', $announcement));
        $response1->assertRedirect();
        $this->assertFalse($announcement->fresh()->is_active);

        // Toggle to active (aktifkan kembali)
        $response2 = $this->actingAs($this->admin)->post(route('admin.announcements.toggle-active', $announcement));
        $response2->assertRedirect();
        $this->assertTrue($announcement->fresh()->is_active);
    }

    public function test_admin_can_force_end_announcement(): void
    {
        $announcement = Announcement::create([
            'title' => 'Pengumuman Durasi Panjang',
            'content' => 'Isi',
            'type' => 'warning',
            'target_type' => 'all',
            'start_at' => now()->subDay(),
            'end_at' => now()->addYear(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->assertTrue($announcement->isCurrentlyActive());

        $response = $this->actingAs($this->admin)->post(route('admin.announcements.force-end', $announcement));
        $response->assertRedirect();

        $fresh = $announcement->fresh();
        $this->assertNotNull($fresh->end_at);
        $this->assertTrue($fresh->end_at->lte(now()));
        $this->assertEquals('expired', $fresh->status);
    }

    public function test_admin_can_delete_announcement(): void
    {
        $announcement = Announcement::create([
            'title' => 'Pengumuman Akan Dihapus',
            'content' => 'Isi',
            'type' => 'info',
            'target_type' => 'all',
            'start_at' => now(),
            'end_at' => now()->addDay(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.announcements.destroy', $announcement));
        $response->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseMissing('announcements', [
            'id' => $announcement->id,
        ]);
    }

    public function test_non_admin_cannot_access_announcements_management(): void
    {
        $responseSupervisor = $this->actingAs($this->supervisor)->get(route('admin.announcements.index'));
        $responseSupervisor->assertStatus(403);

        $responseOperator = $this->actingAs($this->operator)->get(route('admin.announcements.create'));
        $responseOperator->assertStatus(403);
    }

    public function test_announcement_banner_targeting_and_scheduling(): void
    {
        // 1. Pengumuman untuk Supervisor saja
        $announcementSupervisor = Announcement::create([
            'title' => 'Khusus Supervisor Validasi',
            'content' => 'Pesan untuk seluruh supervisor',
            'type' => 'info',
            'target_type' => 'all_supervisors',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHours(2),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // 2. Pengumuman untuk Operator saja
        $announcementOperator = Announcement::create([
            'title' => 'Khusus Operator Input Belanja',
            'content' => 'Pesan untuk seluruh operator',
            'type' => 'warning',
            'target_type' => 'all_operators',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHours(2),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // 3. Pengumuman masa depan (belum aktif)
        $announcementFuture = Announcement::create([
            'title' => 'Pengumuman Terjadwal Besok',
            'content' => 'Belum boleh tampil',
            'type' => 'danger',
            'target_type' => 'all',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // 4. Pengumuman spesifik untuk Operator
        $announcementSpecific = Announcement::create([
            'title' => 'Peringatan Personal Operator Ini',
            'content' => 'Pesan khusus',
            'type' => 'danger',
            'target_type' => 'specific_users',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHours(2),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
        $announcementSpecific->targetUsers()->attach($this->operator->id);

        // Supervisor Login Dashboard:
        // Harus melihat $announcementSupervisor
        // TIDAK boleh melihat $announcementOperator, $announcementFuture, atau $announcementSpecific
        $respSpv = $this->actingAs($this->supervisor)->get(route('supervisor.dashboard'));
        $respSpv->assertStatus(200);
        $respSpv->assertSee('Khusus Supervisor Validasi');
        $respSpv->assertDontSee('Khusus Operator Input Belanja');
        $respSpv->assertDontSee('Pengumuman Terjadwal Besok');
        $respSpv->assertDontSee('Peringatan Personal Operator Ini');

        // Operator Login Dashboard:
        // Harus melihat $announcementOperator dan $announcementSpecific
        // TIDAK boleh melihat $announcementSupervisor atau $announcementFuture
        $respOpr = $this->actingAs($this->operator)->get(route('operator.dashboard'));
        $respOpr->assertStatus(200);
        $respOpr->assertSee('Khusus Operator Input Belanja');
        $respOpr->assertSee('Peringatan Personal Operator Ini');
        $respOpr->assertDontSee('Khusus Supervisor Validasi');
        $respOpr->assertDontSee('Pengumuman Terjadwal Besok');
    }

    public function test_admin_can_reshow_announcement(): void
    {
        $announcement = Announcement::create([
            'title' => 'Pengumuman Penting Deadline RBA',
            'content' => 'Batas waktu penginputan adalah hari ini.',
            'type' => 'warning',
            'target_type' => 'all',
            'start_at' => now()->subHours(2),
            'end_at' => now()->addHours(5),
            'is_active' => true,
            'reshown_at' => null,
            'created_by' => $this->admin->id,
        ]);

        $initialDismissKey = $announcement->dismiss_key;
        $this->assertNull($announcement->reshown_at);

        // Simulasi waktu maju 1 detik agar timestamp berbeda
        $this->travel(2)->seconds();

        $response = $this->actingAs($this->admin)->post(route('admin.announcements.reshow', $announcement));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $announcement->fresh();
        $this->assertNotNull($fresh->reshown_at);
        $this->assertTrue($fresh->is_active);
        $this->assertNotEquals($initialDismissKey, $fresh->dismiss_key);
    }

    public function test_non_admin_cannot_reshow_announcement(): void
    {
        $announcement = Announcement::create([
            'title' => 'Pengumuman Penting',
            'content' => 'Isi',
            'type' => 'warning',
            'target_type' => 'all',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHours(2),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->supervisor)->post(route('admin.announcements.reshow', $announcement));
        $response->assertStatus(403);
    }
}
