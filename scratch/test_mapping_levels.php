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

echo "Total Level 5: " . count($mapLevel5) . "\n";
echo "Total Level 6: " . count($mapLevel6) . "\n";

// Test matching on a Level 7 code
$testCode = '1.3.2.10.01.02.002';
$lastDot = strrpos($testCode, '.');
$code6 = substr($testCode, 0, $lastDot);
$secondLastDot = strrpos($code6, '.');
$code5 = substr($code6, 0, $secondLastDot);

echo "\nTest Kode Level 7: {$testCode}\n";
echo "Kode Level 6: {$code6} => " . ($mapLevel6[$code6] ?? 'NOT FOUND') . "\n";
echo "Kode Level 5: {$code5} => " . ($mapLevel5[$code5] ?? 'NOT FOUND') . "\n";

// Check coverage of all 13,476 Level 7 items
$tabel26 = json_decode(file_get_contents('data/permendagri_108_2016/tabel_2_6_sub_sub_rincian_objek.json'), true);
$missing5 = 0;
$missing6 = 0;
foreach ($tabel26 as $r) {
    $c = $r['kode'];
    $l6 = substr($c, 0, strrpos($c, '.'));
    $l5 = substr($l6, 0, strrpos($l6, '.'));
    if (!isset($mapLevel6[$l6])) $missing6++;
    if (!isset($mapLevel5[$l5])) $missing5++;
}
echo "\nCoverage Test:\n";
echo "Missing Level 6: {$missing6} / " . count($tabel26) . "\n";
echo "Missing Level 5: {$missing5} / " . count($tabel26) . "\n";
