<?php

namespace Database\Seeders;

use App\Models\SubUnit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define sub-units per unit code
        // Structure: [unit_code => [[code, name, type]]]
        $subUnitsDefinitions = [
            'U001' => [ // Unit Pelayanan
                ['SUB-YAN-01', 'SPI', 'Unit'],
                ['SUB-YAN-02', 'Komite Medik', 'Komite'],
                ['SUB-YAN-03', 'Komite Nakes Lain', 'Komite'],
                ['SUB-YAN-04', 'Komite Etik Penelitian', 'Komite'],
                ['SUB-YAN-05', 'Komite Etik dan Hukum', 'Komite'],
                ['SUB-YAN-06', 'Komite PPR anti Mikroba', 'Komite'],
                ['SUB-YAN-07', 'Komite Farmasi dan Terapi', 'Komite'],
                ['SUB-YAN-08', 'IGD', 'Instalasi'],
                ['SUB-YAN-09', 'Instalasi Rawat Jalan', 'Instalasi'],
            ],
            'U002' => [ // Unit Keperawatan
                ['SUB-KEP-01', 'Komite Keperawatan', 'Komite'],
                ['SUB-KEP-02', 'Komite Mutu', 'Komite'],
                ['SUB-KEP-03', 'KPPI', 'Komite'],
                ['SUB-KEP-04', 'Komite K3', 'Komite'],
                ['SUB-KEP-05', 'KKPRS', 'Komite'],
                ['SUB-KEP-06', 'Komkordik', 'Komite'],
                ['SUB-KEP-07', 'Unit Rawat Jalan', 'Unit'],
                ['SUB-KEP-08', 'Instalasi Hemodialisai', 'Instalasi'],
                ['SUB-KEP-09', 'Instalasi Bedah Sentral', 'Instalasi'],
                ['SUB-KEP-10', 'Instalasi Anastesi', 'Instalasi'],
                ['SUB-KEP-11', 'IPKRS', 'Instalasi'],
                ['SUB-KEP-12', 'Unit Rawat Inap', 'Unit'],
            ],
            'U003' => [ // Unit Penunjang
                ['SUB-PEN-01', 'Instalasi RM dan Infokes', 'Instalasi'],
                ['SUB-PEN-02', 'Unit Rekam Medik', 'Unit'],
                ['SUB-PEN-03', 'Unit Adm Klaim', 'Unit'],
                ['SUB-PEN-04', 'Unit Pendaftaran', 'Unit'],
                ['SUB-PEN-05', 'Instalasi Radiologi', 'Instalasi'],
                ['SUB-PEN-06', 'Instalasi Lab PK', 'Instalasi'],
                ['SUB-PEN-07', 'Instalasi Lab PA', 'Instalasi'],
                ['SUB-PEN-08', 'Instalasi Lab Mikrobiologi', 'Instalasi'],
                ['SUB-PEN-09', 'Instalasi Farmasi', 'Instalasi'],
                ['SUB-PEN-10', 'Instalasi Rehab Medik', 'Instalasi'],
                ['SUB-PEN-11', 'Instalasi Loundry', 'Instalasi'],
                ['SUB-PEN-12', 'IKFM', 'Instalasi'],
                ['SUB-PEN-13', 'Unit Kedokteran Forensi', 'Unit'],
                ['SUB-PEN-14', 'Unit Pemulasaran Jenazah', 'Unit'],
                ['SUB-PEN-15', 'Instalasi Gizi', 'Instalasi'],
                ['SUB-PEN-16', 'IPLRS', 'Instalasi'],
                ['SUB-PEN-17', 'Unit PPM', 'Unit'],
                ['SUB-PEN-18', 'Unit CSSD', 'Unit'],
            ],
            'U004' => [ // Unit Perencanaan dan Pemasaran
                ['SUB-REN-01', 'Sub Bag. Perencanaan dan Evaluasi', 'Sub Bagian'],
                ['SUB-REN-02', 'Sub Bag. Pemasaran dan Humas', 'Sub Bagian'],
                ['SUB-REN-03', 'Unit PDE', 'Unit'],
            ],
            'U005' => [ // Unit Umum (or Unit Keuangan depending on DB code)
                ['SUB-UMU-01', 'IPSRS', 'Instalasi'],
                ['SUB-UMU-02', 'Instalasi Pendidikan, Pelatihan dan Penelitian', 'Instalasi'],
                ['SUB-UMU-03', 'Sub Bag. Perlengkapan dan RT', 'Sub Bagian'],
                ['SUB-UMU-04', 'Sub Bag. Tata Usaha', 'Sub Bagian'],
                ['SUB-UMU-05', 'Sub Bag. Kepegawaian', 'Sub Bagian'],
                ['SUB-UMU-06', 'Unit Sentral Dokumen', 'Unit'],
            ],
            'U006' => [ // Unit Keuangan
                ['SUB-KEU-01', 'Sub Bag. Anggaran', 'Sub Bagian'],
                ['SUB-KEU-02', 'Sub Bag. Akuntansi dan Perbendaharaan', 'Sub Bagian'],
            ],
        ];

        // Ensure we match units accurately by code or name
        $unitsByCode = Unit::all()->keyBy('code');
        $unitsByName = Unit::all()->keyBy(function ($u) {
            return strtolower(trim($u->name));
        });

        foreach ($subUnitsDefinitions as $unitCode => $subUnits) {
            $unit = $unitsByCode->get($unitCode);

            // Fallback match by name if code differs
            if (!$unit) {
                if (str_contains($unitCode, 'UMU')) {
                    $unit = $unitsByName->get('unit umum');
                } elseif (str_contains($unitCode, 'KEU')) {
                    $unit = $unitsByName->get('unit keuangan');
                }
            }

            if (!$unit) {
                continue;
            }

            foreach ($subUnits as $item) {
                [$code, $name, $type] = $item;

                // Create or update sub unit
                $subUnit = SubUnit::firstOrCreate(
                    ['code' => $code],
                    [
                        'unit_id' => $unit->id,
                        'name' => $name,
                        'type' => $type,
                        'is_active' => true,
                    ]
                );

                // If user with matching name or email exists, associate them with this sub unit
                User::where('unit_id', $unit->id)
                    ->where(function ($query) use ($name) {
                        $query->where('name', $name)
                              ->orWhere('name', 'like', '%' . $name . '%');
                    })
                    ->whereNull('sub_unit_id')
                    ->update([
                        'sub_unit_id' => $subUnit->id,
                        'jabatan' => $type . ' ' . $name,
                    ]);
            }
        }

        // Set realistic Jabatan for Supervisors & Admins if currently blank
        User::where('role', 'Administrator')->whereNull('jabatan')->get()->each(function ($u) {
            if (stripos($u->name, 'Direktur') !== false && stripos($u->name, 'Wadir') === false) {
                $u->update(['jabatan' => 'Direktur RSUD Kardinah']);
            } elseif (stripos($u->name, 'Wadir') !== false) {
                $u->update(['jabatan' => 'Wakil Direktur']);
            } elseif (stripos($u->name, 'Admin') !== false) {
                $u->update(['jabatan' => 'System Administrator']);
            }
        });

        User::where('role', 'Supervisor')->whereNull('jabatan')->get()->each(function ($u) {
            if ($u->unit) {
                $u->update(['jabatan' => 'Kepala ' . $u->unit->name]);
            } else {
                $u->update(['jabatan' => 'Supervisor']);
            }
        });

        // For any remaining operators without jabatan, set default
        User::where('role', 'Operator')->whereNull('jabatan')->get()->each(function ($u) {
            if ($u->subUnit) {
                $u->update(['jabatan' => 'Staf ' . $u->subUnit->name]);
            } elseif ($u->unit) {
                $u->update(['jabatan' => 'Staf ' . $u->unit->name]);
            } else {
                $u->update(['jabatan' => 'Operator']);
            }
        });
    }
}
