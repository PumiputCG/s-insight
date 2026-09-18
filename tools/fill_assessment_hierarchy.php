<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dir = 'C:\\Users\\Pumiput_IT\\Desktop\\รวมงาน\\Project\\Insight\\SUPAVUT ASSESSENT\\Import Excel พนักงาน';
$newFile = $dir.'\\assessment_data_20260708_064147.xlsx';
$oldFile = $dir.'\\employee_assessments_CodeQ1_20260129_140146.xlsx';
$outFile = $dir.'\\assessment_data_20260708_064147_hierarchy_filled.xlsx';

$newHierarchyCols = range(6, 13);
$oldHierarchyCols = range(9, 16);

function cellText($sheet, int $col, int $row): string
{
    return trim((string) $sheet->getCell([$col, $row])->getValue());
}

function hasHierarchy(array $hierarchy): bool
{
    foreach ($hierarchy as $value) {
        if (trim((string) $value) !== '') {
            return true;
        }
    }

    return false;
}

function normText(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/u', ' ', $value);

    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function normCode(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/u', '', $value);

    return strtoupper($value);
}

function deptKeys(string ...$values): array
{
    $keys = [];

    foreach ($values as $value) {
        $value = trim($value);
        if ($value === '') {
            continue;
        }

        $keys[normText($value)] = true;
        $keys[normCode($value)] = true;

        foreach (preg_split('/[\/\\\\,;|]+/u', $value) ?: [] as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $keys[normText($part)] = true;
            $keys[normCode($part)] = true;
        }
    }

    return array_keys($keys);
}

function hierarchyKey(array $hierarchy): string
{
    return implode("\t", array_map(fn ($value) => trim((string) $value), $hierarchy));
}

function addCandidate(array &$bucket, string $key, array $hierarchy): void
{
    if ($key === '' || ! hasHierarchy($hierarchy)) {
        return;
    }

    $hKey = hierarchyKey($hierarchy);
    if (! isset($bucket[$key][$hKey])) {
        $bucket[$key][$hKey] = ['count' => 0, 'hierarchy' => $hierarchy];
    }

    $bucket[$key][$hKey]['count']++;
}

function pickCandidate(array $bucket, string $key, int $minCount = 1, float $minConfidence = 0.0): ?array
{
    if (! isset($bucket[$key])) {
        return null;
    }

    $items = array_values($bucket[$key]);
    usort($items, fn ($a, $b) => $b['count'] <=> $a['count']);

    $total = array_sum(array_column($items, 'count'));
    $top = $items[0];
    $confidence = $total > 0 ? $top['count'] / $total : 0;

    if ($top['count'] < $minCount || $confidence < $minConfidence) {
        return null;
    }

    return [
        'hierarchy' => $top['hierarchy'],
        'count' => $top['count'],
        'total' => $total,
        'confidence' => $confidence,
        'distinct' => count($items),
    ];
}

function fillHierarchy($sheet, int $row, array $targetCols, array $hierarchy): void
{
    foreach ($targetCols as $offset => $col) {
        $existing = cellText($sheet, $col, $row);
        if ($existing !== '') {
            continue;
        }

        $value = trim((string) ($hierarchy[$offset] ?? ''));
        if ($value === '') {
            continue;
        }

        $sheet->setCellValueExplicit([$col, $row], $value, DataType::TYPE_STRING);
    }
}

function readRows($sheet, array $hierarchyCols, array $layout): array
{
    $rows = [];
    $highestRow = $sheet->getHighestRow();

    for ($row = 3; $row <= $highestRow; $row++) {
        $code = cellText($sheet, $layout['code'], $row);
        if ($code === '') {
            continue;
        }

        $hierarchy = [];
        foreach ($hierarchyCols as $col) {
            $hierarchy[] = cellText($sheet, $col, $row);
        }

        $rows[] = [
            'row' => $row,
            'code' => $code,
            'name' => cellText($sheet, $layout['name'], $row),
            'position' => cellText($sheet, $layout['position'], $row),
            'dept' => cellText($sheet, $layout['dept'], $row),
            'dept_qms' => isset($layout['dept_qms']) ? cellText($sheet, $layout['dept_qms'], $row) : '',
            'dept_hr' => isset($layout['dept_hr']) ? cellText($sheet, $layout['dept_hr'], $row) : '',
            'hierarchy' => $hierarchy,
        ];
    }

    return $rows;
}

$newBook = IOFactory::load($newFile);
$oldBook = IOFactory::load($oldFile);
$newSheet = $newBook->getActiveSheet();
$oldSheet = $oldBook->getActiveSheet();

$oldRows = readRows($oldSheet, $oldHierarchyCols, [
    'code' => 1,
    'name' => 2,
    'position' => 5,
    'dept' => 6,
    'dept_qms' => 7,
    'dept_hr' => 8,
]);

$newRows = readRows($newSheet, $newHierarchyCols, [
    'code' => 1,
    'name' => 2,
    'position' => 4,
    'dept' => 5,
]);

$byCode = [];
$byDeptPosition = [];
$byDept = [];

foreach ($oldRows as $old) {
    if (! hasHierarchy($old['hierarchy'])) {
        continue;
    }

    $byCode[$old['code']] = $old['hierarchy'];
    $positionKey = normText($old['position']);

    foreach (deptKeys($old['dept'], $old['dept_qms'], $old['dept_hr']) as $deptKey) {
        addCandidate($byDeptPosition, $deptKey.'||'.$positionKey, $old['hierarchy']);
        addCandidate($byDept, $deptKey, $old['hierarchy']);
    }
}

$filled = [
    'direct_code' => 0,
    'dept_position' => 0,
    'dept' => 0,
    'already_had_hierarchy' => 0,
    'unresolved' => 0,
];
$reportRows = [];

foreach ($newRows as $new) {
    if (hasHierarchy($new['hierarchy'])) {
        $filled['already_had_hierarchy']++;
        continue;
    }

    $method = '';
    $confidence = '';
    $hierarchy = null;

    if (isset($byCode[$new['code']])) {
        $hierarchy = $byCode[$new['code']];
        $method = 'direct_code';
        $confidence = '100%';
    } else {
        $positionKey = normText($new['position']);

        foreach (deptKeys($new['dept']) as $deptKey) {
            $candidate = pickCandidate($byDeptPosition, $deptKey.'||'.$positionKey, 1, 0.0);
            if ($candidate !== null) {
                $hierarchy = $candidate['hierarchy'];
                $method = 'dept_position';
                $confidence = round($candidate['confidence'] * 100, 1).'% ('.$candidate['count'].'/'.$candidate['total'].')';
                break;
            }
        }

        if ($hierarchy === null) {
            foreach (deptKeys($new['dept']) as $deptKey) {
                $candidate = pickCandidate($byDept, $deptKey, 2, 0.45);
                if ($candidate !== null) {
                    $hierarchy = $candidate['hierarchy'];
                    $method = 'dept';
                    $confidence = round($candidate['confidence'] * 100, 1).'% ('.$candidate['count'].'/'.$candidate['total'].')';
                    break;
                }
            }
        }
    }

    if ($hierarchy !== null) {
        fillHierarchy($newSheet, $new['row'], $newHierarchyCols, $hierarchy);
        $filled[$method]++;
    } else {
        $filled['unresolved']++;
        $method = 'unresolved';
    }

    $reportRows[] = [
        $new['code'],
        $new['name'],
        $new['position'],
        $new['dept'],
        $method,
        $confidence,
        ...($hierarchy ?? array_fill(0, 8, '')),
    ];
}

$report = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($newBook, 'Mapping_Report');
$newBook->addSheet($report);
$headers = [
    'รหัสพนักงาน',
    'ชื่อ-สกุล(ไทย)',
    'ตำแหน่ง',
    'แผนก',
    'วิธีเติม',
    'ความมั่นใจ',
    'ID Supervisor',
    'Supervisor Name',
    'ID Dept.MGR',
    'Division Manager Name',
    'ID Dept.MGR',
    'Dept.MGR Name',
    'ID Plant MGR',
    'Plant Manager Name',
];
foreach ($headers as $i => $header) {
    $report->setCellValueExplicit([$i + 1, 1], $header, DataType::TYPE_STRING);
}
foreach ($reportRows as $rowIndex => $rowValues) {
    foreach ($rowValues as $colIndex => $value) {
        $report->setCellValueExplicit([$colIndex + 1, $rowIndex + 2], (string) $value, DataType::TYPE_STRING);
    }
}
$report->getStyle([1, 1, count($headers), 1])->getFont()->setBold(true);
for ($col = 1; $col <= count($headers); $col++) {
    $report->getColumnDimensionByColumn($col)->setAutoSize(true);
}
$newBook->setActiveSheetIndex(0);

(new Xlsx($newBook))->save($outFile);

$remaining = 0;
foreach ($newRows as $new) {
    $values = [];
    foreach ($newHierarchyCols as $col) {
        $values[] = cellText($newSheet, $col, $new['row']);
    }
    if (! hasHierarchy($values)) {
        $remaining++;
    }
}

echo json_encode([
    'output' => $outFile,
    'new_rows' => count($newRows),
    'old_rows' => count($oldRows),
    'filled' => $filled,
    'remaining_without_hierarchy' => $remaining,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
