<?php

namespace Tests\Feature\Api;

use App\Models\AccountCode;
use App\Models\ApiClient;
use App\Models\KelompokBelanja;
use App\Models\RbaAccountPagu;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaguApiTest extends TestCase
{
    use RefreshDatabase;

    protected ApiClient $activeClient;
    protected string $plainToken;
    protected RbaHeader $header2026Murni;
    protected RbaHeader $header2026Perubahan;
    protected KelompokBelanja $kelompokOperasi;
    protected KelompokBelanja $kelompokModal;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup API Client
        $created = ApiClient::createWithToken('Aplikasi Teman Rekanan');
        $this->activeClient = $created['client'];
        $this->plainToken = $created['plain_token'];

        // 2. Setup Periode & Header
        $admin = User::factory()->create(['role' => 'Administrator']);

        $periodMurni = RbaPeriod::create(['name' => 'Murni', 'is_active' => true]);
        $periodPerubahan = RbaPeriod::create(['name' => 'Perubahan', 'is_active' => true]);

        $this->header2026Murni = RbaHeader::create([
            'year' => 2026,
            'period_id' => $periodMurni->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft',
        ]);

        $this->header2026Perubahan = RbaHeader::create([
            'year' => 2026,
            'period_id' => $periodPerubahan->id,
            'admin_id' => $admin->id,
            'status_global' => 'Approved',
        ]);

        // 3. Setup Kelompok Belanja & Account Codes
        $this->kelompokOperasi = KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Belanja Operasi']);
        $this->kelompokModal = KelompokBelanja::create(['kode' => 'KB02', 'name' => 'Belanja Modal']);

        $acGaji = AccountCode::create([
            'kelompok_belanja_id' => $this->kelompokOperasi->id,
            'code' => '5.1.01.01.01.0001',
            'name' => 'Belanja Gaji Pokok PNS',
        ]);

        $acJkn = AccountCode::create([
            'kelompok_belanja_id' => $this->kelompokOperasi->id,
            'code' => '5.1.02.02.02.0005',
            'name' => 'Belanja Iuran Jaminan Kesehatan bagi Non ASN',
        ]);

        $acAlatMedis = AccountCode::create([
            'kelompok_belanja_id' => $this->kelompokModal->id,
            'code' => '5.2.02.08.01.0001',
            'name' => 'Belanja Modal Alat Kedokteran Umum',
        ]);

        // 4. Setup Pagu
        RbaAccountPagu::create([
            'rba_header_id' => $this->header2026Murni->id,
            'account_code_id' => $acGaji->id,
            'nominal_pagu' => 5000000000,
        ]);

        RbaAccountPagu::create([
            'rba_header_id' => $this->header2026Murni->id,
            'account_code_id' => $acJkn->id,
            'nominal_pagu' => 100000000,
        ]);

        RbaAccountPagu::create([
            'rba_header_id' => $this->header2026Perubahan->id,
            'account_code_id' => $acAlatMedis->id,
            'nominal_pagu' => 750000000,
        ]);
    }

    public function test_public_ping_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/ping');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'service' => 'SIPAKAR RSUD Kardinah REST API',
                'status' => 'OK',
            ]);
    }

    public function test_protected_endpoint_without_key_returns_401(): void
    {
        $response = $this->getJson('/api/v1/pagu');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonFragment(['message' => 'Otentikasi gagal. API Key tidak ditemukan pada header Authorization: Bearer <token> atau X-API-KEY.']);
    }

    public function test_protected_endpoint_with_invalid_key_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer rba_live_invalid_token_1234567890')
            ->getJson('/api/v1/pagu');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Otentikasi gagal. API Key tidak valid atau tidak terdaftar.',
            ]);
    }

    public function test_protected_endpoint_with_inactive_key_returns_403(): void
    {
        $this->activeClient->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonFragment(['message' => 'Akses ditolak. Akun API Key ini berstatus non-aktif. Silakan hubungi Administrator RSUD.']);
    }

    public function test_protected_endpoint_with_expired_key_returns_403(): void
    {
        $this->activeClient->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_protected_endpoint_with_valid_bearer_token_returns_all_8_attributes(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'meta' => [
                    'total_records' => 3,
                    'total_nominal_pagu' => 5850000000,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'meta' => [
                    'total_records',
                    'total_nominal_pagu',
                    'total_nominal_pagu_formatted',
                    'pagination',
                    'filters_applied',
                ],
                'data' => [
                    '*' => [
                        'id',
                        'rba_header_id',
                        'year',
                        'period',
                        'status_rba',
                        'kode_kelompok_belanja',
                        'nama_kelompok_belanja',
                        'kode_rekening',
                        'nama_rekening',
                        'nominal_pagu',
                        'nominal_pagu_formatted',
                        'updated_at',
                    ],
                ],
            ]);

        // Verifikasi item spesifik 5.1.02.02.02.0005 bernilai 100jt
        $response->assertJsonFragment([
            'kode_rekening' => '5.1.02.02.02.0005',
            'nama_rekening' => 'Belanja Iuran Jaminan Kesehatan bagi Non ASN',
            'nominal_pagu' => 100000000,
            'nominal_pagu_formatted' => 'Rp 100.000.000',
        ]);
    }

    public function test_protected_endpoint_with_x_api_key_header_succeeds(): void
    {
        $response = $this->withHeader('X-API-KEY', $this->plainToken)
            ->getJson('/api/v1/pagu');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_filter_pagus_by_year_period_and_status(): void
    {
        // 1. Filter by Year 2026 & Period Murni
        $resMurni = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu?year=2026&period=Murni');

        $resMurni->assertStatus(200)
            ->assertJsonPath('meta.total_records', 2)
            ->assertJsonPath('meta.total_nominal_pagu', 5100000000);

        // 2. Filter by Status Approved (hanya Perubahan)
        $resApproved = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu?status=Approved');

        $resApproved->assertStatus(200)
            ->assertJsonPath('meta.total_records', 1)
            ->assertJsonFragment(['period' => 'Perubahan', 'status_rba' => 'Approved']);
    }

    public function test_can_filter_pagus_by_account_code_and_kelompok_belanja(): void
    {
        // 1. Filter by Kelompok Belanja KB02 (Modal)
        $resKb = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu?kode_kelompok_belanja=KB02');

        $resKb->assertStatus(200)
            ->assertJsonPath('meta.total_records', 1)
            ->assertJsonFragment(['kode_kelompok_belanja' => 'KB02', 'nama_kelompok_belanja' => 'Belanja Modal']);

        // 2. Filter by Account Code prefix 5.1.02
        $resAc = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu?kode_rekening=5.1.02');

        $resAc->assertStatus(200)
            ->assertJsonPath('meta.total_records', 1)
            ->assertJsonFragment(['kode_rekening' => '5.1.02.02.02.0005']);

        // 3. Filter by Nama Rekening search
        $resNama = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu?nama_rekening=Jaminan');

        $resNama->assertStatus(200)
            ->assertJsonPath('meta.total_records', 1)
            ->assertJsonFragment(['kode_rekening' => '5.1.02.02.02.0005']);
    }

    public function test_can_filter_pagus_by_amount_range_and_search(): void
    {
        // Filter range: min 50jt, max 200jt -> harusnya hanya 100jt (JKN)
        $resRange = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu?min_pagu=50000000&max_pagu=200000000');

        $resRange->assertStatus(200)
            ->assertJsonPath('meta.total_records', 1)
            ->assertJsonFragment(['nominal_pagu' => 100000000]);

        // Quick search 'Gaji'
        $resSearch = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu?q=Gaji');

        $resSearch->assertStatus(200)
            ->assertJsonPath('meta.total_records', 1)
            ->assertJsonFragment(['nama_rekening' => 'Belanja Gaji Pokok PNS']);
    }

    public function test_pagination_and_all_flag_works(): void
    {
        // 1. Pagination with per_page = 1
        $resPage = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu?per_page=1&page=1');

        $resPage->assertStatus(200)
            ->assertJsonPath('meta.pagination.per_page', 1)
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonPath('meta.pagination.has_more', true);

        // 2. Fetch all=true
        $resAll = $this->withHeader('Authorization', "Bearer {$this->plainToken}")
            ->getJson('/api/v1/pagu?all=true');

        $resAll->assertStatus(200)
            ->assertJsonPath('meta.total_records', 3)
            ->assertJsonCount(3, 'data');
    }

    public function test_artisan_commands_for_api_client_management(): void
    {
        // 1. Create client command
        $this->artisan('api:client-create', ['name' => 'Aplikasi Dinkes Test', '--expires-in-days' => 30])
            ->assertSuccessful()
            ->expectsOutputToContain('BERHASIL MEMBUAT API KEY KLIEN BARU');

        $this->assertDatabaseHas('api_clients', [
            'name' => 'Aplikasi Dinkes Test',
            'is_active' => true,
        ]);

        $newClient = ApiClient::where('name', 'Aplikasi Dinkes Test')->first();

        // 2. List clients command
        $this->artisan('api:client-list')
            ->assertSuccessful()
            ->expectsOutputToContain('Aplikasi Dinkes Test');

        // 3. Revoke client command
        $this->artisan('api:client-revoke', ['identifier' => $newClient->id])
            ->assertSuccessful()
            ->expectsOutputToContain('DINONAKTIFKAN');

        $this->assertDatabaseHas('api_clients', [
            'id' => $newClient->id,
            'is_active' => false,
        ]);
    }
}
