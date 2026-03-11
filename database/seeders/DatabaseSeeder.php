<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            KelompokBelanjaSeeder::class,
            AccountCodeSeeder::class,
            RbaPeriodSeeder::class,
        ]);

        $users = [
            // Format: [Name, Email, Role, Unit_ID]
            ['Admin', 'admin@hospital.com', 'Administrator', null],
            ['Direktur', 'direktur@hospital.com', 'Administrator', null],
            ['Wadir Pelayanan', 'wadiryan@hospital.com', 'Administrator', null],
            ['Wadir Umum dan Keuangan', 'wadirum@hospital.com', 'Supervisor', null],
            ['Kabid. Pelayanan', 'pelayanan@hospital.com', 'Supervisor', 1],
            ['Kabid. Keperawatan', 'keperawatan@hospital.com', 'Supervisor', 2],
            ['Kabid. Penunjang', 'penunjang@hospital.com', 'Supervisor', 3],
            ['Kabag. Perencanaan dan Pemasaran', 'rensar@hospital.com', 'Supervisor', 4],
            ['Kabag. Keuangan', 'keuangan@hospital.com', 'Supervisor', 5],
            ['Kabag. Umum', 'umum@hospital.com', 'Supervisor', 6],
            ['SPI', 'spi@hospital.com', 'Operator', 1],
            ['Komite Medik', 'komed@hospital.com', 'Operator', 1],
            ['Komite Keperawatan', 'komper@hospital.com', 'Operator', 2],
            ['Komite Nakes Lain', 'nakeslain@hospital.com', 'Operator', 1],
            ['Komite Mutu', 'mutu@hospital.com', 'Operator', 2],
            ['KPPI', 'kppi@hospital.com', 'Operator', 2],
            ['Komite K3', 'komitek3@hospital.com', 'Operator', 2],
            ['KKPRS', 'kkprs@hospital.com', 'Operator', 2],
            ['Komkordik', 'komkordik@hospital.com', 'Operator', 2],
            ['Komite Etik Penelitian', 'etikpenelitian@hospital.com', 'Operator', 1],
            ['Komite Etik dan Hukum', 'etikhukum@hospital.com', 'Operator', 1],
            ['Komite PPR anti Mikroba', 'pprantimikroba@hospital.com', 'Operator', 1],
            ['Komite Farmasi dan Terapi', 'komitefarmasi@hospital.com', 'Operator', 1],
            ['IGD', 'igd@hospital.com', 'Operator', 1],
            ['Instalasi Rawat Jalan', 'irj@hospital.com', 'Operator', 1],
            ['Unit Rawat Jalan', 'unitrajal@hospital.com', 'Operator', 2],
            ['Instalasi Hemodialisai', 'ihd@hospital.com', 'Operator', 2],
            ['Instalasi Bedah Sentral', 'ibs@hospital.com', 'Operator', 2],
            ['Instalasi Anastesi', 'anastesi@hospital.com', 'Operator', 2],
            ['Instalasi RM dan Infokes', 'irm_infokes@hospital.com', 'Operator', 3],
            ['Unit Rekam Medik', 'unitrm@hospital.com', 'Operator', 3],
            ['Unit Adm Klaim', 'unitklaim@hospital.com', 'Operator', 3],
            ['Unit Pendaftaran', 'pendaftaran@hospital.com', 'Operator', 3],
            ['Instalasi Radiologi', 'radiologi@hospital.com', 'Operator', 3],
            ['Instalasi Lab PK', 'labpk@hospital.com', 'Operator', 3],
            ['Instalasi Lab PA', 'labpa@hospital.com', 'Operator', 3],
            ['Instalasi Lab Mikrobiologi', 'labmikro@hospital.com', 'Operator', 3],
            ['Instalasi Farmasi', 'farmasi@hospital.com', 'Operator', 3],
            ['Instalasi Rehab Medik', 'rehabmedik@hospital.com', 'Operator', 3],
            ['Instalasi Loundry', 'loundry@hospital.com', 'Operator', 3],
            ['IKFM', 'ikfm@hospital.com', 'Operator', 3],
            ['Unit Kedokteran Forensi', 'unitforensi@hospital.com', 'Operator', 3],
            ['Unit Pemulasaran Jenazah', 'unitpemulasaran@hospital.com', 'Operator', 3],
            ['Instalasi Gizi', 'gizi@hospital.com', 'Operator', 3],
            ['IPLRS', 'iplrs@hospital.com', 'Operator', 3],
            ['IPKRS', 'ipkrs@hospital.com', 'Operator', 2],
            ['IPSRS', 'ipsrs@hospital.com', 'Operator', 6],
            ['Instalasi Pendidikan, Pelatihan dan Penelitian', 'instalasipendidikan@hospital.com', 'Operator', 6],
            ['Unit Rawat Inap', 'unitranap@hospital.com', 'Operator', 2],
            ['Unit PPM', 'unitppm@hospital.com', 'Operator', 3],
            ['Unit CSSD', 'unitcssd@hospital.com', 'Operator', 3],
            ['Sub Bag. Perencanaan dan Evaluasi', 'renval@hospital.com', 'Operator', 4],
            ['Sub Bag. Pemasaran dan Humas', 'humas@hospital.com', 'Operator', 4],
            ['Unit PDE', 'pde@hospital.com', 'Operator', 4],
            ['Sub Bag. Anggaran', 'anggaran@hospital.com', 'Operator', 5],
            ['Sub Bag. Akuntansi dan Perbendaharaan', 'akuntansi@hospital.com', 'Operator', 5],
            ['Sub Bag. Perlengkapan dan RT', 'perlengkapan@hospital.com', 'Operator', 6],
            ['Sub Bag. Tata Usaha', 'tu@hospital.com', 'Operator', 6],
            ['Sub Bag. Kepegawaian', 'kepegawaian@hospital.com', 'Operator', 6],
            ['Unit Sentral Dokumen', 'sendok@hospital.com', 'Operator', 6],
        ];

        foreach ($users as $user) {
            User::factory()->create([
                'name' => $user[0],
                'email' => $user[1],
                'password' => bcrypt('password'),
                'role' => $user[2],
                'unit_id' => $user[3],
            ]);
        }

        // // Admin User
        // User::factory()->create([
        //     'name' => 'Admin',
        //     'email' => 'admin@hospital.com',
        //     'password' => bcrypt('password'),
        //     'role' => 'Administrator',
        //     'unit_id' => null,
        // ]);

        // // Direktur User
        // User::factory()->create([
        //     'name' => 'Direktur',
        //     'email' => 'direktur@hospital.com',
        //     'password' => bcrypt('password'),
        //     'role' => 'Administrator',
        //     'unit_id' => null,
        // ]);

        // // Wakil Direktur Pelayanan User
        // User::factory()->create([
        //     'name' => 'Wadir Pelayanan',
        //     'email' => 'wadiryan@hospital.com',
        //     'password' => bcrypt('password'),
        //     'role' => 'Administrator',
        //     'unit_id' => null,
        // ]);

        // // Supervisor User (Unit Farmasi)
        // User::factory()->create([
        //     'name' => 'Supervisor Farmasi',
        //     'email' => 'supervisor@hospital.com',
        //     'password' => bcrypt('password'),
        //     'role' => 'Supervisor',
        //     'unit_id' => 1,
        // ]);

        // // Operator User (Unit Farmasi)
        // User::factory()->create([
        //     'name' => 'Operator Farmasi',
        //     'email' => 'operator@hospital.com',
        //     'password' => bcrypt('password'),
        //     'role' => 'Operator',
        //     'unit_id' => 1,
        // ]);
    }
}
