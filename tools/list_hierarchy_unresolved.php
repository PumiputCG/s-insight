<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'C:\\Users\\Pumiput_IT\\Desktop\\รวมงาน\\Project\\Insight\\SUPAVUT ASSESSENT\\Import Excel พนักงาน\\assessment_data_20260708_064147_hierarchy_filled.xlsx';

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheetByName('Mapping_Report');

if ($sheet === null) {
    fwrite(STDERR, "Mapping_Report sheet not found\n");
    exit(1);
}

$highestRow = $sheet->getHighestRow();

for ($row = 2; $row <= $highestRow; $row++) {
    $method = trim((string) $sheet->getCell([5, $row])->getValue());
    if ($method !== 'unresolved') {
        continue;
    }

    $values = [];
    for ($col = 1; $col <= 6; $col++) {
        $values[] = trim((string) $sheet->getCell([$col, $row])->getValue());
    }

    echo implode(' | ', $values).PHP_EOL;
}
