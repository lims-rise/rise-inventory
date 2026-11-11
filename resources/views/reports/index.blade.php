@extends('layouts.app')

@section('title', 'Reports')

@section('content')
@php
    $labels = \App\Http\Controllers\ReportController::stateLabels();
    $mc = $maintenanceCounts;
    $qs = fn (array $extra = []) => http_build_query(array_merge($query, $extra));
    $money = fn ($v) => number_format($v, fmod($v, 1) ? 2 : 0);
    $stateColor = ['overdue' => '#ef4444', 'due' => '#f59e0b', 'ok' => '#14b8a6', 'no_frequency' => '#94a3b8', 'never' => '#8b5cf6'];
    $qualityIssues = collect($quality)->sum('count');
@endphp

<div class="rp-head">
    <div class="rp-head-title">
        <span class="rp-head-icon"><i class="bi bi-bar-chart-line"></i></span>
        <div>
            <ol class="rp-crumb">
                <li><a href="{{ url('welcome') }}"><i class="bi bi-house-door"></i></a></li>
                <li>Reports</li>
            </ol>
            <h1>Inventory Reports</h1>
            <p>{{ $locationName ?: 'All locations' }} · {{ number_format($summary['total']) }} {{ \Illuminate\Support\Str::plural('item', $summary['total']) }} match the filters</p>
        </div>
    </div>
    <div class="rp-head-actions">
        <button type="button" class="btn btn-default" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>

