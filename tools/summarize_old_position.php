<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'C:\\Users\\Pumiput_IT\\Desktop\\รวมงาน\\Project\\Insight\\SUPAVUT ASSESSENT\\Import Excel พนักงาน\\employee_assessments_CodeQ1_20260129_140146.xlsx';
$target = $argv[1] ?? 'Supervisor';

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$items = [];

for ($row = 3; $row <= $sheet->getHighestRow(); $row++) {
    $position = trim((string) $sheet->getCell([5, $row])->getValue());
    if (strcasecmp($position, $target) !== 0) {
        continue;
    }

    $hierarchy = [];
    for ($col = 9; $col <= 16; $col++) {
        $hierarchy[] = trim((string) $sheet->getCell([$col, $row])->getValue());
    }
    $key = implode(' | ', $hierarchy);
    $items[$key] = ($items[$key] ?? 0) + 1;
}

arsort($items);
echo "POSITION {$target}".PHP_EOL;
foreach (array_slice($items, 0, 20, true) as $hierarchy => $count) {
    echo "{$count} :: {$hierarchy}".PHP_EOL;
}
