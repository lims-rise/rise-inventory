<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\ItemFund;
use App\Models\ItemSubCategory;
use App\Models\Location;
use App\Models\LocationDetail;
use App\Services\LocationContext;
use App\Services\ReportService;
use App\Support\ConditionStyle;
use App\Support\DataTables;
use App\Support\FormOptions;
use App\Support\SimpleXlsx;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengganti controller CI3 Reports (URL & nama parameter sama: date1, date2, cat, subcat, fund, loc, con).
 * Semua level boleh melihat & mengekspor (sama dengan CI3).
 */
class ReportController extends Controller
{
    public const TABS = ['inventory', 'summary', 'maintenance', 'quality'];

    /** Kolom Excel Inventory, sama dengan Excel CI3 (27 kolom). */
    private const EXCEL_COLUMNS = [
        'No' => null, 'Inventory_number' => 'inventory_number', 'Name' => 'name', 'Description' => 'description',
        'Type/Model' => 'type', 'Serial_number' => 'serial_number', 'Purchase_date' => 'purchase_date',
        'Purchase_price' => 'purchase_price', 'Currency' => 'currency', 'Category' => 'category',
        'Sub_category' => 'sub_category', 'Fund' => 'fund', 'Manufacture' => 'manufacture', 'Supplier' => 'supplier',
        'Qty' => 'qty', 'Location' => 'location', 'Location_detail' => 'location_detail',
        'Responsible_Person' => 'staffname', 'Condition' => 'condition', 'Purpose' => 'purpose', 'Remark' => 'remark',
        'Services_type' => 'services', 'Frequency' => 'frequency', 'Last_service_date' => 'last_service_date',
        'Next_service_date' => 'next_service', 'Provider_contact' => 'provider_contact', 'Staff_PIC' => 'staff_pic',
        'Schedule prior(weeks)' => 'service_schedule',
    ];

    public function __construct(private LocationContext $locations) {}

    private function service(Request $request): ReportService
    {
        return new ReportService($this->locations->currentId(Auth::user()), ReportService::filtersFrom($request));
    }

    public function index(Request $request): View
    {
        $report = $this->service($request);
        $f = $report->filters;
        $locationId = $this->locations->currentId(Auth::user());
        $tab = in_array($request->input('tab'), self::TABS, true) ? $request->input('tab') : 'inventory';

        $categories = ItemCategory::where('status', 1)->orderBy('name')->pluck('name', 'id');
        $subCategories = ItemSubCategory::where('status', 1)
            ->when($f['cat'], fn ($q, $v) => $q->where('category_id', $v))
            ->orderBy('name')->pluck('name', 'id');
        $funds = ItemFund::where('status', 1)->orderBy('name')->pluck('name', 'id');
        $locationDetails = LocationDetail::where('status', 1)->where('id_location', $locationId)->orderBy('id')->pluck('name', 'id');
        $conditions = FormOptions::conditions();

        // Label filter aktif (chip di atas laporan)
        $chips = array_filter([
            'cat' => $f['cat'] ? 'Category: '.($categories[$f['cat']] ?? '#'.$f['cat']) : null,
            'subcat' => $f['subcat'] ? 'Sub category: '.($subCategories[$f['subcat']] ?? '#'.$f['subcat']) : null,
            'fund' => $f['fund'] ? 'Fund: '.($funds[$f['fund']] ?? '#'.$f['fund']) : null,
            'loc' => $f['loc'] ? 'Location: '.($locationDetails[$f['loc']] ?? '#'.$f['loc']) : null,
            'con' => $f['con'] === 'none' ? 'Condition: No history'
                : ($f['con'] ? 'Condition: '.($conditions[$f['con']] ?? '#'.$f['con']) : null),
            'date1' => $f['date1'] ? 'Purchased from '.$f['date1'] : null,
            'date2' => $f['date2'] ? 'Purchased until '.$f['date2'] : null,
        ]);

        $maintenance = $report->maintenance();

        return view('reports.index', [
            'tab' => $tab,
            'filters' => $f,
            'chips' => $chips,
            'query' => array_filter($f, fn ($v) => $v !== null),
            'categories' => $categories,
            'subCategories' => $subCategories,
            'funds' => $funds,
            'locationDetails' => $locationDetails,
            'conditions' => $conditions,
            'locationName' => (string) Location::whereKey($locationId)->value('name'),
            'summary' => $report->summary(),
            'maintenance' => $maintenance,
            'maintenanceCounts' => $maintenance->countBy('state')->all(),
            'quality' => $report->quality(),
        ]);
    }

