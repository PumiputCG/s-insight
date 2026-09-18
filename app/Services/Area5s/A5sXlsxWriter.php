<?php

namespace App\Services\Area5s;

use RuntimeException;
use ZipArchive;

/**
 * สร้างไฟล์ Excel .xlsx (Office Open XML) ด้วย ZipArchive ตรง ๆ — ไม่พึ่ง package นอก
 * รองรับ: inline string, merge cells, ความกว้างคอลัมน์, สไตล์ (0=ปกติ 1=หัวตาราง 2=ขอบ+wrap 3=ขอบ+กึ่งกลาง)
 */
class A5sXlsxWriter
{
    /**
     * @param  array<int, string>  $headers  หัวตาราง (แถวแรก)
     * @param  array<int, array<int, string>>  $rows  แถวข้อมูล
     * @param  array<int, string>  $merges  เช่น ['A2:A4', 'B2:B9']
     * @param  array<int, int|float>  $widths  ความกว้างต่อคอลัมน์
     * @param  array<int, int>  $colStyles  style index ต่อคอลัมน์ของแถวข้อมูล (default 2)
     */
    public static function write(string $file, string $sheetName, array $headers, array $rows, array $merges = [], array $widths = [], array $colStyles = []): void
    {
        $zip = new ZipArchive;
        if ($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('สร้างไฟล์ xlsx ไม่ได้: '.$file);
        }

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>
XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>
XML);
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.self::esc($sheetName).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>
XML);
        $zip->addFromString('xl/styles.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Tahoma"/></font><font><b/><sz val="11"/><name val="Tahoma"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE7ECE0"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFADB5A6"/></left><right style="thin"><color rgb="FFADB5A6"/></right><top style="thin"><color rgb="FFADB5A6"/></top><bottom style="thin"><color rgb="FFADB5A6"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs></styleSheet>
XML);

        $cols = '';
        foreach ($widths as $i => $w) {
            $n = $i + 1;
            $cols .= '<col min="'.$n.'" max="'.$n.'" width="'.$w.'" customWidth="1"/>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .($cols !== '' ? '<cols>'.$cols.'</cols>' : '')
            .'<sheetData>';

        $sheet .= '<row r="1" ht="24" customHeight="1">';
        foreach ($headers as $i => $header) {
            $sheet .= '<c r="'.self::ref($i, 1).'" t="inlineStr" s="1"><is><t xml:space="preserve">'.self::esc($header).'</t></is></c>';
        }
        $sheet .= '</row>';

        foreach ($rows as $r => $row) {
            $rowNum = $r + 2;
            $sheet .= '<row r="'.$rowNum.'">';
            foreach ($row as $i => $value) {
                $style = $colStyles[$i] ?? 2;
                $sheet .= '<c r="'.self::ref($i, $rowNum).'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.self::esc((string) $value).'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData>';

        if ($merges !== []) {
            $sheet .= '<mergeCells count="'.count($merges).'">';
            foreach ($merges as $merge) {
                $sheet .= '<mergeCell ref="'.$merge.'"/>';
            }
            $sheet .= '</mergeCells>';
        }
        $sheet .= '</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
    }

    /** ตำแหน่งเซลล์ เช่น (0,1) => A1 (รองรับเกิน Z) */
    private static function ref(int $colIndex, int $rowNum): string
    {
        $letters = '';
        $n = $colIndex + 1;
        while ($n > 0) {
            $n--;
            $letters = chr(65 + ($n % 26)).$letters;
            $n = intdiv($n, 26);
        }

        return $letters.$rowNum;
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
