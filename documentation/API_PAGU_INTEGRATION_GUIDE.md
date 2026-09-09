# Panduan Integrasi REST API - Endpoint Pagu RBA RSUD Kardinah

Dokumen ini adalah panduan teknis resmi bagi pengembang pihak ketiga / aplikasi rekanan untuk mengakses data **Pagu Rencana Bisnis dan Anggaran (RBA)** Rumah Sakit Umum Daerah (RSUD) Kardinah Kota Tegal melalui layanan REST API.

---

## 1. Ringkasan Endpoint

| Komponen | Spesifikasi |
| :--- | :--- |
| **Base URL** | `http://<domain_atau_ip_server_sipakar>/api/v1` |
| **Endpoint Path** | `/pagu` |
| **URL Lengkap** | `http://<domain_atau_ip_server_sipakar>/api/v1/pagu` |
| **HTTP Method** | `GET` |
| **Format Pertukaran Data** | `JSON (application/json)` |
| **Karakter Encoding** | `UTF-8` |
| **Rate Limit** | Maksimal 60 request per menit per API Key |

---

## 2. Sistem Autentikasi & Keamanan

Endpoint ini dilindungi oleh otentikasi **API Key**. Setiap permintaan (HTTP Request) yang dikirim oleh aplikasi rekanan **wajib menyertakan API Key** yang masih aktif pada header HTTP.

### Cara Mengirimkan API Key (Pilih salah satu):

#### Opsi A: Menggunakan Header Authorization Bearer (Direkomendasikan Standar REST)
```http
Authorization: Bearer rba_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

#### Opsi B: Menggunakan Header Kustom `X-API-KEY`
```http
X-API-KEY: rba_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

> **Catatan Keamanan**:
> - Jangan pernah mengekspos API Key pada repositori publik atau client-side code yang tidak terproteksi.
> - Jika API Key bocor atau perlu dicabut, segera hubungi Administrator SIPAKAR RSUD Kardinah untuk menonaktifkan dan membuatkan kunci baru.

---

## 3. Parameter Filter (Query Parameters)

Seluruh parameter di bawah bersifat opsional dan dapat dikombinasikan secara bebas:

| Parameter | Alias | Tipe | Contoh Nilai | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| `year` | `tahun` | Integer | `2026` | Filter tahun anggaran RBA |
| `period` | `periode` | String | `Murni` / `Perubahan` | Filter nama periode RBA |
| `period_id` | - | Integer | `1` | Filter ID periode RBA spesifik |
| `status` | `status_rba` | String | `Approved` / `Draft` | Filter status global RBA |
| `kode_kelompok_belanja` | `kelompok_belanja_kode` | String | `KB01` | Filter kode kelompok belanja |
| `nama_kelompok_belanja` | `kelompok_belanja_name` | String | `Operasi` | Pencarian parsial nama kelompok belanja |
| `kode_rekening` | `account_code` | String | `5.1.02` | Filter kode rekening (exact atau awalan prefix) |
| `nama_rekening` | `account_name` | String | `Jaminan Kesehatan` | Pencarian parsial uraian/nama rekening |
| `min_pagu` | - | Numeric | `50000000` | Filter batas minimal nominal pagu |
| `max_pagu` | - | Numeric | `500000000` | Filter batas maksimal nominal pagu |
| `exact_pagu` | `nilai_pagu` | Numeric | `100000000` | Filter nilai nominal pagu tepat sama |
| `q` | `search` | String | `Gaji` | Pencarian cepat pada nama rekening & kelompok |
| `per_page` | `limit` | Integer | `50` | Jumlah data per halaman (default 50, max 200) |
| `page` | - | Integer | `1` | Nomor halaman data |
| `all` | `paginate=false` | Boolean | `true` | Ambil seluruh data sekaligus tanpa pagination |
| `sort_by` | - | String | `code` / `name` / `nominal_pagu` / `year` | Kolom pengurutan data (default: `code`) |
| `sort_dir` | - | String | `asc` / `desc` | Arah urutan (default: `asc`) |

---

## 4. Contoh Penggunaan Request

