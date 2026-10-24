<?php

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterBarangCategoryAndSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Administrator',
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'role' => 'Operator',
            'can_propose' => true,
            'is_active' => true,
        ]);

        // Siapkan sampel barang dengan variasi penamaan Permendagri 108
        MasterBarang::create([
            'kode_barang' => '1.3.2.10.01.02.002',
            'kode_permendagri' => '1.3.2.10.01.02.002',
            'kode_level5' => '1.3.2.10.01',
            'nama_level5' => 'KOMPUTER UNIT',
            'kode_level6' => '1.3.2.10.01.02',
            'nama_level6' => 'PERSONAL KOMPUTER',
            'nama_barang' => 'Lap Top',
            'satuan' => 'Unit',
            'deskripsi' => 'KOMPUTER UNIT > PERSONAL KOMPUTER',
            'is_active' => true,
        ]);

        MasterBarang::create([
            'kode_barang' => '1.3.2.15.03.03.041',
            'kode_permendagri' => '1.3.2.15.03.03.041',
            'kode_level5' => '1.3.2.15.03',
            'nama_level5' => 'ALAT SAR',
            'kode_level6' => '1.3.2.15.03.03',
            'nama_level6' => 'ALAT KERJA BAWAH AIR',
            'nama_barang' => 'Tas Laptop, Kedap Air Untuk Membawa Laptop',
            'satuan' => 'Buah',
            'deskripsi' => 'ALAT SAR > ALAT KERJA BAWAH AIR',
            'is_active' => true,
        ]);

        MasterBarang::create([
            'kode_barang' => '1.3.2.05.02.06.026',
            'kode_permendagri' => '1.3.2.05.02.06.026',
            'kode_level5' => '1.3.2.05.02',
            'nama_level5' => 'PERALATAN KANTOR',
            'kode_level6' => '1.3.2.05.02.06',
            'nama_level6' => 'ALAT TULIS DAN PAPAN TULIS',
            'nama_barang' => 'White Board',
            'satuan' => 'Buah',
            'deskripsi' => 'PERALATAN KANTOR > ALAT TULIS DAN PAPAN TULIS',
            'is_active' => true,
        ]);

        MasterBarang::create([
            'kode_barang' => '1.3.2.10.02.04.010',
            'kode_permendagri' => '1.3.2.10.02.04.010',
            'kode_level5' => '1.3.2.10.02',
            'nama_level5' => 'PERALATAN KOMPUTER',
            'kode_level6' => '1.3.2.10.02.04',
            'nama_level6' => 'PERALATAN STUDIO DAN KOMUNIKASI',
            'nama_barang' => 'Microphone',
            'satuan' => 'Unit',
            'deskripsi' => 'PERALATAN KOMPUTER > PERALATAN STUDIO DAN KOMUNIKASI',
            'is_active' => true,
        ]);
    }

    public function test_smart_search_can_find_laptop_when_searching_without_space(): void
    {
        $results = MasterBarang::search('laptop')->get();

        $this->assertTrue($results->contains('nama_barang', 'Lap Top'), 'Pencarian laptop harus menemukan unit Lap Top.');
        $this->assertTrue($results->contains('nama_barang', 'Tas Laptop, Kedap Air Untuk Membawa Laptop'));
    }

    public function test_smart_search_can_find_whiteboard_and_microphone_variations(): void
    {
        $whiteboardResults = MasterBarang::search('whiteboard')->get();
        $this->assertTrue($whiteboardResults->contains('nama_barang', 'White Board'));

        $micResults = MasterBarang::search('micro phone')->get();
        $this->assertTrue($micResults->contains('nama_barang', 'Microphone'));
    }

    public function test_deskripsi_is_clean_without_ocr_typos(): void
    {
        $laptop = MasterBarang::where('nama_barang', 'Lap Top')->first();
        $this->assertNotNull($laptop);
        $this->assertEquals('KOMPUTER UNIT', $laptop->nama_level5);
        $this->assertEquals('PERSONAL KOMPUTER', $laptop->nama_level6);
        $this->assertEquals('KOMPUTER UNIT > PERSONAL KOMPUTER', $laptop->deskripsi);
        $this->assertStringNotContainsString('AIATSAR', $laptop->deskripsi);
        $this->assertStringNotContainsString('BAWAHAIR', $laptop->deskripsi);
    }

    public function test_level5_and_level6_scopes_work_correctly(): void
    {
        $komputerUnit = MasterBarang::level5('1.3.2.10.01')->get();
        $this->assertTrue($komputerUnit->isNotEmpty());
        foreach ($komputerUnit as $item) {
            $this->assertEquals('1.3.2.10.01', $item->kode_level5);
        }

        $personalKomputer = MasterBarang::level6('1.3.2.10.01.02')->get();
        $this->assertTrue($personalKomputer->isNotEmpty());
        foreach ($personalKomputer as $item) {
            $this->assertEquals('1.3.2.10.01.02', $item->kode_level6);
        }
    }

    public function test_admin_can_filter_master_barangs_by_level5_and_level6_via_datatables_ajax(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.master-barangs.index', [
            'level5' => '1.3.2.10.01',
            'level6' => '1.3.2.10.01.02',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        $json = $response->json();
        $this->assertEquals(1, $json['recordsFiltered']);
        $this->assertStringContainsString('Lap Top', $json['data'][0]['nama_barang']);
        $this->assertStringContainsString('KOMPUTER UNIT', $json['data'][0]['nama_barang']);
    }

    public function test_admin_can_fetch_level6_subcategories_via_api(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.master-barangs.level6', [
            'level5' => '1.3.2.10.01',
        ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $codes = array_column($data, 'kode_level6');
        $this->assertContains('1.3.2.10.01.02', $codes);
    }

    public function test_operator_can_search_master_barangs_async_with_smart_search_and_category_filter(): void
    {
        $response = $this->actingAs($this->operator)->getJson(route('operator.rkbmd.api.master-barangs', [
            'q' => 'laptop',
        ]));

        $response->assertOk();
        $items = $response->json();
        $this->assertNotEmpty($items);

        $names = array_column($items, 'nama_barang');
        $this->assertContains('Lap Top', $names);

        // Filter dengan level 5
        $respFiltered = $this->actingAs($this->operator)->getJson(route('operator.rkbmd.api.master-barangs', [
            'level5' => '1.3.2.10.01',
            'limit' => 15,
        ]));

        $respFiltered->assertOk();
        $filteredItems = $respFiltered->json();
        $this->assertNotEmpty($filteredItems);
        foreach ($filteredItems as $it) {
            $this->assertEquals('1.3.2.10.01', $it['kode_level5']);
        }
    }
}
