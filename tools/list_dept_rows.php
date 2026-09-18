<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'C:\\Users\\Pumiput_IT\\Desktop\\รวมงาน\\Project\\Insight\\SUPAVUT ASSESSENT\\Import Excel พนักงาน\\assessment_data_20260708_064147_hierarchy_filled.xlsx';
$target = $argv[1] ?? 'iNest';

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

for ($row = 3; $row <= $sheet->getHighestRow(); $row++) {
    $dept = trim((string) $sheet->getCell([5, $row])->getValue());
    if ($dept !== $target) {
        continue;
    }

    $values = [];
    foreach ([1, 2, 4, 5, 14] as $col) {
        $values[] = trim((string) $sheet->getCell([$col, $row])->getValue());
    }

    echo implode(' | ', $values).PHP_EOL;
}