### 4.1 Menggunakan cURL (Terminal / Command Prompt)
```bash
curl -X GET "http://10.102.10.180:8000/api/v1/pagu?year=2026&period=Murni" \
     -H "Authorization: Bearer rba_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
     -H "Accept: application/json"
```

### 4.2 Menggunakan Javascript (Fetch API / Node.js)
```javascript
const API_URL = "http://10.102.10.180:8000/api/v1/pagu?year=2026&period=Murni";
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

### 4.3 Menggunakan PHP (cURL / Guzzle)
```php
<?php

$client = new \GuzzleHttp\Client();
$response = $client->request('GET', 'http://10.102.10.180:8000/api/v1/pagu', [
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

### 4.4 Menggunakan Python (Requests)
```python
import requests

url = "http://10.102.10.180:8000/api/v1/pagu"
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

## 5. Struktur Respons (JSON Payload)

### 5.1 Respons Berhasil (HTTP 200 OK)
```json
{
  "success": true,
  "message": "Data pagu RBA berhasil diambil.",
  "meta": {
    "total_records": 66,
    "total_nominal_pagu": 177000000000,
    "total_nominal_pagu_formatted": "Rp 177.000.000.000",
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total_pages": 2,
      "from": 1,
      "to": 50,
      "has_more": true
    },
    "filters_applied": {
      "year": 2026,
      "period": "Murni"
    }
  },
  "data": [
    {
      "id": 1,
      "rba_header_id": 1,
      "year": 2026,
      "period": "Murni",
      "status_rba": "Draft",
      "kode_kelompok_belanja": "KB01",
      "nama_kelompok_belanja": "Belanja Operasi",
      "kode_rekening": "5.1.01.01.01.0001",
      "nama_rekening": "Belanja Gaji Pokok PNS",
      "nominal_pagu": 5000000000,
      "nominal_pagu_formatted": "Rp 5.000.000.000",
      "updated_at": "2026-09-08T04:20:00Z"
    },
    {
      "id": 44,
      "rba_header_id": 1,
      "year": 2026,
      "period": "Murni",
      "status_rba": "Draft",
      "kode_kelompok_belanja": "KB01",
      "nama_kelompok_belanja": "Belanja Operasi",
      "kode_rekening": "5.1.02.02.02.0005",
      "nama_rekening": "Belanja Iuran Jaminan Kesehatan bagi Non ASN",
      "nominal_pagu": 100000000,
      "nominal_pagu_formatted": "Rp 100.000.000",
      "updated_at": "2026-09-08T04:20:00Z"
    }
  ]
}
```

---

## 6. Penanganan Kesalahan (Error Codes)

| Kode HTTP | Keterangan | Contoh Respon JSON |
| :--- | :--- | :--- |
| **401 Unauthorized** | Token tidak dikirimkan atau token tidak valid / salah | `{"success": false, "message": "Otentikasi gagal. API Key tidak valid atau tidak terdaftar."}` |
| **403 Forbidden** | Akun API Key dinonaktifkan oleh Admin atau sudah kedaluwarsa | `{"success": false, "message": "Akses ditolak. Akun API Key ini berstatus non-aktif. Silakan hubungi Administrator RSUD."}` |
| **429 Too Many Requests** | Melebihi kuota pemanggilan (maksimal 60 request/menit) | `{"message": "Too Many Requests"}` |
| **500 Server Error** | Terjadi kesalahan internal server | `{"success": false, "message": "Terjadi kesalahan internal pada server."}` |

---

## 7. Perintah CLI untuk Administrator RSUD

Administrator RSUD dapat mengelola API Key melalui terminal perintah Artisan:

1. **Membuat API Key untuk Rekanan**:
   ```bash
   php artisan api:client-create "Nama Aplikasi Teman" [--expires-in-days=365]
   ```
2. **Melihat Daftar Seluruh Klien & Kapan Terakhir Diakses**:
   ```bash
   php artisan api:client-list
   ```
3. **Mencabut / Menonaktifkan Kunci Akses**:
   ```bash
   php artisan api:client-revoke {id_klien}
   ```