{{-- ================= Filter (berlaku untuk semua tab) ================= --}}
<form method="GET" action="{{ route('reports.index') }}" class="rp-card rr-filters" id="report-filters">
    <input type="hidden" name="tab" id="f-tab" value="{{ $tab }}">
    <div class="rr-filter-grid">
        <div class="form-group">
            <label for="f-cat">Category</label>
            <select id="f-cat" name="cat" class="form-control rr-auto" data-placeholder="All categories" data-allow-clear="true">
                <option value=""></option>
                @foreach ($categories as $id => $name)<option value="{{ $id }}" @selected($filters['cat'] == $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="f-subcat">Sub Category</label>
            <select id="f-subcat" name="subcat" class="form-control rr-auto" data-placeholder="All sub categories" data-allow-clear="true">
                <option value=""></option>
                @foreach ($subCategories as $id => $name)<option value="{{ $id }}" @selected($filters['subcat'] == $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="f-fund">Fund</label>
            <select id="f-fund" name="fund" class="form-control rr-auto" data-placeholder="All funds" data-allow-clear="true">
                <option value=""></option>
                @foreach ($funds as $id => $name)<option value="{{ $id }}" @selected($filters['fund'] == $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="f-loc">Location Detail</label>
            <select id="f-loc" name="loc" class="form-control rr-auto" data-placeholder="All location details" data-allow-clear="true">
                <option value=""></option>
                @foreach ($locationDetails as $id => $name)<option value="{{ $id }}" @selected($filters['loc'] == $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="f-con">Condition</label>
            <select id="f-con" name="con" class="form-control rr-auto" data-placeholder="All conditions" data-allow-clear="true">
                <option value=""></option>
                @foreach ($conditions as $id => $name)<option value="{{ $id }}" @selected($filters['con'] === $id)>{{ $name }}</option>@endforeach
                <option value="none" @selected($filters['con'] === 'none')>No history yet</option>
            </select>
        </div>
        <div class="form-group">
            <label for="f-date1">Purchase date range</label>
            <div class="rr-dates">
                <input type="date" id="f-date1" name="date1" class="form-control" value="{{ $filters['date1'] }}" aria-label="From">
                <span>to</span>
                <input type="date" id="f-date2" name="date2" class="form-control" value="{{ $filters['date2'] }}" aria-label="Until">
            </div>
        </div>
    </div>
    @if ($chips)
        <div class="rr-chips">
            @foreach ($chips as $key => $label)
                <a href="{{ route('reports.index') }}?{{ http_build_query(array_merge(array_diff_key($query, [$key => 1]), ['tab' => $tab])) }}" class="rr-chip" title="Remove filter">
                    {{ $label }} <i class="bi bi-x"></i>
                </a>
            @endforeach
            <a href="{{ route('reports.index', ['tab' => $tab]) }}" class="rr-chip rr-chip-clear"><i class="bi bi-arrow-counterclockwise"></i> Clear all</a>
        </div>
    @endif
    <noscript><button type="submit" class="btn btn-primary">Apply</button></noscript>
</form>

{{-- ================= Tab ================= --}}
<ul class="rr-tabs" role="tablist">
    <li class="{{ $tab === 'inventory' ? 'active' : '' }}"><a href="#tab-inventory" data-toggle="tab" data-tab="inventory" role="tab"><i class="bi bi-table"></i> Inventory <span>{{ number_format($summary['total']) }}</span></a></li>
    <li class="{{ $tab === 'summary' ? 'active' : '' }}"><a href="#tab-summary" data-toggle="tab" data-tab="summary" role="tab"><i class="bi bi-pie-chart"></i> Summary</a></li>
    <li class="{{ $tab === 'maintenance' ? 'active' : '' }}"><a href="#tab-maintenance" data-toggle="tab" data-tab="maintenance" role="tab"><i class="bi bi-tools"></i> Maintenance due
        @if (($mc['overdue'] ?? 0) + ($mc['due'] ?? 0))<span class="rr-alert">{{ ($mc['overdue'] ?? 0) + ($mc['due'] ?? 0) }}</span>@endif</a></li>
    <li class="{{ $tab === 'quality' ? 'active' : '' }}"><a href="#tab-quality" data-toggle="tab" data-tab="quality" role="tab"><i class="bi bi-clipboard-check"></i> Data quality
        @if ($qualityIssues)<span>{{ number_format($qualityIssues) }}</span>@endif</a></li>
</ul>

<div class="tab-content">
    {{-- ===== Inventory (report CI3) ===== --}}
    <div class="tab-pane {{ $tab === 'inventory' ? 'active' : '' }}" id="tab-inventory" role="tabpanel">
        <div class="rp-card">
            <div class="rf-card-head">
                <div>
                    <h3 class="rf-section-title"><i class="bi bi-table"></i> Fixed Inventory List</h3>
                    <p class="rr-sub">The Excel file has all 28 columns: purchase, location, responsible person and latest maintenance, plus a Summary sheet.</p>
                </div>
                <div class="rf-card-actions">
                    <a href="{{ route('reports.excel') }}?{{ $qs() }}" class="btn rp-btn-excel btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
                </div>
            </div>
            <table class="table rp-table dt-responsive" id="report-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Inventory Number</th><th>Category</th><th>Sub Category</th><th>Name</th>
                        <th>Purchase Date</th><th>Fund</th><th>Location</th><th>Responsible Person</th><th>Condition</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- ===== Summary ===== --}}
    <div class="tab-pane {{ $tab === 'summary' ? 'active' : '' }}" id="tab-summary" role="tabpanel">
        <div class="rr-kpis">
            <div class="rp-card rr-kpi"><small>Items</small><b>{{ number_format($summary['total']) }}</b><span>{{ number_format($summary['units']) }} units by quantity</span></div>
            <div class="rp-card rr-kpi" style="--c:#14b8a6"><small>Good condition</small><b>{{ $summary['goodPct'] }}%</b><span>{{ number_format($summary['good']) }} items</span></div>
            <div class="rp-card rr-kpi" style="--c:#8b5cf6"><small>No responsible person</small><b>{{ number_format($summary['noHolder']) }}</b><span>items without history</span></div>
            <div class="rp-card rr-kpi" style="--c:#f59e0b"><small>Average age</small><b>{{ $summary['avgAge'] !== null ? $summary['avgAge'] : '—' }}</b><span>years since purchase</span></div>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="rp-card">
                    <h3 class="rf-section-title"><i class="bi bi-graph-up"></i> Items purchased per year</h3>
                    @if ($summary['byYear']->isEmpty())
                        <div class="rp-empty"><i class="bi bi-calendar"></i>No purchase dates recorded.</div>
                    @else
                        <div id="chart-year" style="height:260px"></div>
                    @endif
                </div>
            </div>
            <div class="col-lg-5">
                <div class="rp-card">
                    <h3 class="rf-section-title"><i class="bi bi-cash-stack"></i> Purchase value</h3>
                    @forelse ($summary['values'] as $v)
                        <div class="rr-money"><span>{{ $v['currency'] }}</span><b>{{ $money($v['total']) }}</b><small>{{ $v['n'] }} priced {{ \Illuminate\Support\Str::plural('item', $v['n']) }}</small></div>
                    @empty
                        <div class="rp-empty"><i class="bi bi-cash"></i>No purchase prices recorded.</div>
                    @endforelse
                    <p class="rr-sub">Totals are shown per currency and are not converted.</p>
                </div>
            </div>
        </div>

        <div class="rp-card">
            <h3 class="rf-section-title"><i class="bi bi-grid-3x3-gap"></i> By category</h3>
            <div class="table-responsive">
                <table class="table rp-table rr-tree">
                    <thead><tr><th>Category / Sub category</th><th class="text-right">Items</th><th style="width:22%">Share</th>
                        @foreach ($summary['currencies'] as $c)<th class="text-right">Value {{ $c }}</th>@endforeach</tr></thead>
                    <tbody>
                        @forelse ($summary['byCategory'] as $cat)
                            <tr class="rr-group">
                                <td>{{ $cat['name'] }}</td>
                                <td class="text-right">{{ number_format($cat['n']) }}</td>
                                <td><div class="rr-bar"><span style="width:{{ $summary['total'] ? round($cat['n'] / $summary['total'] * 100, 1) : 0 }}%"></span></div></td>
                                @foreach ($summary['currencies'] as $c)<td class="text-right">{{ $cat['value'][$c] ? $money($cat['value'][$c]) : '—' }}</td>@endforeach
                            </tr>
                            @foreach ($cat['subs'] as $sub)
                                <tr>
                                    <td class="rr-indent">{{ $sub['name'] }}</td>
                                    <td class="text-right">{{ number_format($sub['n']) }}</td>
                                    <td><div class="rr-bar rr-bar-light"><span style="width:{{ $summary['total'] ? round($sub['n'] / $summary['total'] * 100, 1) : 0 }}%"></span></div></td>
                                    @foreach ($summary['currencies'] as $c)<td class="text-right">{{ $sub['value'][$c] ? $money($sub['value'][$c]) : '—' }}</td>@endforeach
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="{{ 3 + count($summary['currencies']) }}"><div class="rp-empty">No items match the filters.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            @foreach ([['By location detail', 'bi-building', $summary['byLocation']], ['By condition', 'bi-clipboard2-pulse', $summary['byCondition']], ['By fund', 'bi-bank', $summary['byFund']]] as [$title, $icon, $rows])
                <div class="col-lg-4">
                    <div class="rp-card">
                        <h3 class="rf-section-title"><i class="bi {{ $icon }}"></i> {{ $title }}</h3>
                        <ul class="rr-list">
                            @forelse ($rows as $row)
                                <li>
                                    <span class="rr-list-name">
                                        @isset($row['id'])<i class="sf-dot" style="background: {{ \App\Support\ConditionStyle::color($row['id']) }}"></i>@endisset
                                        {{ $row['name'] }}
                                    </span>
                                    <b>{{ number_format($row['n']) }}</b>
                                    <div class="rr-bar"><span style="width:{{ $summary['total'] ? round($row['n'] / $summary['total'] * 100, 1) : 0 }}%"></span></div>
                                </li>
                            @empty
                                <li class="text-muted">No data.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== Maintenance due ===== --}}
    <div class="tab-pane {{ $tab === 'maintenance' ? 'active' : '' }}" id="tab-maintenance" role="tabpanel">
        <div class="rr-kpis rr-kpis-5">
            @foreach (['overdue', 'due', 'never', 'no_frequency', 'ok'] as $state)
                <button type="button" class="rp-card rr-kpi rr-state" data-state="{{ $state }}" style="--c: {{ $stateColor[$state] }}">
                    <small>{{ $labels[$state] }}</small><b>{{ $mc[$state] ?? 0 }}</b>
                </button>
            @endforeach
        </div>
        <div class="rp-card">
            <div class="rf-card-head">
                <div>
                    <h3 class="rf-section-title"><i class="bi bi-tools"></i> Maintenance schedule</h3>
                    <p class="rr-sub">Items with a maintenance record or "Scheduled check = Yes". Next service = last service + frequency.
                        "Due soon" starts the number of weeks set in "Schedule prior" (default {{ \App\Services\ReportService::DUE_SOON_WEEKS_DEFAULT }}).</p>
                </div>
                <div class="rf-card-actions">
                    <a href="{{ route('reports.excel_maintenance') }}?{{ $qs() }}" class="btn rp-btn-excel btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
                </div>
            </div>
            @if ($maintenance->isEmpty())
                <div class="rp-empty"><i class="bi bi-tools"></i>No items with a maintenance schedule.</div>
            @else
                <div class="table-responsive">
                    <table class="table rp-table" id="maintenance-table">
                        <thead><tr><th>Status</th><th>Item</th><th>Next service</th><th>Last service</th><th>Service type</th><th>Frequency</th><th>PIC</th><th>Location</th></tr></thead>
                        <tbody>
                            @foreach ($maintenance as $m)
                                <tr data-state="{{ $m->state }}">
                                    <td><span class="rf-badge" style="--c:{{ $stateColor[$m->state] }}">{{ $labels[$m->state] }}</span></td>
                                    <td><a href="{{ route('item_master.read', ['id' => $m->inventory_number]) }}" class="rf-code">{{ $m->inventory_number }}</a>
                                        <div class="sf-item-name">{{ $m->name }}</div></td>
                                    <td class="rf-nowrap">
                                        @if ($m->next_service)
                                            <b>{{ \Illuminate\Support\Carbon::parse($m->next_service)->format('d M Y') }}</b><br>
                                            <small class="{{ $m->days < 0 ? 'text-danger' : 'text-muted' }}">
                                                {{ $m->days < 0 ? abs($m->days).' days overdue' : ($m->days === 0 ? 'today' : 'in '.$m->days.' days') }}
                                            </small>
                                        @else — @endif
                                    </td>
                                    <td class="rf-nowrap">{{ $m->last_service_date ? \Illuminate\Support\Carbon::parse($m->last_service_date)->format('d M Y') : '—' }}</td>
                                    <td>{{ $m->services ?: '—' }}</td>
                                    <td>{{ $m->frequency ?: '—' }}</td>
                                    <td>{{ $m->staff_pic ?: '—' }}</td>
                                    <td>{{ $m->location_detail ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ===== Data quality ===== --}}
    <div class="tab-pane {{ $tab === 'quality' ? 'active' : '' }}" id="tab-quality" role="tabpanel">
        <div class="rp-card">
            <div class="rf-card-head">
                <div>
                    <h3 class="rf-section-title"><i class="bi bi-clipboard-check"></i> Missing information</h3>
                    <p class="rr-sub">Open a check to see which items need completing. Click an item to edit it.</p>
                </div>
                <div class="rf-card-actions">
                    <a href="{{ route('reports.excel_quality') }}?{{ $qs() }}" class="btn rp-btn-excel btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
                </div>
            </div>
            <div class="rr-checks">
                @foreach ($quality as $key => $check)
                    @php $pct = $summary['total'] ? round(($summary['total'] - $check['count']) / $summary['total'] * 100) : 100; @endphp
                    <details class="rr-check {{ $check['count'] ? '' : 'rr-check-ok' }}">
                        <summary>
                            <span class="rr-ring" style="--p: {{ $pct }}"><b>{{ $pct }}%</b></span>
                            <span class="rr-check-text"><b>{{ $check['label'] }}</b><small>{{ $check['hint'] }}</small></span>
                            <span class="rr-check-count">{{ $check['count'] ? number_format($check['count']) : '' }}
                                @if (! $check['count'])<i class="bi bi-check-circle-fill"></i>@endif</span>
                        </summary>
                        @if ($check['count'])
                            <ul class="rr-check-items">
                                @foreach ($check['items'] as $it)
                                    <li><a href="{{ route('item_master.edit', ['id' => $it->inventory_number]) }}"><span class="rf-code">{{ $it->inventory_number }}</span> {{ $it->name }}</a>
                                        <small>{{ $it->sub_category }}</small></li>
                                @endforeach
                                @if ($check['count'] > $check['items']->count())
                                    <li class="text-muted">… and {{ $check['count'] - $check['items']->count() }} more. Export Excel for the full list.</li>
                                @endif
                            </ul>
                        @endif
                    </details>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script defer src="https://code.highcharts.com/highcharts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var years = @json($summary['byYear']);
    if (!window.Highcharts || !years.length || !document.getElementById('chart-year')) return;
    Highcharts.chart('chart-year', {
        chart: { type: 'column', backgroundColor: 'transparent', style: { fontFamily: getComputedStyle(document.documentElement).getPropertyValue('--rise-font') } },
        title: { text: null }, credits: { enabled: false }, legend: { enabled: false },
        xAxis: { categories: years.map(function (y) { return y.year; }), lineColor: '#e6ecf4', labels: { style: { color: '#6b7a90', fontWeight: '600' } } },
        yAxis: { title: { text: null }, allowDecimals: false, gridLineColor: '#eef2f8', gridLineDashStyle: 'Dash' },
        tooltip: { backgroundColor: '#0b1e3a', borderWidth: 0, borderRadius: 12, shadow: false, style: { color: '#fff' }, pointFormat: '<b>{point.y}</b> items' },
        plotOptions: { column: { borderRadius: 8, borderWidth: 0, maxPointWidth: 48,
            color: { linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 }, stops: [[0, '#4c9bff'], [1, '#1f6feb']] },
            dataLabels: { enabled: true, style: { textOutline: 'none', color: '#14213d', fontWeight: '800' } } } },
        series: [{ name: 'Items', data: years.map(function (y) { return y.n; }) }]
    });
});

