<?php

namespace Tests\Feature\Admin;

use App\Models\HardDeleteArchive;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use App\Models\SystemSetting;
use App\Models\Unit;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected RbaPeriod $period;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create([
            'name' => 'Unit Pelayanan Medis',
            'code' => 'UPM',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'admin_setting_test@hospital.com',
            'role' => 'Administrator',
            'unit_id' => $this->unit->id,
        ]);

        $this->operator = User::factory()->create([
            'name' => 'Operator Setting Test',
            'email' => 'operator_setting_test@hospital.com',
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
        ]);

        $this->period = RbaPeriod::create([
            'name' => 'RBA Murni 2026',
            'year' => 2026,
            'status' => 'Active',
        ]);
    }

    public function test_admin_can_access_system_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));
        $response->assertOk();
        $response->assertSee('Pengaturan Sistem & Kebijakan Keamanan');
        $response->assertSee('Proteksi Tindakan Hapus RBA Header');
    }

    public function test_non_admin_cannot_access_system_settings_page(): void
    {
        $response = $this->actingAs($this->operator)->get(route('admin.settings.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_toggle_rba_deletion_setting_and_logs_activity(): void
    {
        // 1. Enable RBA Deletion
        $responseEnable = $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'allow_rba_deletion' => '1',
        ]);

        $responseEnable->assertRedirect(route('admin.settings.index'));
        $responseEnable->assertSessionHas('success');
        $this->assertTrue(SystemSetting::isRbaDeletionAllowed());

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'updated',
            'model_type' => SystemSetting::class,
        ]);

        // 2. Disable / Lock RBA Deletion
        $responseDisable = $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'allow_rba_deletion' => '0',
        ]);

        $responseDisable->assertRedirect(route('admin.settings.index'));
        $responseDisable->assertSessionHas('success');
        $this->assertFalse(SystemSetting::isRbaDeletionAllowed());
    }

    public function test_rba_headers_index_hides_delete_button_when_deletion_is_disabled(): void
    {
        SystemSetting::set('allow_rba_deletion', false, $this->admin->id);

        $header = RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'Draft',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.headers.index'));
        $response->assertOk();
        $response->assertDontSee('Hapus RBA');
        $response->assertDontSee('Mode Pembersihan Aktif');
    }

    public function test_rba_headers_index_shows_delete_button_and_warning_when_deletion_is_enabled(): void
    {
        SystemSetting::set('allow_rba_deletion', true, $this->admin->id);

        $header = RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'Draft',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.headers.index'));
        $response->assertOk();
        $response->assertSee('Hapus RBA');
        $response->assertSee('Mode Pembersihan Aktif');
    }

    public function test_delete_rba_header_is_blocked_at_controller_when_setting_is_disabled(): void
    {
        SystemSetting::set('allow_rba_deletion', false, $this->admin->id);

        $header = RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'Draft',
        ]);
        $headerId = $header->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.headers.destroy', $header), [
            'reason' => 'Mencoba bypass',
        ]);

        $response->assertRedirect(route('admin.headers.index'));
        $response->assertSessionHas('error');

        // Header must still exist in operational tables!
        $this->assertDatabaseHas('rba_headers', ['id' => $headerId]);
        $this->assertDatabaseMissing('hard_delete_archives', ['model_id' => $headerId]);
    }

    public function test_delete_rba_header_succeeds_when_setting_is_enabled(): void
    {
        SystemSetting::set('allow_rba_deletion', true, $this->admin->id);

        $header = RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'Draft',
        ]);
        $headerId = $header->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.headers.destroy', $header), [
            'reason' => 'Penghapusan dengan izin aktif',
        ]);

        $response->assertRedirect(route('admin.headers.index'));
        $response->assertSessionHas('success');

        // Header must be removed from operational table and moved to archive
        $this->assertDatabaseMissing('rba_headers', ['id' => $headerId]);
        $this->assertDatabaseHas('hard_delete_archives', ['model_id' => $headerId]);
    }

    public function test_artisan_toggle_command_changes_system_setting(): void
    {
        $this->artisan('rba:toggle-deletion enable')
            ->assertExitCode(0);
        $this->assertTrue(SystemSetting::isRbaDeletionAllowed());

        $this->artisan('rba:toggle-deletion disable')
            ->assertExitCode(0);
        $this->assertFalse(SystemSetting::isRbaDeletionAllowed());

        // Toggle invert without argument
        $this->artisan('rba:toggle-deletion')
            ->assertExitCode(0);
        $this->assertTrue(SystemSetting::isRbaDeletionAllowed());
    }
}
