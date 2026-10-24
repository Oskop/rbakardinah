<?php
$levelJson = json_decode(file_get_contents('data/permendagri_108_2016/permendagri_108_master_level1_6.json'), true);
$mapLevel5 = [];
$mapLevel6 = [];

foreach ($levelJson as $row) {
    if ($row['level'] == 5) {
        $mapLevel5[$row['kode']] = $row['uraian'];
    } elseif ($row['level'] == 6) {
        $mapLevel6[$row['kode']] = $row['uraian'];
    }
}

$tabel26 = json_decode(file_get_contents('data/permendagri_108_2016/tabel_2_6_sub_sub_rincian_objek.json'), true);
$samples = [];
foreach ($tabel26 as $r) {
    $c = $r['kode'];
    $l6 = substr($c, 0, strrpos($c, '.'));
    $l5 = substr($l6, 0, strrpos($l6, '.'));
    if (!isset($mapLevel6[$l6]) && count($samples) < 10) {
        $samples[] = [
            'kode_level7' => $c,
            'uraian' => $r['uraian'],
            'derived_level6' => $l6,
            'derived_level5' => $l5,
        ];
    }
}
print_r($samples);
