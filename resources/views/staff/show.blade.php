@extends('layouts.app')

@section('title', $staff->name)

@section('content')
<div class="row">
    {{-- ================= Profil ================= --}}
    <div class="col-lg-4">
        <div class="rp-card sf-profile">
            <div class="sf-profile-cover"></div>
            <img class="sf-profile-photo" src="{{ $photo }}" alt="" onerror="this.onerror=null;this.src='{{ asset('images/avatar-default.svg') }}'">
            <h1 class="sf-profile-name">{{ $staff->name }}</h1>
            <p class="sf-profile-meta">
                <span><i class="bi bi-diagram-3"></i> {{ $staff->department_name ?: 'No department' }}</span>
                <span><i class="bi bi-geo-alt"></i> {{ $staff->location_name ?: 'No location' }}</span>
            </p>

            <div class="sf-filters" role="group" aria-label="Filter items by condition">
                <button type="button" class="sf-filter active" data-condition="">
                    <span>Total items</span><b>{{ $total }}</b>
                </button>
                @foreach ($conditions as $c)
                    <button type="button" class="sf-filter" data-condition="{{ $c->id }}" style="--c: {{ $c->color }}">
                        <span><i class="sf-dot"></i>{{ $c->name }}</span><b>{{ $c->val }}</b>
                    </button>
                @endforeach
            </div>

            <div class="sf-profile-actions">
                <a href="{{ $backUrl }}" class="btn btn-default"><i class="bi bi-arrow-left"></i> Back</a>
                @if ($canEdit)
                    <a href="{{ route('staff.index', ['id' => $staff->id]) }}" class="btn btn-default"><i class="bi bi-pencil-square"></i> Edit</a>
                @endif
            </div>
        </div>
    </div>

    {{-- ================= Chart sub kategori ================= --}}
    <div class="col-lg-8">
        <div class="rp-card sf-chart-card">
            <div class="rf-card-head">
                <h3 class="rf-section-title"><i class="bi bi-pie-chart"></i> Items by sub category</h3>
            </div>
            @if ($chartData->isEmpty())
                <div class="rp-empty"><i class="bi bi-pie-chart"></i>{{ $staff->name }} is not holding any items.</div>
            @else
                <div id="staff-chart" style="height: 300px"></div>
            @endif
        </div>
    </div>
</div>

{{-- ================= Aset yang dipegang ================= --}}
<div class="rp-card">
    <div class="rf-card-head">
        <h3 class="rf-section-title"><i class="bi bi-box-seam"></i> Inventory List <small id="filter-label" style="display:none"></small></h3>
        @if ($canEdit && $total > 0)
            <div class="rf-card-actions">
                <button type="button" class="btn btn-sm sf-btn-transfer" data-transfer-all><i class="bi bi-arrow-left-right"></i> Transfer All</button>
            </div>
        @endif
    </div>
    <table class="table rp-table dt-responsive" id="items-table" style="width:100%">
        <thead>
            <tr>
                <th style="width:50px">No</th>
                <th>Inventory Number</th>
                <th>Date</th>
                <th>Description</th>
                <th>Serial</th>
                <th>Type</th>
                <th>Category</th>
                <th>Sub Category</th>
                <th>Remark</th>
                <th>Condition</th>
                @if ($canEdit)<th class="text-right">Action</th>@endif
            </tr>
        </thead>
    </table>
</div>

@if ($canEdit)
    @include('staff._transfer_modals')
@endif
@endsection

@push('scripts')
<script defer src="https://code.highcharts.com/highcharts.js"></script>
<script>
// Chart sub kategori (Highcharts dimuat dengan defer, siap saat DOMContentLoaded)
document.addEventListener('DOMContentLoaded', function () {
    var data = @json($chartData);
    if (!window.Highcharts || !data.length || !document.getElementById('staff-chart')) return;
    var palette = ['#1f6feb', '#14b8a6', '#f59e0b', '#8b5cf6', '#ef4444', '#4c9bff', '#22c55e', '#ec4899', '#0ea5e9', '#a3a3a3'];
    Highcharts.chart('staff-chart', {
        chart: { type: 'pie', backgroundColor: 'transparent', style: { fontFamily: getComputedStyle(document.documentElement).getPropertyValue('--rise-font') } },
        title: { text: null }, credits: { enabled: false }, colors: palette,
        tooltip: { backgroundColor: '#0b1e3a', borderWidth: 0, borderRadius: 12, shadow: false, style: { color: '#fff' },
                   pointFormat: '<b>{point.y}</b> items ({point.percentage:.1f}%)' },
        legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle', itemMarginBottom: 6,
                  itemStyle: { fontWeight: '600', color: '#14213d' }, labelFormat: '{name} <span style="color:#6b7a90">({y})</span>', useHTML: true },
        plotOptions: { pie: { innerSize: '62%', borderWidth: 3, borderColor: '#fff', borderRadius: 6, showInLegend: true,
                              dataLabels: { enabled: false } } },
        series: [{ name: 'Items', data: data }]
    });
});

