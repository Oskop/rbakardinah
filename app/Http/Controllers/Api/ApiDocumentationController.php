<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiDocumentationController extends Controller
{
    /**
     * Render the API documentation portal (Swagger UI or Redoc).
     */
    public function index(Request $request): View
    {
        $view = $request->query('view', 'swagger');
        if (!in_array($view, ['swagger', 'redoc'])) {
            $view = 'swagger';
        }

        return view('api_documentation.index', [
            'view' => $view,
            'specUrl' => route('api.openapi.json'),
        ]);
    }

    /**
     * Generate and return the OpenAPI 3.0 specification.
     */
    public function openapi(): JsonResponse
    {
        $serverUrl = url('/api/v1');

        $markdownDescription = <<<'MARKDOWN'
# Panduan Integrasi REST API - Endpoint Pagu RBA RSUD Kardinah

Dokumen ini adalah panduan teknis resmi bagi pengembang pihak ketiga / aplikasi rekanan untuk mengakses data **Pagu Rencana Bisnis dan Anggaran (RBA)** Rumah Sakit Umum Daerah (RSUD) Kardinah Kota Tegal melalui layanan REST API.

---

## 1. Ringkasan Endpoint
* **Base URL**: `{SERVER_URL}`
* **Format Pertukaran Data**: `JSON (application/json)`
* **Karakter Encoding**: `UTF-8`
* **Rate Limit**: Maksimal **60 request per menit** per API Key

---

## 2. Sistem Autentikasi & Keamanan
Setiap permintaan ke endpoint yang terproteksi **wajib menyertakan API Key** yang aktif. Pilih salah satu cara berikut:

### Opsi A: Header `Authorization: Bearer <token>` (Direkomendasikan Standar REST)
```http
Authorization: Bearer rba_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Opsi B: Header Kustom `X-API-KEY`
```http
X-API-KEY: rba_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## 3. Contoh Kode Pemanggilan (Multi-Language Code Samples)

### 3.1 cURL (Terminal / Bash)
```bash
curl -X GET "{SERVER_URL}/pagu?year=2026&period=Murni" \
     -H "Authorization: Bearer rba_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
     -H "Accept: application/json"
```

### 3.2 JavaScript / Node.js (Fetch API)
```javascript
const API_URL = "{SERVER_URL}/pagu?year=2026&period=Murni";
const API_KEY = "rba_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";

fetch(API_URL, {
  method: "GET",
  headers: {
    "Authorization": `Bearer ${API_KEY}`,
    "Accept": "application/json"
  }
})
.then(response => response.json())
.then(data => {
  console.log("Status:", data.success);
  console.log("Total Pagu:", data.meta.total_nominal_pagu_formatted);
  console.log("Data Rekening:", data.data);
})
.catch(error => console.error("Error:", error));
```

### 3.3 PHP (Guzzle HTTP Client)
```php
<?php
$client = new \GuzzleHttp\Client();
$response = $client->request('GET', '{SERVER_URL}/pagu', [
    'headers' => [
        'Authorization' => 'Bearer rba_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'Accept'        => 'application/json',
    ],
    'query' => [
        'year'   => 2026,
        'period' => 'Murni',
    ]
]);

$result = json_decode($response->getBody(), true);
print_r($result);
```

### 3.4 Python (Requests)
```python
import requests

url = "{SERVER_URL}/pagu"
headers = {
    "Authorization": "Bearer rba_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "Accept": "application/json"
}
params = {
    "year": 2026,
    "period": "Murni"
}

response = requests.get(url, headers=headers, params=params)
data = response.json()
print("Total Nominal Pagu:", data["meta"]["total_nominal_pagu_formatted"])
```

---

## 4. Prosedur Memperoleh API Key Resmi
Untuk memperoleh API Key resmi bagi aplikasi rekanan, silakan berkoordinasi dengan:
* **Pengelola**: Tim IT / Bagian Perencanaan RSUD Kardinah Kota Tegal
* **Persyaratan**: Nama aplikasi pemohon, nama penanggung jawab teknis, dan tujuan integrasi data.
* Kunci akses bersifat rahasia dan tidak boleh dipublikasikan di repositori publik.
MARKDOWN;

        $markdownDescription = str_replace('{SERVER_URL}', $serverUrl, $markdownDescription);

        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'SIPAKAR REST API - RSUD Kardinah Kota Tegal',
                'description' => $markdownDescription,
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'Tim TI RSUD Kardinah Kota Tegal',
                    'url' => 'https://rsudkardinah.tegalkota.go.id',
                ],
            ],
            'servers' => [
                [
                    'url' => $serverUrl,
                    'description' => 'Server SIPAKAR API v1 (' . config('app.env') . ')',
                ],
            ],
            'tags' => [
                [
                    'name' => 'Pagu Anggaran',
                    'description' => 'Operasi data pagu belanja anggaran Rencana Bisnis dan Anggaran (RBA)',
                ],
                [
                    'name' => 'Sistem & Utilitas',
                    'description' => 'Pemeriksaan status dan konektivitas API',
                ],
            ],
            'paths' => [
                '/ping' => [
                    'get' => [
                        'tags' => ['Sistem & Utilitas'],
                        'summary' => 'Pemeriksaan Konektivitas (Health Check)',
                        'description' => 'Endpoint publik tanpa autentikasi untuk memverifikasi ketersediaan dan status aktif layanan REST API SIPAKAR.',
                        'operationId' => 'pingApi',
                        'responses' => [
                            '200' => [
                                'description' => 'Layanan API beroperasi normal.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'status' => 'ok',
                                            'message' => 'SIPAKAR API v1 is active and ready.',
                                            'timestamp' => '2026-09-09T17:30:00+07:00',
                                            'version' => '1.0.0',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/pagu' => [
                    'get' => [
                        'tags' => ['Pagu Anggaran'],
                        'summary' => 'Ambil Data Pagu Belanja RBA',
                        'description' => 'Mengambil daftar rincian pagu anggaran belanja RBA RSUD Kardinah. Dilengkapi 17 pilihan filter query dinamis yang dapat dikombinasikan secara bebas.',
                        'operationId' => 'getPaguList',
                        'security' => [
                            ['BearerAuth' => []],
                            ['ApiKeyAuth' => []],
                        ],
                        'parameters' => [
                            [
                                'name' => 'year',
                                'in' => 'query',
                                'description' => 'Tahun anggaran RBA (contoh: 2026)',
                                'required' => false,
                                'schema' => ['type' => 'integer', 'example' => 2026],
                            ],
                            [
                                'name' => 'tahun',
                                'in' => 'query',
                                'description' => 'Alias untuk parameter year',
                                'required' => false,
                                'schema' => ['type' => 'integer', 'example' => 2026],
                            ],
                            [
                                'name' => 'period',
                                'in' => 'query',
                                'description' => 'Nama periode RBA (contoh: Murni atau Perubahan)',
                                'required' => false,
                                'schema' => ['type' => 'string', 'enum' => ['Murni', 'Perubahan'], 'example' => 'Murni'],
                            ],
                            [
                                'name' => 'periode',
                                'in' => 'query',
                                'description' => 'Alias untuk parameter period',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => 'Murni'],
                            ],
                            [
                                'name' => 'period_id',
                                'in' => 'query',
                                'description' => 'ID unik periode RBA',
                                'required' => false,
                                'schema' => ['type' => 'integer', 'example' => 1],
                            ],
                            [
                                'name' => 'status',
                                'in' => 'query',
                                'description' => 'Status global RBA',
                                'required' => false,
                                'schema' => ['type' => 'string', 'enum' => ['Approved', 'Draft'], 'example' => 'Approved'],
                            ],
                            [
                                'name' => 'status_rba',
                                'in' => 'query',
                                'description' => 'Alias untuk parameter status',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => 'Approved'],
                            ],
                            [
                                'name' => 'kode_kelompok_belanja',
                                'in' => 'query',
                                'description' => 'Kode kelompok belanja (contoh: KB01, KB02)',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => 'KB01'],
                            ],
                            [
                                'name' => 'kelompok_belanja_kode',
                                'in' => 'query',
                                'description' => 'Alias untuk parameter kode_kelompok_belanja',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => 'KB01'],
                            ],
                            [
                                'name' => 'nama_kelompok_belanja',
                                'in' => 'query',
                                'description' => 'Pencarian parsial nama kelompok belanja (contoh: Operasi, Modal)',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => 'Operasi'],
                            ],
                            [
                                'name' => 'kelompok_belanja_name',
                                'in' => 'query',
                                'description' => 'Alias untuk parameter nama_kelompok_belanja',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => 'Operasi'],
                            ],
                            [
                                'name' => 'kode_rekening',
                                'in' => 'query',
                                'description' => 'Filter nomor rekening (kode lengkap atau awalan prefix, contoh: 5.1.01 atau 5.1.02.02.02.0005)',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => '5.1.01'],
                            ],
                            [
                                'name' => 'account_code',
                                'in' => 'query',
                                'description' => 'Alias untuk parameter kode_rekening',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => '5.1.01'],
                            ],
                            [
                                'name' => 'nama_rekening',
                                'in' => 'query',
                                'description' => 'Pencarian parsial uraian rekening belanja (contoh: Gaji, Jaminan Kesehatan, Listrik)',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => 'Gaji'],
                            ],
                            [
                                'name' => 'account_name',
                                'in' => 'query',
                                'description' => 'Alias untuk parameter nama_rekening',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => 'Gaji'],
                            ],
                            [
                                'name' => 'min_pagu',
                                'in' => 'query',
                                'description' => 'Filter batas nilai pagu minimal',
                                'required' => false,
                                'schema' => ['type' => 'number', 'example' => 50000000],
                            ],
                            [
                                'name' => 'max_pagu',
                                'in' => 'query',
                                'description' => 'Filter batas nilai pagu maksimal',
                                'required' => false,
                                'schema' => ['type' => 'number', 'example' => 500000000],
                            ],
                            [
                                'name' => 'exact_pagu',
                                'in' => 'query',
                                'description' => 'Filter nilai nominal pagu tepat sama',
                                'required' => false,
                                'schema' => ['type' => 'number', 'example' => 100000000],
                            ],
                            [
                                'name' => 'nilai_pagu',
                                'in' => 'query',
                                'description' => 'Alias untuk parameter exact_pagu',
                                'required' => false,
                                'schema' => ['type' => 'number', 'example' => 100000000],
                            ],
                            [
                                'name' => 'q',
                                'in' => 'query',
                                'description' => 'Pencarian kata kunci cepat pada nama rekening dan nama kelompok belanja',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => 'Gaji'],
                            ],
                            [
                                'name' => 'search',
                                'in' => 'query',
                                'description' => 'Alias untuk parameter q',
                                'required' => false,
                                'schema' => ['type' => 'string', 'example' => 'Gaji'],
                            ],
                            [
                                'name' => 'per_page',
                                'in' => 'query',
                                'description' => 'Jumlah baris data per halaman (default: 50, maksimum: 200)',
                                'required' => false,
                                'schema' => ['type' => 'integer', 'default' => 50, 'example' => 50],
                            ],
                            [
                                'name' => 'limit',
                                'in' => 'query',
                                'description' => 'Alias untuk parameter per_page',
                                'required' => false,
                                'schema' => ['type' => 'integer', 'example' => 50],
                            ],
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'description' => 'Nomor halaman pagination',
                                'required' => false,
                                'schema' => ['type' => 'integer', 'default' => 1, 'example' => 1],
                            ],
                            [
                                'name' => 'all',
                                'in' => 'query',
                                'description' => 'Jika diisi true / 1, mengembalikan seluruh data sekaligus tanpa pagination',
                                'required' => false,
                                'schema' => ['type' => 'boolean', 'default' => false, 'example' => false],
                            ],
                            [
                                'name' => 'sort_by',
                                'in' => 'query',
                                'description' => 'Kolom pengurutan data',
                                'required' => false,
                                'schema' => ['type' => 'string', 'enum' => ['code', 'name', 'nominal_pagu', 'year'], 'default' => 'code'],
                            ],
                            [
                                'name' => 'sort_dir',
                                'in' => 'query',
                                'description' => 'Arah urutan data (asc = A-Z, desc = Z-A)',
                                'required' => false,
                                'schema' => ['type' => 'string', 'enum' => ['asc', 'desc'], 'default' => 'asc'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Data pagu RBA berhasil diambil.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => true,
                                            'message' => 'Data pagu RBA berhasil diambil.',
                                            'meta' => [
                                                'total_records' => 66,
                                                'total_nominal_pagu' => 177000000000,
                                                'total_nominal_pagu_formatted' => 'Rp 177.000.000.000',
                                                'pagination' => [
                                                    'current_page' => 1,
                                                    'per_page' => 50,
                                                    'total_pages' => 2,
                                                    'from' => 1,
                                                    'to' => 50,
                                                    'has_more' => true,
                                                ],
                                                'filters_applied' => [
                                                    'year' => 2026,
                                                    'period' => 'Murni',
                                                ],
                                            ],
                                            'data' => [
                                                [
                                                    'id' => 1,
                                                    'rba_header_id' => 1,
                                                    'year' => 2026,
                                                    'period' => 'Murni',
                                                    'status_rba' => 'Draft',
                                                    'kode_kelompok_belanja' => 'KB01',
                                                    'nama_kelompok_belanja' => 'Belanja Operasi',
                                                    'kode_rekening' => '5.1.01.01.01.0001',
                                                    'nama_rekening' => 'Belanja Gaji Pokok PNS',
                                                    'nominal_pagu' => 5000000000,
                                                    'nominal_pagu_formatted' => 'Rp 5.000.000.000',
                                                    'updated_at' => '2026-09-08T04:20:00Z',
                                                ],
                                                [
                                                    'id' => 44,
                                                    'rba_header_id' => 1,
                                                    'year' => 2026,
                                                    'period' => 'Murni',
                                                    'status_rba' => 'Draft',
                                                    'kode_kelompok_belanja' => 'KB01',
                                                    'nama_kelompok_belanja' => 'Belanja Operasi',
                                                    'kode_rekening' => '5.1.02.02.02.0005',
                                                    'nama_rekening' => 'Belanja Iuran Jaminan Kesehatan bagi Non ASN',
                                                    'nominal_pagu' => 100000000,
                                                    'nominal_pagu_formatted' => 'Rp 100.000.000',
                                                    'updated_at' => '2026-09-08T04:20:00Z',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Otentikasi gagal. API Key tidak disertakan atau token tidak valid.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => false,
                                            'message' => 'Otentikasi gagal. API Key tidak ditemukan pada header Authorization: Bearer <token> atau X-API-KEY.',
                                        ],
                                    ],
                                ],
                            ],
                            '403' => [
                                'description' => 'Akses ditolak. API Key berstatus non-aktif atau telah kedaluwarsa.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => false,
                                            'message' => 'Akses ditolak. Akun API Key ini berstatus non-aktif. Silakan hubungi Administrator RSUD.',
                                        ],
                                    ],
                                ],
                            ],
                            '429' => [
                                'description' => 'Terlalu banyak permintaan (melebihi limit 60 request per menit).',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'message' => 'Too Many Requests',
                                        ],
                                    ],
                                ],
                            ],
                            '500' => [
                                'description' => 'Kesalahan internal server.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => false,
                                            'message' => 'Terjadi kesalahan internal pada server.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'API Key Token',
                        'description' => 'Masukkan API Key Anda menggunakan format Bearer token (contoh: rba_live_...).',
                    ],
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-KEY',
                        'description' => 'Masukkan API Key Anda secara langsung pada header X-API-KEY.',
                    ],
                ],
            ],
        ];

        return response()->json($spec);
    }
}