    /** Tabel Inventory (DataTables server-side), pengganti reports/json CI3. */
    public function json(Request $request): JsonResponse
    {
        $columns = [
            'inventory_number' => 'im.inventory_number', 'category' => 'ic2.name', 'sub_category' => 'isc.name',
            'name' => 'im.name', 'purchase_date' => 'im.purchase_date', 'fund' => 'fund.name',
            'location_all' => 'ldet.name', 'staffname' => 'staff.name', 'condition' => 'ic.name',
        ];

        $query = $this->service($request)->query()->select([
            'im.inventory_number', 'ic2.name as category', 'isc.name as sub_category', 'im.name', 'im.purchase_date',
            'fund.name as fund', 'loc.name as location', 'ldet.name as location_detail', 'staff.name as staffname',
            'it2.condition_id', 'ic.name as condition',
        ]);

        return DataTables::make($request, $query)
            ->searchable(array_merge(array_values($columns), ['im.serial_number', 'im.type']))
            ->orderable($columns)
            ->defaultOrder('im.inventory_number', 'asc')
            ->map(fn ($r) => [
                'inventory_number' => e($r->inventory_number),
                'category' => e((string) $r->category),
                'sub_category' => e((string) $r->sub_category),
                'name' => e((string) $r->name),
                'purchase_date' => $r->purchase_date ? e($r->purchase_date) : '',
                'fund' => e((string) $r->fund),
                // CI3: location_all = "Lokasi (Detail)"
                'location_all' => e(trim(($r->location ?? '').($r->location_detail ? ' ('.$r->location_detail.')' : ''))),
                'staffname' => e((string) $r->staffname),
                'condition' => e((string) $r->condition),
                'condition_color' => ConditionStyle::color($r->condition_id),
                'url' => route('item_master.read', ['id' => $r->inventory_number]),
            ])
            ->response();
    }

    // ================================================================== Excel

