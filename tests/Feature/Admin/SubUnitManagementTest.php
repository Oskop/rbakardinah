<?php

namespace Tests\Feature\Admin;

use App\Models\SubUnit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubUnitManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $operator;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create(['code' => 'U004', 'name' => 'Unit Perencanaan dan Pemasaran', 'is_active' => true]);

        $this->admin = User::factory()->create([
            'role' => 'Administrator',
            'email' => 'admin.sub@hospital.com',
            'is_active' => true,
        ]);

        $this->supervisor = User::factory()->create([
            'role' => 'Supervisor',
            'email' => 'supervisor.sub@hospital.com',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'role' => 'Operator',
            'email' => 'operator.sub@hospital.com',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_sub_units_index_page()
    {
        $subUnit = SubUnit::create([
            'unit_id' => $this->unit->id,
            'code' => 'SUB-REN-01',
            'name' => 'Sub Bag. Perencanaan dan Evaluasi',
            'type' => 'Sub Bagian',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.sub-units.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Sub-Unit Kerja');
        $response->assertSee('Sub Bag. Perencanaan dan Evaluasi');
        $response->assertSee('SUB-REN-01');
        $response->assertSee('Unit Perencanaan dan Pemasaran');
    }

    public function test_admin_can_create_new_sub_unit()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sub-units.store'), [
            'unit_id' => $this->unit->id,
            'code' => 'SUB-REN-03',
            'name' => 'Unit PDE',
            'type' => 'Unit',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.sub-units.index'));
        $this->assertDatabaseHas('sub_units', [
            'code' => 'SUB-REN-03',
            'name' => 'Unit PDE',
            'type' => 'Unit',
            'unit_id' => $this->unit->id,
        ]);
    }

    public function test_admin_can_update_sub_unit()
    {
        $subUnit = SubUnit::create([
            'unit_id' => $this->unit->id,
            'code' => 'SUB-REN-01',
            'name' => 'Sub Bag. Perencanaan',
            'type' => 'Sub Bagian',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.sub-units.update', $subUnit), [
            'unit_id' => $this->unit->id,
            'code' => 'SUB-REN-01-REV',
            'name' => 'Sub Bag. Perencanaan dan Evaluasi Baru',
            'type' => 'Sub Bagian',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.sub-units.index'));
        $this->assertDatabaseHas('sub_units', [
            'id' => $subUnit->id,
            'code' => 'SUB-REN-01-REV',
            'name' => 'Sub Bag. Perencanaan dan Evaluasi Baru',
        ]);
    }

    public function test_admin_can_toggle_sub_unit_status()
    {
        $subUnit = SubUnit::create([
            'unit_id' => $this->unit->id,
            'code' => 'SUB-REN-01',
            'name' => 'Sub Bag. Perencanaan',
            'type' => 'Sub Bagian',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.sub-units.destroy', $subUnit));

        $response->assertRedirect(route('admin.sub-units.index'));
        $this->assertFalse($subUnit->fresh()->is_active);

        // Toggle back to active
        $this->actingAs($this->admin)->delete(route('admin.sub-units.destroy', $subUnit));
        $this->assertTrue($subUnit->fresh()->is_active);
    }

    public function test_ajax_get_sub_units_by_unit()
    {
        $subUnit1 = SubUnit::create([
            'unit_id' => $this->unit->id,
            'code' => 'SUB-REN-01',
            'name' => 'Sub Bag. Perencanaan',
            'type' => 'Sub Bagian',
            'is_active' => true,
        ]);

        $otherUnit = Unit::create(['code' => 'U005', 'name' => 'Unit Umum', 'is_active' => true]);
        $subUnit2 = SubUnit::create([
            'unit_id' => $otherUnit->id,
            'code' => 'SUB-UMU-01',
            'name' => 'Sub Bag. TU',
            'type' => 'Sub Bagian',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.units.sub-units', $this->unit));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $subUnit1->id,
            'name' => 'Sub Bag. Perencanaan',
        ]);
        $response->assertJsonMissing([
            'name' => 'Sub Bag. TU',
        ]);
    }

    public function test_admin_can_view_organizational_chart()
    {
        $subUnit = SubUnit::create([
            'unit_id' => $this->unit->id,
            'code' => 'SUB-REN-03',
            'name' => 'Unit PDE',
            'type' => 'Unit',
            'is_active' => true,
        ]);

        $this->operator->update([
            'sub_unit_id' => $subUnit->id,
            'jabatan' => 'Pranata Komputer Ahli Pertama',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.org-chart.index'));

        $response->assertStatus(200);
        $response->assertSee('Bagan Struktur Organisasi RSUD Kardinah');
        $response->assertSee('Dewan Direksi & Administrator Sistem', false);
        $response->assertSee('Unit Perencanaan dan Pemasaran');
        $response->assertSee('Unit PDE');
        $response->assertSee($this->operator->name);
        $response->assertSee('Pranata Komputer Ahli Pertama');
    }

    public function test_admin_can_create_user_with_sub_unit_and_jabatan()
    {
        $subUnit = SubUnit::create([
            'unit_id' => $this->unit->id,
            'code' => 'SUB-REN-03',
            'name' => 'Unit PDE',
            'type' => 'Unit',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Ahmad Fauzi, S.Kom',
            'email' => 'ahmad.fauzi@hospital.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
            'sub_unit_id' => $subUnit->id,
            'jabatan' => 'Pranata Komputer Ahli Pertama',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Ahmad Fauzi, S.Kom',
            'sub_unit_id' => $subUnit->id,
            'jabatan' => 'Pranata Komputer Ahli Pertama',
            'unit_id' => $this->unit->id,
        ]);
    }

    public function test_supervisor_can_assign_sub_unit_and_jabatan_to_operator()
    {
        $subUnit = SubUnit::create([
            'unit_id' => $this->unit->id,
            'code' => 'SUB-REN-03',
            'name' => 'Unit PDE',
            'type' => 'Unit',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->supervisor)->put(route('supervisor.users.update', $this->operator), [
            'name' => $this->operator->name,
            'email' => $this->operator->email,
            'is_active' => 1,
            'sub_unit_id' => $subUnit->id,
            'jabatan' => 'Staf PDE',
        ]);

        $response->assertRedirect(route('supervisor.users.index'));
        $this->assertEquals($subUnit->id, $this->operator->fresh()->sub_unit_id);
        $this->assertEquals('Staf PDE', $this->operator->fresh()->jabatan);
    }

    public function test_supervisor_cannot_assign_sub_unit_from_different_unit()
    {
        $otherUnit = Unit::create(['code' => 'U005', 'name' => 'Unit Umum', 'is_active' => true]);
        $foreignSubUnit = SubUnit::create([
            'unit_id' => $otherUnit->id,
            'code' => 'SUB-UMU-01',
            'name' => 'Sub Bag. TU',
            'type' => 'Sub Bagian',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->supervisor)->put(route('supervisor.users.update', $this->operator), [
            'name' => $this->operator->name,
            'email' => $this->operator->email,
            'is_active' => 1,
            'sub_unit_id' => $foreignSubUnit->id,
            'jabatan' => 'Staf TU',
        ]);

        $response->assertSessionHasErrors('sub_unit_id');
        $this->assertNull($this->operator->fresh()->sub_unit_id);
    }

    public function test_operator_cannot_access_sub_units_management()
    {
        $response = $this->actingAs($this->operator)->get(route('admin.sub-units.index'));
        $response->assertStatus(403);
    }
}
