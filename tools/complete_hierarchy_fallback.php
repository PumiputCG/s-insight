<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$file = 'C:\\Users\\Pumiput_IT\\Desktop\\รวมงาน\\Project\\Insight\\SUPAVUT ASSESSENT\\Import Excel พนักงาน\\assessment_data_20260708_064147_hierarchy_filled.xlsx';

function tx($sheet, int $col, int $row): string
{
    return trim((string) $sheet->getCell([$col, $row])->getValue());
}

function hasH(array $values): bool
{
    foreach ($values as $value) {
        if (trim((string) $value) !== '') {
            return true;
        }
    }

    return false;
}

function hKey(array $values): string
{
    return implode("\t", array_map(fn ($v) => trim((string) $v), $values));
}

function normSimple(string $value): string
{
    $value = preg_replace('/\s+/u', ' ', trim($value));

    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function addBucket(array &$bucket, string $key, array $hierarchy): void
{
    if ($key === '' || ! hasH($hierarchy)) {
        return;
    }

    $hk = hKey($hierarchy);
    if (! isset($bucket[$key][$hk])) {
        $bucket[$key][$hk] = ['count' => 0, 'hierarchy' => $hierarchy];
    }
    $bucket[$key][$hk]['count']++;
}

function pickBucket(array $bucket, string $key, int $minCount = 1): ?array
{
    if (! isset($bucket[$key])) {
        return null;
    }

    $items = array_values($bucket[$key]);
    usort($items, fn ($a, $b) => $b['count'] <=> $a['count']);
    $total = array_sum(array_column($items, 'count'));
    $top = $items[0];

    if ($top['count'] < $minCount) {
        return null;
    }

    return [
        'hierarchy' => $top['hierarchy'],
        'count' => $top['count'],
        'total' => $total,
        'confidence' => $total > 0 ? $top['count'] / $total : 0,
    ];
}

function setHierarchy($sheet, int $row, array $hierarchy): void
{
    for ($offset = 0; $offset < 8; $offset++) {
        $sheet->setCellValueExplicit([6 + $offset, $row], (string) ($hierarchy[$offset] ?? ''), DataType::TYPE_STRING);
    }
}

function personHierarchy(string $code, string $name): array
{
    return [$code, $name, $code, $name, $code, $name, $code, $name];
}

function inestHierarchy(array $row, array $people): ?array
{
    $gm = $people['general manager'] ?? null;
    $manager = $people['manager'] ?? null;
    if ($gm === null) {
        return null;
    }

    $pos = normSimple($row['position']);

    if ($pos === 'general manager') {
        return personHierarchy($row['code'], $row['name']);
    }

    if ($pos === 'manager' || $manager === null) {
        return personHierarchy($gm['code'], $gm['name']);
    }

    return [
        $manager['code'], $manager['name'],
        $gm['code'], $gm['name'],
        $gm['code'], $gm['name'],
        $gm['code'], $gm['name'],
    ];
}

$spreadsheet = IOFactory::load($file);
$template = $spreadsheet->getActiveSheet();
$report = $spreadsheet->getSheetByName('Mapping_Report');

if ($report === null) {
    fwrite(STDERR, "Mapping_Report sheet not found\n");
    exit(1);
}

$rows = [];
$byCode = [];
$byDept = [];
$byDeptPosition = [];
$deptPeople = [];

for ($row = 3; $row <= $template->getHighestRow(); $row++) {
    $code = tx($template, 1, $row);
    if ($code === '') {
        continue;
    }

    $hierarchy = [];
    for ($col = 6; $col <= 13; $col++) {
        $hierarchy[] = tx($template, $col, $row);
    }

    $data = [
        'row' => $row,
        'code' => $code,
        'name' => tx($template, 2, $row),
        'position' => tx($template, 4, $row),
        'dept' => tx($template, 5, $row),
        'hierarchy' => $hierarchy,
    ];
    $rows[] = $data;
    $byCode[$code] = $data;

    $deptKey = normSimple($data['dept']);
    $positionKey = normSimple($data['position']);
    $deptPeople[$deptKey][$positionKey][] = ['code' => $code, 'name' => $data['name'], 'position' => $data['position']];

    if (hasH($hierarchy)) {
        addBucket($byDept, $deptKey, $hierarchy);
        addBucket($byDeptPosition, $deptKey.'||'.$positionKey, $hierarchy);
    }
}

$reportRowByCode = [];
for ($row = 2; $row <= $report->getHighestRow(); $row++) {
    $code = tx($report, 1, $row);
    if ($code !== '') {
        $reportRowByCode[$code] = $row;
    }
}

$filled = [
    'current_dept_position' => 0,
    'current_dept' => 0,
    'inest_position_tree' => 0,
    'single_dept_self' => 0,
    'still_unresolved' => 0,
];

foreach ($rows as $row) {
    if (hasH($row['hierarchy'])) {
        continue;
    }

    $method = '';
    $confidence = '';
    $hierarchy = null;
    $deptKey = normSimple($row['dept']);
    $positionKey = normSimple($row['position']);

    $candidate = pickBucket($byDeptPosition, $deptKey.'||'.$positionKey, 1);
    if ($candidate !== null) {
        $hierarchy = $candidate['hierarchy'];
        $method = 'current_dept_position';
        $confidence = round($candidate['confidence'] * 100, 1).'% ('.$candidate['count'].'/'.$candidate['total'].')';
    }

    if ($hierarchy === null) {
        $candidate = pickBucket($byDept, $deptKey, 1);
        if ($candidate !== null) {
            $hierarchy = $candidate['hierarchy'];
            $method = 'current_dept';
            $confidence = round($candidate['confidence'] * 100, 1).'% ('.$candidate['count'].'/'.$candidate['total'].')';
        }
    }

    if ($hierarchy === null && $deptKey === normSimple('iNest')) {
        $people = [];
        foreach ($deptPeople[$deptKey] ?? [] as $pos => $list) {
            if ($pos === 'general manager' || $pos === 'manager') {
                $people[$pos] = $list[0];
            }
        }

        $hierarchy = inestHierarchy($row, $people);
        if ($hierarchy !== null) {
            $method = 'inest_position_tree';
            $confidence = 'inferred from iNest GM/Manager';
        }
    }

    if ($hierarchy === null && count($deptPeople[$deptKey] ?? []) === 1 && count(($deptPeople[$deptKey] ?? [])[$positionKey] ?? []) === 1) {
        $hierarchy = personHierarchy($row['code'], $row['name']);
        $method = 'single_dept_self';
        $confidence = 'only employee in dept';
    }

    if ($hierarchy === null) {
        $filled['still_unresolved']++;
        continue;
    }

    setHierarchy($template, $row['row'], $hierarchy);
    $filled[$method]++;

    $reportRow = $reportRowByCode[$row['code']] ?? null;
    if ($reportRow !== null) {
        $report->setCellValueExplicit([5, $reportRow], $method, DataType::TYPE_STRING);
        $report->setCellValueExplicit([6, $reportRow], $confidence, DataType::TYPE_STRING);
        foreach ($hierarchy as $offset => $value) {
            $report->setCellValueExplicit([7 + $offset, $reportRow], (string) $value, DataType::TYPE_STRING);
        }
    }
}

$spreadsheet->setActiveSheetIndex(0);
(new Xlsx($spreadsheet))->save($file);

$remaining = 0;
for ($row = 3; $row <= $template->getHighestRow(); $row++) {
    $hierarchy = [];
    for ($col = 6; $col <= 13; $col++) {
        $hierarchy[] = tx($template, $col, $row);
    }
    if (! hasH($hierarchy)) {
        $remaining++;
    }
}

echo json_encode([
    'file' => $file,
    'filled_fallback' => $filled,
    'remaining_without_hierarchy' => $remaining,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