$(function () {
    var $form = $('#report-filters');
    var params = @json($query);

    // Filter otomatis diterapkan saat diubah (CI3: harus klik Refresh)
    $form.find('select.rr-auto').each(function () {
        $(this).select2({ width: '100%', placeholder: $(this).data('placeholder'), allowClear: true });
    });
    $form.on('change', 'select.rr-auto, input[type=date]', function () {
        if (this.id === 'f-cat') $('#f-subcat').val(''); // sub kategori lama mungkin bukan milik kategori baru
        $form.trigger('submit');
    });
    $form.on('submit', function () { // jangan kirim parameter kosong supaya URL rapi
        $form.find('select, input').each(function () { if (!this.value) this.disabled = true; });
    });

    // Tab: ingat pilihan di URL (bisa dibagikan / di-bookmark)
    $('[data-tab]').on('shown.bs.tab', function () {
        var tab = $(this).data('tab');
        $('#f-tab').val(tab);
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        history.replaceState(null, '', url.toString());
        if (tab === 'inventory') table.columns.adjust().responsive.recalc();
        if (window.Highcharts) Highcharts.charts.forEach(function (c) { if (c) c.reflow(); });
    });

    // Tabel Inventory
    var dash = '<span class="text-muted">—</span>';
    var text = function (d) { return d || dash; };
    var table = $('#report-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        pageLength: 25, lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        order: [[0, 'asc']],
        dom: "<'rp-dt-top'lf>t<'rp-dt-bottom'ip>",
        ajax: { url: @json(route('reports.json')), type: 'POST', global: false, data: function (d) { return $.extend(d, params); } },
        columns: [
            { data: 'inventory_number', className: 'rf-nowrap', render: function (d, t, r) { return '<a href="' + r.url + '" class="rf-code">' + d + '</a>'; } },
            { data: 'category', render: text }, { data: 'sub_category', render: text },
            { data: 'name', className: 'rp-strong' }, { data: 'purchase_date', className: 'rf-nowrap', render: text },
            { data: 'fund', render: text }, { data: 'location_all', render: text }, { data: 'staffname', render: text },
            { data: 'condition', render: function (d, t, r) { return d ? '<span class="rf-badge" style="--c:' + r.condition_color + '">' + d + '</span>' : '<span class="rf-badge rf-badge-muted">No history</span>'; } }
        ],
        language: {
            processing: '<span class="rp-spinner"></span> Loading…', search: '', searchPlaceholder: 'Search in results…',
            lengthMenu: 'Show _MENU_', info: 'Showing _START_–_END_ of _TOTAL_', infoEmpty: 'No items', infoFiltered: '(filtered from _MAX_)',
            zeroRecords: '<div class="rp-empty"><i class="bi bi-search"></i>No matching items</div>',
            emptyTable: '<div class="rp-empty"><i class="bi bi-funnel"></i>No items match the filters.</div>',
            paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
        }
    });
    var timer;
    $('#report-table_filter input').off('.DT').on('input.DT', function () {
        var value = this.value;
        clearTimeout(timer);
        timer = setTimeout(function () { table.search(value).draw(); }, 350);
    });

    // Maintenance: klik kartu status untuk menyaring tabel
    $('.rr-state').on('click', function () {
        var state = $(this).data('state');
        var active = !$(this).hasClass('active');
        $('.rr-state').removeClass('active');
        $(this).toggleClass('active', active);
        $('#maintenance-table tbody tr').each(function () {
            $(this).toggle(!active || $(this).data('state') === state);
        });
    });
});
</script>
@endpush
