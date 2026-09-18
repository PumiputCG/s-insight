<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'C:\\Users\\Pumiput_IT\\Desktop\\รวมงาน\\Project\\Insight\\SUPAVUT ASSESSENT\\Import Excel พนักงาน\\assessment_data_20260708_064147_hierarchy_filled.xlsx';
$targetDepts = array_flip(['MFG/MT1', 'ACC', 'iNest', 'BOI', 'HRD']);

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();
$deptMap = [];

for ($row = 3; $row <= $highestRow; $row++) {
    $dept = trim((string) $sheet->getCell([5, $row])->getValue());
    if (! isset($targetDepts[$dept])) {
        continue;
    }

    $hierarchy = [];
    for ($col = 6; $col <= 13; $col++) {
        $hierarchy[] = trim((string) $sheet->getCell([$col, $row])->getValue());
    }
    $key = implode(' | ', $hierarchy);
    if (trim($key) === '|||||||') {
        $key = '(blank)';
    }

    $deptMap[$dept][$key] = ($deptMap[$dept][$key] ?? 0) + 1;
}

foreach ($deptMap as $dept => $items) {
    arsort($items);
    echo "DEPT {$dept}".PHP_EOL;
    foreach (array_slice($items, 0, 10, true) as $hierarchy => $count) {
        echo "  {$count} :: {$hierarchy}".PHP_EOL;
    }
}
