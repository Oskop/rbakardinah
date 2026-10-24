<?php
$levelJson = json_decode(file_get_contents('data/permendagri_108_2016/permendagri_108_master_level1_6.json'), true);
$mapLevel6 = [];
foreach ($levelJson as $row) {
    if ($row['level'] == 6) {
        $mapLevel6[$row['kode']] = $row['uraian'];
    }
}

$tabel26 = json_decode(file_get_contents('data/permendagri_108_2016/tabel_2_6_sub_sub_rincian_objek.json'), true);
foreach ($tabel26 as $r) {
    if (($r['level'] ?? 0) == 7) {
        $c = $r['kode'];
        $l6 = substr($c, 0, strrpos($c, '.'));
        if (!isset($mapLevel6[$l6])) {
            echo "Kode: {$c} | Derived L6: {$l6} | Uraian: {$r['uraian']}\n";
        }
    }
}