    /** GET reports/excel?…  27 kolom seperti CI3 + sheet Summary. */
    public function excel(Request $request): Response
    {
        $report = $this->service($request);
        $rows = $report->inventoryRows()->values()->map(function ($r, $i) {
            $line = [];
            foreach (self::EXCEL_COLUMNS as $key) {
                $line[] = $key === null ? $i + 1 : $this->cell($r->$key ?? null, $key);
            }

            return $line;
        })->all();

        $fileName = 'RISE_Inventory_Report_'.date('Ymd').'.xlsx';
        $headings = array_keys(self::EXCEL_COLUMNS);

        if (! class_exists(Spreadsheet::class)) {
            return SimpleXlsx::make('Inventory Report', $headings, $rows)->download($fileName);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventory Report');
        $lastCol = Coordinate::stringFromColumnIndex(count($headings));

        $this->logos($sheet, 'A1', 'N1');
        $sheet->setCellValue('B6', 'Fixed Inventory List');
        $sheet->getStyle('B6')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('N6', 'RISE - '.date('Y-m-d'));
        $sheet->setCellValue('B7', $this->filterText($request));
        $sheet->getStyle('B7')->getFont()->setItalic(true)->getColor()->setARGB('FF6B7A90');

        $this->table($sheet, 8, $headings, $rows);
        foreach (['D' => 45, 'F' => 25, 'T' => 35, 'U' => 35] as $col => $width) {
            $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth($width);
            $sheet->getStyle($col.'9:'.$col.max(9, 8 + count($rows)))->getAlignment()->setWrapText(true);
        }
        $sheet->getStyle('H9:H'.max(9, 8 + count($rows)))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setAutoFilter('A8:'.$lastCol.max(8, 8 + count($rows)));

        // Sheet Summary
        $summary = $report->summary();
        $sum = $spreadsheet->createSheet();
        $sum->setTitle('Summary');
        $sum->setCellValue('A1', 'Inventory summary');
        $sum->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sum->setCellValue('A2', $this->filterText($request));
        $r = 4;
        foreach ([
            ['Items', $summary['total']], ['Units (quantity)', $summary['units']],
            ['Good condition', $summary['good'].' ('.$summary['goodPct'].'%)'],
            ['Without responsible person', $summary['noHolder']],
            ['Average age (years)', $summary['avgAge'] ?? '—'],
        ] as [$label, $value]) {
            $sum->setCellValue('A'.$r, $label);
            $sum->setCellValue('B'.$r, $value);
            $r++;
        }
        foreach ($summary['values'] as $v) {
            $sum->setCellValue('A'.$r, 'Purchase value '.$v['currency']);
            $sum->setCellValue('B'.$r, $v['total']);
            $sum->getStyle('B'.$r)->getNumberFormat()->setFormatCode('#,##0.00');
            $r++;
        }

        $r += 1;
        $catRows = [];
        foreach ($summary['byCategory'] as $cat) {
            foreach ($cat['subs'] as $sub) {
                $line = [$cat['name'], $sub['name'], $sub['n']];
                foreach ($summary['currencies'] as $c) {
                    $line[] = $sub['value'][$c] ?: null;
                }
                $catRows[] = $line;
            }
        }
        $r = $this->table($sum, $r, array_merge(['Category', 'Sub category', 'Items'],
            array_map(fn ($c) => 'Value '.$c, $summary['currencies'])), $catRows) + 2;

        $r = $this->table($sum, $r, ['Location detail', 'Items'],
            $summary['byLocation']->map(fn ($x) => [$x['name'], $x['n']])->all()) + 2;
        $r = $this->table($sum, $r, ['Condition', 'Items'],
            $summary['byCondition']->map(fn ($x) => [$x['name'], $x['n']])->all()) + 2;
        $this->table($sum, $r, ['Fund', 'Items'], $summary['byFund']->map(fn ($x) => [$x['name'], $x['n']])->all());

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn () => $writer->save('php://output'), $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function excelMaintenance(Request $request): Response
    {
        $labels = $this->stateLabels();
        $rows = $this->service($request)->maintenance()->values()->map(fn ($r, $i) => [
            $i + 1, $r->inventory_number, $r->name, $labels[$r->state], $r->next_service, $r->days,
            $r->last_service_date, $r->services, $r->frequency, $r->staff_pic, $r->provider_contact,
            $r->location_detail, $r->holder,
        ])->all();

        return SimpleXlsx::make('Maintenance due', ['No', 'Inventory Number', 'Name', 'Status', 'Next service', 'Days left',
            'Last service', 'Service type', 'Frequency', 'PIC', 'Provider contact', 'Location detail', 'Responsible person'], $rows)
            ->download('RISE_Maintenance_Due_'.date('Ymd').'.xlsx');
    }

    public function excelQuality(Request $request): Response
    {
        $rows = $this->service($request)->qualityRows()->values()
            ->map(fn ($r, $i) => array_merge([$i + 1], $r))->all();

        return SimpleXlsx::make('Data quality', ['No', 'Missing', 'Inventory Number', 'Name', 'Sub category', 'Location detail'], $rows)
            ->download('RISE_Data_Quality_'.date('Ymd').'.xlsx');
    }

    // ================================================================== Helpers

    public static function stateLabels(): array
    {
        return [
            'overdue' => 'Overdue', 'due' => 'Due soon', 'ok' => 'On schedule',
            'no_frequency' => 'No frequency set', 'never' => 'Never serviced',
        ];
    }

    private function cell(mixed $value, string $key): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (in_array($key, ['purchase_price'], true)) {
            return (float) $value;
        }
        if (in_array($key, ['qty', 'service_schedule'], true)) {
            return (int) $value;
        }

        return (string) $value;
    }

    private function filterText(Request $request): string
    {
        $f = ReportService::filtersFrom($request);
        $location = (string) Location::whereKey($this->locations->currentId(Auth::user()))->value('name');
        $parts = ['Location: '.($location ?: '—')];
        if ($f['cat']) $parts[] = 'Category: '.ItemCategory::whereKey($f['cat'])->value('name');
        if ($f['subcat']) $parts[] = 'Sub category: '.ItemSubCategory::whereKey($f['subcat'])->value('name');
        if ($f['fund']) $parts[] = 'Fund: '.ItemFund::whereKey($f['fund'])->value('name');
        if ($f['loc']) $parts[] = 'Location detail: '.LocationDetail::whereKey($f['loc'])->value('name');
        if ($f['con'] === 'none') $parts[] = 'Condition: No history';
        elseif ($f['con']) $parts[] = 'Condition: '.\App\Models\ItemCondition::whereKey($f['con'])->value('name');
        if ($f['date1'] || $f['date2']) $parts[] = 'Purchase date: '.($f['date1'] ?: '…').' to '.($f['date2'] ?: '…');

        return implode('  ·  ', $parts);
    }

    private function logos(Worksheet $sheet, string $left, string $right): void
    {
        foreach ([['image/monash.png', $left, 'Monash'], ['image/rise.png', $right, 'RISE']] as [$path, $cell, $name]) {
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
    }

    /** Tulis tabel bergaya (header navy + border) mulai baris $start. Mengembalikan baris terakhir. */
    private function table(Worksheet $sheet, int $start, array $headings, array $rows): int
    {
        $lastCol = Coordinate::stringFromColumnIndex(max(1, count($headings)));
        foreach ($headings as $i => $h) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$start, $h);
        }
        $r = $start + 1;
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $value) {
                if ($value !== null && $value !== '') {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$r, $value);
                }
            }
            $r++;
        }
        $last = max($start, $r - 1);

        $sheet->getStyle("A{$start}:{$lastCol}{$start}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0B1E3A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle("A{$start}:{$lastCol}{$last}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFC8D6']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
        ]);
        for ($i = 1; $i <= count($headings); $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            if (! $sheet->getColumnDimension($col)->getWidth() || $sheet->getColumnDimension($col)->getWidth() < 0) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        return $last;
    }
}
