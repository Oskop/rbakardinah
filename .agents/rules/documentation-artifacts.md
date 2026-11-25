---
trigger: always_on
---

# Aturan Penyimpanan Artifact `implementation_plan` dan `walkthrough`

Setiap kali membuat atau memperbarui artifact **`implementation_plan`** atau **`walkthrough`**, agen **WAJIB** sekaligus menyimpan salinannya ke dalam folder `documentation/` di root repositori ini.

## Format Penamaan Berkas

```text
documentation/{yyyymmddHHMMSS}_{tipe_dokumen}_{Judul_Dokumen}.md
```

- `{yyyymmddHHMMSS}`: Timestamp waktu lokal (WIB / UTC+7) saat dokumen dibuat atau direvisi (contoh: `20261125094909`).
- `{tipe_dokumen}`: **Harus** salah satu dari dua nilai berikut:
  - `implementation_plan`
  - `walkthrough`
- `{Judul_Dokumen}`: Ringkasan judul dokumen yang singkat dan deskriptif, menggunakan pemisah garis bawah `_` (contoh: `Perbaikan_Wrap_Text_Deskripsi_RKBMD_DataTables`).

## Ketentuan Wajib Lainnya

1. **Sinkronisasi Artifact & Repositori**: Baik salinan di direktori artifact Antigravity maupun salinan di `documentation/` harus memiliki isi yang identik dan mutakhir (termasuk ketika `implementation_plan` mengalami revisi berdasarkan review pengguna).
2. **Sertakan Tautan ke User**: Saat menyampaikan hasil pembuatan `implementation_plan` atau `walkthrough` kepada pengguna, sertakan tautan klik ke kedua lokasi berkas (file di `documentation/` dan file artifact).