$(function () {
    var canEdit = @json($canEdit);
    var staffId = @json($staff->id);
    var conditionFilter = '';
    var dash = '<span class="text-muted">—</span>';
    var text = function (d) { return d ? d : dash; };

    var columns = [
        { data: 'inventory_number', orderable: false, className: 'rp-no', render: function () { return ''; } },
        { data: 'inventory_number', className: 'rf-nowrap', render: function (d, t, r) {
            return '<a href="' + r.url + '" class="rf-code">' + d + '</a><div class="sf-item-name">' + r.name + '</div>';
        } },
        { data: 'transaction_at', className: 'rf-nowrap', render: text },
        { data: 'description', render: function (d, t, r) { return d ? '<span title="' + d + '">' + r.description_short + '</span>' : dash; } },
        { data: 'serial_number', render: text },
        { data: 'type', render: text },
        { data: 'category_name', render: text },
        { data: 'sub_category_name', render: text },
        { data: 'remark', render: text },
        { data: 'condition_name', render: function (d, t, r) {
            return d ? '<span class="rf-badge" style="--c:' + r.condition_color + '">' + d + '</span>' : dash;
        } }
    ];
    if (canEdit) {
        columns.push({ data: 'transfer', orderable: false, className: 'text-right', render: function () {
            return '<button type="button" class="rp-act sf-act-transfer" title="Transfer this item"><i class="bi bi-arrow-left-right"></i></button>';
        } });
    }

    var table = $('#items-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        order: [[2, 'desc']],
        pageLength: 25,
        dom: "<'rp-dt-top'lf>t<'rp-dt-bottom'ip>",
        ajax: {
            url: @json(route('staff.data')), type: 'POST', global: false,
            data: function (d) { d.staff_id = staffId; d.condition_id = conditionFilter; }
        },
        columns: columns,
        columnDefs: [{ responsivePriority: 1, targets: 1 }, { responsivePriority: 2, targets: -1 }, { responsivePriority: 3, targets: 9 }],
        language: {
            processing: '<span class="rp-spinner"></span> Loading…',
            search: '', searchPlaceholder: 'Search items…',
            lengthMenu: 'Show _MENU_', info: 'Showing _START_–_END_ of _TOTAL_', infoEmpty: 'No items',
            infoFiltered: '(filtered from _MAX_)',
            zeroRecords: '<div class="rp-empty"><i class="bi bi-search"></i>No matching items</div>',
            emptyTable: '<div class="rp-empty"><i class="bi bi-inbox"></i>No items held by this staff.</div>',
            paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
        },
        rowCallback: function (row, data, index) {
            $('td:eq(0)', row).html(this.api().page.info().start + index + 1);
        }
    });

    var timer;
    $('#items-table_filter input').off('.DT').on('input.DT', function () {
        var value = this.value;
        clearTimeout(timer);
        timer = setTimeout(function () { table.search(value).draw(); }, 350);
    });

    // Filter kondisi (CI3: klik daftar kondisi di kartu profil)
    $('.sf-filter').on('click', function () {
        $(this).addClass('active').siblings().removeClass('active');
        conditionFilter = String($(this).data('condition'));
        var label = conditionFilter ? $(this).find('span').text() : '';
        $('#filter-label').text(label).toggle(!!label);
        table.ajax.reload();
    });

    if (!canEdit) return;

    // ---------- Modal Transfer & Transfer All ----------
    var $one = $('#transfer-modal'), $all = $('#transfer-all-modal');
    $.each([$one, $all], function (i, $modal) {
        $modal.find('select.rf-modal-select').each(function () {
            $(this).select2({ dropdownParent: $modal, width: '100%', placeholder: $(this).data('placeholder') });
        });
    });

    function resetForm($modal) {
        var $form = $modal.find('form');
        $form[0].reset();
        $form.find('select').val('').trigger('change.select2');
        $form.find('.has-error').removeClass('has-error');
        $form.find('.rf-js-error').remove();
        $form.find('.rf-modal-alert').hide();
        return $form;
    }

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function nowLocal() {
        var d = new Date();
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    $('#items-table').on('click', '.sf-act-transfer', function () {
        var $tr = $(this).closest('tr');
        if ($tr.hasClass('child')) $tr = $tr.prev();
        var item = table.row($tr).data().transfer;
        var $form = resetForm($one);
        $form.find('[name=inventory_number]').val(item.inventory_number);
        $one.find('[data-item-number]').text(item.inventory_number);
        $one.find('[data-item-name]').text(item.name);
        $one.find('[data-item-desc]').text(item.description || '—');
        $form.find('[name=transaction_at]').val(nowLocal());
        $form.find('[name=condition_id]').val(item.condition_id ? String(item.condition_id) : '').trigger('change.select2');
        $form.find('[name=purpose]').val(item.purpose);
        $form.find('[name=remark]').val(item.remark);
        $one.modal('show');
    });

    $('[data-transfer-all]').on('click', function () { resetForm($all); $all.modal('show'); });

    // Responsible Person -> Department & Location otomatis (CI3: staff/get_detail)
    $('select[data-autofill]').on('select2:select', function () {
        var $opt = $(this).find('option:selected');
        var $form = $(this).closest('form');
        var prefix = $(this).data('autofill');
        if ($opt.data('department')) $form.find('[name=department_id' + prefix + ']').val(String($opt.data('department'))).trigger('change.select2');
        if ($opt.data('location')) $form.find('[name=location_id' + prefix + ']').val(String($opt.data('location'))).trigger('change.select2');
    });

    $('.rf-modal-form').on('submit', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var $form = $(this);
        var $btn = $form.find('[type=submit]').prop('disabled', true);
        $form.find('.has-error').removeClass('has-error');
        $form.find('.rf-js-error').remove();
        $form.find('.rf-modal-alert').hide();

        $.ajax({ url: $form.attr('action'), type: 'POST', data: $form.serialize(), dataType: 'json' })
            .done(function () { window.location.reload(); })
            .fail(function (x) {
                $btn.prop('disabled', false);
                var errors = x.responseJSON && x.responseJSON.errors;
                if (errors) {
                    $.each(errors, function (field, messages) {
                        $form.find('[name="' + field + '"]').closest('.form-group').addClass('has-error')
                             .append($('<span class="help-block rf-js-error">').text(messages[0]));
                    });
                } else {
                    $form.find('.rf-modal-alert').text((x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.').show();
                }
            });
    });
});
</script>
@endpush
