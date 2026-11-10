<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Penulis file Excel (.xlsx) sederhana tanpa package tambahan (cukup ekstensi PHP zip).
 * Pengganti helper exportexcel CI3 (xlsBOF/xlsWriteLabel) yang menghasilkan .xls format lama.
 *
 *   return SimpleXlsx::make('Category', ['No', 'Name'], $rows)->download('item_category.xlsx');
 */
class SimpleXlsx
{
    /** @param array<int, array<int, string|int|float|null>> $rows */
    public function __construct(
        private string $sheetName,
        private array $headings,
        private iterable $rows,
    ) {}

    public static function make(string $sheetName, array $headings, iterable $rows): self
    {
        return new self($sheetName, $headings, $rows);
    }

    public function download(string $fileName): BinaryFileResponse
    {
        $path = $this->save(tempnam(sys_get_temp_dir(), 'xlsx'));

        return response()
            ->download($path, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }

    public function save(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat file Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());
        $zip->close();

        return $path;
    }

    private function sheet(): string
    {
        $rowsXml = $this->rowXml(1, $this->headings, 1);
        $widths = array_map(fn ($h) => mb_strlen((string) $h), $this->headings);
        $r = 2;

        foreach ($this->rows as $row) {
            $row = array_values((array) $row);
            foreach ($row as $i => $v) {
                $widths[$i] = max($widths[$i] ?? 0, mb_strlen((string) $v));
            }
            $rowsXml .= $this->rowXml($r++, $row, 0);
        }

        $cols = '';
        foreach ($widths as $i => $w) {
            $n = $i + 1;
            $cols .= '<col min="'.$n.'" max="'.$n.'" width="'.min(max($w + 3, 8), 80).'" customWidth="1"/>';
        }

        $lastCol = $this->col(max(count($this->headings), 1));

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'.$cols.'</cols>'
            .'<sheetData>'.$rowsXml.'</sheetData>'
            .'<autoFilter ref="A1:'.$lastCol.max($r - 1, 1).'"/>'
            .'</worksheet>';
    }

    private function rowXml(int $r, array $values, int $style): string
    {
        $cells = '';
        foreach (array_values($values) as $i => $v) {
            $ref = $this->col($i + 1).$r;
            $s = $style ? ' s="'.$style.'"' : '';

            if (is_int($v) || is_float($v)) {
                $cells .= '<c r="'.$ref.'"'.$s.'><v>'.$v.'</v></c>';
            } elseif ($v !== null && $v !== '') {
                $cells .= '<c r="'.$ref.'"'.$s.' t="inlineStr"><is><t xml:space="preserve">'.$this->xml((string) $v).'</t></is></c>';
            }
        }

        return '<row r="'.$r.'">'.$cells.'</row>';
    }

    /** 1 -> A, 27 -> AA */
    private function col(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m).$s;
            $n = intdiv($n - 1, 26);
        }

        return $s;
    }

    private function xml(string $v): string
    {
        // Data lama latin1 bisa berisi byte non-UTF8; pastikan valid supaya Excel tidak menolak file
        if (! mb_check_encoding($v, 'UTF-8')) {
            $v = mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1');
        }
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v);

        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(): string
    {
        $name = $this->xml(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $this->sheetName), 0, 31) ?: 'Sheet1');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$name.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    /** Style 0 = normal, style 1 = header tebal putih di atas navy. */
    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF0B1E3A"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
