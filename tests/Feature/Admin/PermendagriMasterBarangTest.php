<?php

namespace Tests\Feature\Admin;

use App\Models\MasterBarang;
use App\Models\User;
use Database\Seeders\Permendagri108MasterBarangSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PermendagriMasterBarangTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'Administrator']);
    }

    public function test_master_barangs_table_has_kode_permendagri_column(): void
    {
        $this->assertTrue(Schema::hasColumn('master_barangs', 'kode_permendagri'));
    }

    public function test_admin_can_create_master_barang_with_kode_permendagri(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.master-barangs.store'), [
            'kode_barang' => '1.3.2.10.01.02.001',
            'kode_permendagri' => '1.3.2.10.01.02.001',
            'nama_barang' => 'Personal Computer Desktop Unit',
            'satuan' => 'Unit',
            'deskripsi' => 'Komputer meja kantor',
        ]);

        $response->assertRedirect(route('admin.master-barangs.index'));
        $this->assertDatabaseHas('master_barangs', [
            'kode_barang' => '1.3.2.10.01.02.001',
            'kode_permendagri' => '1.3.2.10.01.02.001',
            'nama_barang' => 'Personal Computer Desktop Unit',
        ]);
    }

    public function test_admin_can_update_master_barang_with_kode_permendagri(): void
    {
        $barang = MasterBarang::create([
            'kode_barang' => '1.3.2.07.01.01.001',
            'kode_permendagri' => null,
            'nama_barang' => 'Tensimeter Standar',
            'satuan' => 'Unit',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.master-barangs.update', $barang), [
            'kode_barang' => '1.3.2.07.01.01.001',
            'kode_permendagri' => '1.3.2.07.01.01.001',
            'nama_barang' => 'Tensimeter Digital Presisi Medis',
            'satuan' => 'Unit',
            'deskripsi' => 'Alat ukur tekanan darah digital',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.master-barangs.index'));
        $this->assertDatabaseHas('master_barangs', [
            'id' => $barang->id,
            'kode_permendagri' => '1.3.2.07.01.01.001',
            'nama_barang' => 'Tensimeter Digital Presisi Medis',
        ]);
    }

    public function test_scope_search_matches_kode_permendagri(): void
    {
        MasterBarang::create([
            'kode_barang' => 'BRG-001',
            'kode_permendagri' => '1.1.7.01.01.01.001',
            'nama_barang' => 'Aspal Hotmix',
            'satuan' => 'Drum',
            'is_active' => true,
        ]);

        $this->assertEquals(1, MasterBarang::search('1.1.7.01.01.01.001')->count());
        $this->assertEquals(1, MasterBarang::search('BRG-001')->count());
        $this->assertEquals(1, MasterBarang::search('Aspal')->count());
        $this->assertEquals(0, MasterBarang::search('TidakAdaBarang')->count());
    }

    public function test_artisan_command_and_seeder_are_idempotent(): void
    {
        // Jalankan seeder pertama kali
        $this->artisan('rkbmd:import-permendagri')
            ->assertSuccessful();

        $countAfterFirst = MasterBarang::count();
        $this->assertGreaterThan(10000, $countAfterFirst, 'Total baris terimpor harus lebih dari 10.000 item.');

        // Verifikasi sampel data medis Level 7 tersimpan dengan kode referensi
        $sampleMedis = MasterBarang::where('kode_permendagri', '1.3.2.07.01.01.001')->first();
        $this->assertNotNull($sampleMedis);
        $this->assertEquals('1.3.2.07.01.01.001', $sampleMedis->kode_permendagri);

        // Verifikasi sampel kode duplikat (misal 1.3.2.05.03.04.002)
        $duplicateGroup = MasterBarang::where('kode_permendagri', '1.3.2.05.03.04.002')->get();
        $this->assertCount(2, $duplicateGroup, 'Kode duplikat Permendagri harus tersimpan keduanya.');
        $this->assertEquals('1.3.2.05.03.04.002', $duplicateGroup[0]->kode_permendagri);
        $this->assertEquals('1.3.2.05.03.04.002', $duplicateGroup[1]->kode_permendagri);
        // Pastikan kode_barang unik (salah satu ber-postfix .1)
        $this->assertNotEquals($duplicateGroup[0]->kode_barang, $duplicateGroup[1]->kode_barang);

        // Jalankan seeder kedua kali (uji idempotensi - tidak boleh ada error atau duplicate key violation)
        $this->artisan('rkbmd:import-permendagri')
            ->assertSuccessful();

        $countAfterSecond = MasterBarang::count();
        $this->assertEquals($countAfterFirst, $countAfterSecond, 'Menjalankan ulang seeder tidak boleh menambah baris duplikat.');
    }
}
