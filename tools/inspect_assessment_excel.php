<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$dir = 'C:\\Users\\Pumiput_IT\\Desktop\\รวมงาน\\Project\\Insight\\SUPAVUT ASSESSENT\\Import Excel พนักงาน';

foreach (glob($dir.'\\*.xlsx') as $file) {
    echo basename($file).' exists='.(file_exists($file) ? 'yes' : 'no').PHP_EOL;

    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
    $maxRow = $sheet->getHighestRow();

    echo 'sheet='.$sheet->getTitle().' dim='.$sheet->getHighestColumn().$maxRow.PHP_EOL;

    for ($row = 1; $row <= min(5, $maxRow); $row++) {
        $values = [];
        for ($col = 1; $col <= min(24, $maxCol); $col++) {
            $values[] = trim((string) $sheet->getCell([$col, $row])->getValue());
        }

        echo 'row'.$row.': '.implode(' | ', $values).PHP_EOL;
    }

    echo '---'.PHP_EOL;
}
