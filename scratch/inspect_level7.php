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
$level7Count = 0;
$missing6 = 0;
$missing5 = 0;

foreach ($tabel26 as $r) {
    if (($r['level'] ?? 0) == 7) {
        $level7Count++;
        $c = $r['kode'];
        $l6 = substr($c, 0, strrpos($c, '.'));
        $l5 = substr($l6, 0, strrpos($l6, '.'));
        if (!isset($mapLevel6[$l6])) $missing6++;
        if (!isset($mapLevel5[$l5])) $missing5++;
    }
}

echo "Total Level 7: {$level7Count}\n";
echo "Missing Level 6 on level 7 items: {$missing6}\n";
echo "Missing Level 5 on level 7 items: {$missing5}\n";
