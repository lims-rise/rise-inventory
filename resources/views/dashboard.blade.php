@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    /* ===== Dashboard RISE (prefix rd- supaya tidak bentrok dengan AdminLTE) ===== */
    .rd { --ink:#14213d; --muted:#6b7a90; --line:#e6ecf4; --blue:#1f6feb; --sky:#8fc1ff; font-family: var(--rise-font); }
    .rd .row { margin-left: -10px; margin-right: -10px; }
    .rd .row > [class*="col-"] { padding-left: 10px; padding-right: 10px; }
    .rd-gap { margin-bottom: 20px; }

    /* Hero */
    .rd-hero {
        position: relative; overflow: hidden; border-radius: 22px; color: #fff;
        padding: 30px 32px; margin-bottom: 20px;
        background:
            linear-gradient(115deg, rgba(8,22,46,.94) 0%, rgba(13,40,82,.86) 55%, rgba(31,111,235,.72) 100%),
            url("{{ asset('images/login-bg.svg') }}") center / cover,
            #0b1e3a;
        box-shadow: 0 18px 40px rgba(11,30,58,.22);
    }
    .rd-hero::after {
        content: ""; position: absolute; width: 380px; height: 380px; right: -90px; top: -150px; border-radius: 50%;
        background: radial-gradient(circle, rgba(143,193,255,.45), rgba(143,193,255,0) 65%);
        pointer-events: none;
    }
    .rd-hero-grid { position: relative; z-index: 1; display: flex; gap: 28px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
    .rd-eyebrow {
        display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px;
        font-size: 11.5px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.16);
    }
    .rd-live { width: 8px; height: 8px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 0 rgba(52,211,153,.7); animation: rd-pulse 2s infinite; }
    .rd-hero h1 { font-size: clamp(24px, 3vw, 34px); font-weight: 800; letter-spacing: -.02em; margin: 16px 0 8px; }
    .rd-hero p { margin: 0; color: rgba(255,255,255,.75); font-size: 15px; }
    .rd-hero p i { color: var(--sky); margin-right: 6px; }
    .rd-hero-stats { display: flex; gap: 14px; flex-wrap: wrap; }
    .rd-glass {
        min-width: 150px; padding: 16px 20px; border-radius: 16px;
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14);
        backdrop-filter: blur(8px);
    }
    .rd-glass.big { min-width: 200px; background: rgba(255,255,255,.12); }
    .rd-glass small { display: block; font-size: 11.5px; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.6); font-weight: 700; }
    .rd-glass strong { display: block; font-size: 30px; font-weight: 800; line-height: 1.15; margin-top: 4px; }
    .rd-glass.big strong { font-size: 44px; background: linear-gradient(90deg, #fff, var(--sky)); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .rd-glass span { font-size: 12.5px; color: rgba(255,255,255,.65); }

    /* Kartu umum */
    .rd-card { background: #fff; border-radius: 18px; box-shadow: 0 1px 2px rgba(16,41,79,.06), 0 10px 28px rgba(16,41,79,.06); height: 100%; }
    .rd-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 20px 22px 0; flex-wrap: wrap; }
    .rd-card-head h3 { margin: 0; font-size: 16px; font-weight: 800; color: var(--ink); }
    .rd-card-head p { margin: 4px 0 0; font-size: 13px; color: var(--muted); }
    .rd-card-body { padding: 14px 22px 22px; }

    /* KPI */
    .rd-kpi { position: relative; padding: 20px 22px; overflow: hidden; transition: transform .18s, box-shadow .18s; }
    .rd-kpi:hover { transform: translateY(-3px); box-shadow: 0 2px 4px rgba(16,41,79,.06), 0 18px 36px rgba(16,41,79,.12); }
    .rd-kpi::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 3px; background: var(--c); opacity: .9; }
    .rd-kpi-top { display: flex; align-items: center; justify-content: space-between; }
    .rd-kpi-icon {
        width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; font-size: 21px;
        color: var(--c); background: color-mix(in srgb, var(--c) 13%, #fff);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--c) 22%, #fff);
    }
    .rd-kpi-pct { font-size: 12.5px; font-weight: 700; color: var(--c); background: color-mix(in srgb, var(--c) 10%, #fff); padding: 4px 10px; border-radius: 999px; }
    .rd-kpi-label { margin-top: 16px; font-size: 12px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
    .rd-kpi-value { font-size: 34px; font-weight: 800; color: var(--ink); line-height: 1.1; letter-spacing: -.02em; }
    .rd-kpi-value small { font-size: 14px; font-weight: 600; color: var(--muted); letter-spacing: 0; }
    .rd-bar { height: 6px; border-radius: 999px; background: #eef2f8; margin-top: 14px; overflow: hidden; }
    .rd-bar > span { display: block; height: 100%; width: 0; border-radius: inherit; background: var(--c); box-shadow: 0 0 12px var(--c); transition: width 1.1s cubic-bezier(.2,.8,.2,1); }

    /* Toggle chart */
    .rd-seg { display: inline-flex; padding: 4px; border-radius: 12px; background: #f1f5fb; border: 1px solid var(--line); }
    .rd-seg button { border: 0; background: transparent; padding: 6px 14px; border-radius: 9px; font-size: 13px; font-weight: 700; color: var(--muted); cursor: pointer; }
    .rd-seg button.active { background: #fff; color: var(--blue); box-shadow: 0 2px 6px rgba(16,41,79,.1); }

    /* Donut legend */
    .rd-legend { list-style: none; margin: 6px 0 0; padding: 0; }
    .rd-legend li { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-top: 1px dashed var(--line); font-size: 14px; }
    .rd-legend li:first-child { border-top: 0; }
    .rd-dot { width: 10px; height: 10px; border-radius: 3px; flex: none; }
    .rd-legend .name { flex: 1; font-weight: 600; color: var(--ink); }
    .rd-legend .val { font-weight: 800; color: var(--ink); }
    .rd-legend .pct { width: 56px; text-align: right; color: var(--muted); font-size: 13px; }

    /* Staff */
    .rd-staff { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; }
    .rd-person {
        display: block; text-align: center; padding: 18px 12px 16px; border-radius: 16px; color: var(--ink);
        border: 1px solid var(--line); background: linear-gradient(180deg, #fbfcff, #fff);
        transition: transform .18s, border-color .18s, box-shadow .18s;
    }
    .rd-person:hover { color: var(--ink); transform: translateY(-3px); border-color: #c9dafb; box-shadow: 0 12px 26px rgba(31,111,235,.12); }
    .rd-avatar { width: 68px; height: 68px; margin: 0 auto 12px; padding: 3px; border-radius: 50%; background: conic-gradient(from 210deg, #1f6feb, #8fc1ff, #14b8a6, #1f6feb); }
    .rd-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid #fff; background: #e9eef6; }
    .rd-person b { display: block; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rd-person small { display: block; color: var(--muted); font-size: 12.5px; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rd-person em { display: inline-block; margin-top: 10px; font-style: normal; font-size: 11.5px; font-weight: 700; color: var(--blue); background: #eef4ff; padding: 3px 9px; border-radius: 999px; }

    /* Timeline transaksi */
    .rd-timeline { list-style: none; margin: 0; padding: 0; position: relative; }
    .rd-timeline::before { content: ""; position: absolute; left: 7px; top: 8px; bottom: 8px; width: 2px; background: linear-gradient(var(--line), transparent); }
    .rd-timeline li { position: relative; padding: 0 0 18px 28px; }
    .rd-timeline li:last-child { padding-bottom: 0; }
    .rd-timeline .pin { position: absolute; left: 0; top: 4px; width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 4px solid var(--c); box-shadow: 0 0 0 4px color-mix(in srgb, var(--c) 15%, transparent); }
    .rd-timeline .title { font-weight: 700; color: var(--ink); font-size: 14px; }
    .rd-timeline .meta { font-size: 12.5px; color: var(--muted); margin-top: 3px; }
    .rd-timeline code { font-size: 11.5px; color: #3557a3; background: #eef4ff; padding: 1px 6px; border-radius: 6px; }
    .rd-badge { display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; color: var(--c); background: color-mix(in srgb, var(--c) 12%, #fff); margin-left: 4px; }

    .rd-empty { text-align: center; color: var(--muted); padding: 28px 10px; }
    .rd-empty i { font-size: 30px; display: block; margin-bottom: 8px; color: #b8c4d6; }

    .rd-reveal { opacity: 0; transform: translateY(12px); animation: rd-in .6s cubic-bezier(.2,.8,.2,1) forwards; }
    @keyframes rd-in { to { opacity: 1; transform: none; } }
    @keyframes rd-pulse { 0% { box-shadow: 0 0 0 0 rgba(52,211,153,.6); } 70% { box-shadow: 0 0 0 10px rgba(52,211,153,0); } 100% { box-shadow: 0 0 0 0 rgba(52,211,153,0); } }
    @media (max-width: 767px) {
        .rd-hero { padding: 24px 20px; }
        .rd-glass, .rd-glass.big { min-width: 0; flex: 1 1 140px; }
    }
    @media (prefers-reduced-motion: reduce) { .rd-reveal { animation: none; opacity: 1; transform: none; } .rd-live { animation: none; } }
</style>
@endpush

@section('content')
@php
    $kpis = [
        ['key' => 'good', 'label' => 'Good', 'icon' => 'bi-heart-pulse', 'color' => '#14b8a6'],
        ['key' => 'repair', 'label' => 'Repair / Service', 'icon' => 'bi-tools', 'color' => '#f59e0b'],
        ['key' => 'decommissioned', 'label' => 'Decommissioned', 'icon' => 'bi-archive', 'color' => '#ef4444'],
        ['key' => 'attention', 'label' => 'Broken & Lost', 'icon' => 'bi-exclamation-octagon', 'color' => '#fb7185'],
    ];
@endphp

<div class="rd">
    {{-- ===== Hero ===== --}}
    <section class="rd-hero rd-reveal">
        <div class="rd-hero-grid">
            <div>
                <span class="rd-eyebrow"><span class="rd-live"></span> Live overview &middot; {{ $locationLabel }}</span>
                <h1>{{ $greeting }}, {{ \Illuminate\Support\Str::of(auth()->user()->full_name)->before(' ') }}</h1>
                <p><i class="bi bi-geo-alt"></i>{{ $locationFull }} &nbsp;&middot;&nbsp; <i class="bi bi-calendar3"></i>{{ $today }}</p>
            </div>
            <div class="rd-hero-stats">
                <div class="rd-glass big">
                    <small>Total units</small>
                    <strong data-count="{{ $totalItems }}">0</strong>
                    <span>tracked in {{ $locationLabel }}</span>
                </div>
                <div class="rd-glass">
                    <small>Sub categories</small>
                    <strong data-count="{{ $categoryCount }}">0</strong>
                    <span>in use</span>
                </div>
                <div class="rd-glass">
                    <small>Active staff</small>
                    <strong data-count="{{ $staffCount }}">0</strong>
                    <span>holding assets</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== KPI kondisi ===== --}}
    <div class="row">
        @foreach ($kpis as $i => $kpi)
            <div class="col-sm-6 col-lg-3 rd-gap">
                <div class="rd-card rd-kpi rd-reveal" style="--c: {{ $kpi['color'] }}; animation-delay: {{ 80 + $i * 70 }}ms">
                    <div class="rd-kpi-top">
                        <span class="rd-kpi-icon"><i class="bi {{ $kpi['icon'] }}"></i></span>
                        <span class="rd-kpi-pct">{{ $percent[$kpi['key']] }}%</span>
                    </div>
                    <div class="rd-kpi-label">{{ $kpi['label'] }}</div>
                    <div class="rd-kpi-value">
                        <span data-count="{{ $summary[$kpi['key']] }}">0</span>
                        <small>/ {{ number_format($summary['total']) }} units</small>
                    </div>
                    <div class="rd-bar"><span data-width="{{ $percent[$kpi['key']] }}"></span></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Chart sub kategori + donut kondisi ===== --}}
    <div class="row">
        <div class="col-lg-8 rd-gap">
            <div class="rd-card rd-reveal" style="animation-delay: 300ms">
                <div class="rd-card-head">
                    <div>
                        <h3>Inventory by sub category</h3>
                        <p>{{ $chartData->count() }} sub categories &middot; {{ number_format($totalItems) }} units</p>
                    </div>
                    <div class="rd-seg" role="group" aria-label="Chart range">
                        <button type="button" class="active" data-range="10">Top 10</button>
                        <button type="button" data-range="all">All</button>
                    </div>
                </div>
                <div class="rd-card-body">
                    @if ($chartData->isEmpty())
                        <div class="rd-empty"><i class="bi bi-bar-chart"></i>No inventory recorded for this location yet.</div>
                    @else
                        <div id="rd-chart" style="height: 340px"></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 rd-gap">
            <div class="rd-card rd-reveal" style="animation-delay: 360ms">
                <div class="rd-card-head">
                    <div>
                        <h3>Condition mix</h3>
                        <p>Based on each asset's latest transaction</p>
                    </div>
                </div>
                <div class="rd-card-body">
                    @if ($donutData->isEmpty())
                        <div class="rd-empty"><i class="bi bi-pie-chart"></i>No condition data yet.</div>
                    @else
                        <div id="rd-donut" style="height: 220px"></div>
                        <ul class="rd-legend">
                            @foreach ($donutData as $d)
                                <li>
                                    <span class="rd-dot" style="background: {{ $d['color'] }}"></span>
                                    <span class="name">{{ $d['name'] }}</span>
                                    <span class="val">{{ number_format($d['y']) }}</span>
                                    <span class="pct">{{ $summary['total'] ? round($d['y'] / $summary['total'] * 100, 1) : 0 }}%</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Aktivitas staff + transaksi terbaru ===== --}}
    <div class="row">
        <div class="col-lg-7 rd-gap">
            <div class="rd-card rd-reveal" style="animation-delay: 420ms">
                <div class="rd-card-head">
                    <div>
                        <h3>Latest activity</h3>
                        <p>Staff with the most recent asset movements</p>
                    </div>
                    <a href="{{ url('staff') }}" class="btn btn-default btn-sm">All staff <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="rd-card-body">
                    @if ($staff->isEmpty())
                        <div class="rd-empty"><i class="bi bi-people"></i>No staff activity in this location yet.</div>
                    @else
                        <div class="rd-staff">
                            @foreach ($staff as $row)
                                <a class="rd-person" href="{{ url('staff/read?id='.$row->id) }}">
                                    <div class="rd-avatar">
                                        <img src="{{ asset('assets/images/'.($row->image ?: 'default.jpg')) }}" alt=""
                                             onerror="this.onerror=null;this.src='{{ asset('images/avatar-default.svg') }}'">
                                    </div>
                                    <b title="{{ $row->name }}">{{ $row->name }}</b>
                                    <small>{{ $row->department_name ?: 'No department' }}</small>
                                    <em>{{ $row->total }} {{ \Illuminate\Support\Str::plural('move', $row->total) }}
                                        @if ($row->last_at) &middot; {{ \Illuminate\Support\Carbon::parse($row->last_at)->locale('en')->diffForHumans() }} @endif
                                    </em>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5 rd-gap">
            <div class="rd-card rd-reveal" style="animation-delay: 480ms">
                <div class="rd-card-head">
                    <div>
                        <h3>Recent transactions</h3>
                        <p>Latest check-outs and condition updates</p>
                    </div>
                    <a href="{{ url('item_master') }}" class="btn btn-default btn-sm">Inventory <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="rd-card-body">
                    @if ($transactions->isEmpty())
                        <div class="rd-empty"><i class="bi bi-arrow-left-right"></i>No transactions yet.</div>
                    @else
                        <ul class="rd-timeline">
                            @foreach ($transactions as $t)
                                <li style="--c: {{ $t->color }}">
                                    <span class="pin"></span>
                                    <div class="title">{{ $t->item_name }}</div>
                                    <div class="meta">
                                        <code>{{ $t->inventory_number }}</code>
                                        &rarr; {{ $t->staff_name ?: '-' }}
                                        <span class="rd-badge">{{ $t->condition_name ?: 'Unknown' }}</span>
                                    </div>
                                    <div class="meta"><i class="bi bi-clock"></i> {{ $t->ago }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- defer: Highcharts tidak menahan tampilan halaman bila CDN lambat --}}
<script defer src="https://code.highcharts.com/highcharts.js"></script>
<script defer src="https://code.highcharts.com/modules/exporting.js"></script>
<script defer src="https://code.highcharts.com/modules/export-data.js"></script>
<script defer src="https://code.highcharts.com/modules/accessibility.js"></script>
<script>
/* ---- Angka berjalan (count-up) + progress bar: tidak bergantung pada library apa pun ---- */
(function () {
    function countUp() {
        var els = document.querySelectorAll('[data-count]');
        Array.prototype.forEach.call(els, function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0, start = null;
            function step(ts) {
                if (!start) start = ts;
                var p = Math.min((ts - start) / 1100, 1), eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(target * eased).toLocaleString('en-US');
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
        setTimeout(function () {
            Array.prototype.forEach.call(document.querySelectorAll('.rd-bar > span'), function (bar) {
                bar.style.width = bar.getAttribute('data-width') + '%';
            });
        }, 250);
    }
    // Script ini ada di akhir <body>, jadi elemen sudah ada: langsung jalankan
    try { countUp(); } catch (e) { console.error('count-up:', e); }
})();

/* ---- Chart: dijalankan setelah Highcharts (defer) selesai dimuat ---- */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Highcharts) {
        console.warn('Highcharts tidak termuat (cek koneksi ke code.highcharts.com).');
        return;
    }
    try { riseCharts(); } catch (e) { console.error('chart:', e); }
});

function riseCharts() {
    var font = getComputedStyle(document.documentElement).getPropertyValue('--rise-font') || 'sans-serif';
    var allData = @json($chartData);
    var donutData = @json($donutData);
    var total = {{ (int) $summary['total'] }};

    Highcharts.setOptions({
        chart: { style: { fontFamily: font }, backgroundColor: 'transparent' },
        credits: { enabled: false },
        title: { text: null },
        lang: { thousandsSep: ',' }
    });

    /* ---- Chart sub kategori ---- */
    if (allData.length && document.getElementById('rd-chart')) {
        var chart = Highcharts.chart('rd-chart', {
            chart: { type: 'column', spacing: [10, 0, 0, 0], animation: { duration: 900 } },
            xAxis: {
                type: 'category', lineColor: '#e6ecf4', tickLength: 0,
                labels: { rotation: -35, style: { color: '#6b7a90', fontSize: '11.5px', fontWeight: '600' } }
            },
            yAxis: {
                title: { text: null }, gridLineColor: '#eef2f8', gridLineDashStyle: 'Dash',
                labels: { style: { color: '#9aa6b8' } }, allowDecimals: false
            },
            legend: { enabled: false },
            tooltip: {
                backgroundColor: '#0b1e3a', borderWidth: 0, borderRadius: 12, shadow: false, padding: 12,
                style: { color: '#fff', fontSize: '13px' },
                formatter: function () {
                    var pct = total ? (this.y / total * 100).toFixed(1) : 0;
                    return '<span style="color:#8fc1ff;font-weight:700">' + this.key + '</span><br>' +
                           '<b style="font-size:16px">' + this.y + '</b> units &middot; ' + pct + '%';
                }
            },
            plotOptions: {
                column: {
                    borderWidth: 0, borderRadius: 8, pointPadding: 0.12, groupPadding: 0.08, maxPointWidth: 44,
                    color: {
                        linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                        stops: [[0, '#4c9bff'], [1, '#1f6feb']]
                    },
                    states: { hover: { color: { linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 }, stops: [[0, '#8fc1ff'], [1, '#14b8a6']] } } },
                    dataLabels: { enabled: true, crop: false, overflow: 'none', style: { color: '#14213d', fontWeight: '800', textOutline: 'none', fontSize: '12px' } }
                }
            },
            exporting: {
                buttons: { contextButton: { symbolStroke: '#6b7a90', theme: { fill: 'transparent' } } }
            },
            series: [{ name: 'Units', data: allData.slice(0, 10) }]
        });

        $('.rd-seg button').on('click', function () {
            $(this).addClass('active').siblings().removeClass('active');
            var range = $(this).data('range');
            chart.series[0].setData((range === 'all' ? allData : allData.slice(0, 10)).map(function (p) { return $.extend({}, p); }), true, { duration: 600 });
        });
    }

    /* ---- Donut kondisi ---- */
    if (donutData.length && document.getElementById('rd-donut')) {
        Highcharts.chart('rd-donut', {
            chart: { type: 'pie', spacing: [0, 0, 0, 0] },
            title: {
                text: '<div style="text-align:center"><div style="font-size:28px;font-weight:800;color:#14213d">' +
                      total.toLocaleString('en-US') + '</div><div style="font-size:12px;color:#6b7a90;font-weight:700;letter-spacing:.08em">UNITS</div></div>',
                useHTML: true, verticalAlign: 'middle', floating: true, y: 6
            },
            tooltip: {
                backgroundColor: '#0b1e3a', borderWidth: 0, borderRadius: 12, shadow: false,
                style: { color: '#fff' }, pointFormat: '<b>{point.y}</b> units ({point.percentage:.1f}%)'
            },
            exporting: { enabled: false },
            plotOptions: {
                pie: {
                    innerSize: '74%', borderWidth: 3, borderColor: '#fff', borderRadius: 6,
                    dataLabels: { enabled: false }, startAngle: -90,
                    states: { hover: { halo: { size: 8 } } }
                }
            },
            series: [{ name: 'Condition', data: donutData }]
        });
    }
}
</script>
@endpush
