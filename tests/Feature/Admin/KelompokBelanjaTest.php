<?php

namespace Tests\Feature\Admin;

use App\Models\KelompokBelanja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelompokBelanjaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_kelompok_belanja_with_kode_and_name(): void
    {
        $admin = User::factory()->create(['role' => 'Administrator']);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.kelompok-belanja.store'), [
                'kode' => '5.1.04',
                'name' => 'Belanja Lain-lain',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.kelompok-belanja.index'));

        $this->assertDatabaseHas('kelompok_belanjas', [
            'kode' => '5.1.04',
            'name' => 'Belanja Lain-lain',
        ]);
    }

    public function test_admin_can_update_kelompok_belanja_kode_and_name(): void
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $group = KelompokBelanja::create(['kode' => '5.1.04', 'name' => 'Belanja Temp']);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.kelompok-belanja.update', $group), [
                'kode' => '5.1.05',
                'name' => 'Belanja Lain-lain Updated',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.kelompok-belanja.index'));

        $this->assertDatabaseHas('kelompok_belanjas', [
            'id' => $group->id,
            'kode' => '5.1.05',
            'name' => 'Belanja Lain-lain Updated',
        ]);
    }
}
