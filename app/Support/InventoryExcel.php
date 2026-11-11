<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * Export "Fixed Inventory List", pengganti Item_master::excel() CI3.
 * Format sama: logo Monash (A1) & RISE (N1), judul di B6, tanggal di N6, header di baris 8, kolom A–P.
 *
 * Butuh: composer require phpoffice/phpspreadsheet
 * Bila package belum terpasang, otomatis memakai SimpleXlsx (data sama, tanpa logo & judul).
 */
class InventoryExcel
{
    public const HEADINGS = [
        'No', 'Inventory Number', 'Name', 'Description', 'Type/Model', 'Serial Number', 'Category',
        'Sub Category', 'Location', 'Department', 'Responsible Person', 'Purpose', 'Condition', 'Remark',
        'Purchase Date', 'Purchase Price',
    ];

    /** @return array<int, array<int, mixed>> */
    public static function rows(?int $locationId): array
    {
        $records = InventoryQuery::base($locationId)
            ->orderBy('im.inventory_number')
            ->get([
                'im.inventory_number', 'im.name', 'im.description', 'im.type', 'im.serial_number',
                'ic2.name as category_name', 'isc.name as sub_category_name', 'l.name as location_name',
                'd.name as department_name', 'staff.name as staff_name', 'it2.purpose', 'ic.name as condition_name',
                'it2.remark', 'im.purchase_date', 'im.purchase_price',
            ]);

        $rows = [];
        foreach ($records as $i => $r) {
            $rows[] = [
                $i + 1, $r->inventory_number, $r->name, $r->description, $r->type, $r->serial_number,
                $r->category_name, $r->sub_category_name, $r->location_name, $r->department_name,
                $r->staff_name, $r->purpose, $r->condition_name, $r->remark, $r->purchase_date,
                $r->purchase_price !== null ? (float) $r->purchase_price : null,
            ];
        }

        return $rows;
    }

    public static function download(?int $locationId, string $locationName): Response
    {
        $rows = self::rows($locationId);
        $fileName = 'Inventory_List_'.date('Ymd').'.xlsx';

        if (! class_exists(Spreadsheet::class)) {
            return SimpleXlsx::make('Inventory', self::HEADINGS, $rows)->download($fileName);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventory');

        // Logo (file dari folder image/ CI3, disalin ke public/image)
        foreach ([['image/monash.png', 'A1', 'Monash'], ['image/rise.png', 'N1', 'RISE']] as [$path, $cell, $name]) {
            if (is_file(public_path($path))) {
                $drawing = new Drawing();
                $drawing->setName($name);
                $drawing->setPath(public_path($path));
                $drawing->setCoordinates($cell);
                $drawing->setOffsetX(10);
                $drawing->setHeight(70);
                $drawing->setWorksheet($sheet);
            }
        }

        $sheet->setCellValue('B6', 'Fixed Inventory List');
        $sheet->setCellValue('N6', 'RISE - '.date('Y-m-d'));
        $sheet->setCellValue('B7', $locationName);
        $sheet->getStyle('B6')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('N6')->getFont()->setBold(true);

        // Header (baris 8)
        $header = 8;
        foreach (self::HEADINGS as $i => $heading) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$header, $heading);
        }
        $sheet->getStyle("A{$header}:P{$header}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0B1E3A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        // Data
        $r = $header + 1;
        foreach ($rows as $row) {
            foreach ($row as $i => $value) {
                if ($value !== null && $value !== '') {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$r, $value);
                }
            }
            $r++;
        }
        $last = max($header, $r - 1);

        $sheet->getStyle("A{$header}:P{$last}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFC8D6']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
        ]);
        $sheet->getStyle("P".($header + 1).":P{$last}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Lebar kolom: teks panjang dibungkus, lainnya otomatis
        $wide = ['D' => 45, 'F' => 25, 'L' => 35, 'N' => 35];
        foreach (range('A', 'P') as $col) {
            if (isset($wide[$col])) {
                $sheet->getColumnDimension($col)->setWidth($wide[$col]);
                $sheet->getStyle("{$col}".($header + 1).":{$col}{$last}")->getAlignment()->setWrapText(true);
            } else {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }
        $sheet->freezePane('A'.($header + 1));
        $sheet->setAutoFilter("A{$header}:P{$last}");

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
