@extends('layouts.app')

@section('title', 'Inventory List')

@section('content')
@php
    $actions = ($canEdit ? '<a href="'.e(route('item_master.create')).'" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Data</a>' : '')
             .'<a href="'.e(route('item_master.excel')).'" class="btn rp-btn-excel"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>';
@endphp

@include('inventory._head', [
    'heading' => 'Inventory List',
    'sub' => '<b>'.number_format($total).'</b> units in '.e($locationLabel ?: 'all locations'),
    'actions' => $actions,
])

<div class="rp-card">
    <div class="rf-filters">
        <div class="rf-filter">
            <label for="f-category"><i class="bi bi-grid-3x3-gap"></i> Category</label>
            <select id="f-category" class="form-control">
                <option value="">All categories</option>
                @foreach ($categories as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="rf-filter">
            <label for="f-condition"><i class="bi bi-clipboard2-pulse"></i> Condition</label>
            <select id="f-condition" class="form-control">
                <option value="">All conditions</option>
                @foreach ($conditions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
                <option value="none">No history yet</option>
            </select>
        </div>
        <button type="button" class="btn btn-default rf-filter-reset" id="f-reset" style="display:none">
            <i class="bi bi-x-lg"></i> Clear filters
        </button>
    </div>

    <table class="table rp-table dt-responsive" id="inventory-table" style="width:100%">
        <thead>
            <tr>
                <th style="width:50px">No</th>
                <th>Inventory Number</th>
                <th>Name</th>
                <th>Description</th>
                <th>Type/Model</th>
                <th>Fund</th>
                <th>Category</th>
                <th>Sub Category</th>
                <th>Location</th>
                <th>Department</th>
                <th>Person</th>
                <th>Purpose</th>
                <th>Condition</th>
                <th>Remark</th>
                <th class="text-right" style="width:140px">Action</th>
            </tr>
        </thead>
    </table>
</div>

@if ($canDelete)
    @include('inventory._delete_modal')
@endif
@endsection

@push('scripts')
<script>
$(function () {
    var dash = '<span class="text-muted">—</span>';
    var text = function (d) { return d ? d : dash; };

    var table = $('#inventory-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 100, // sama dengan CI3
        lengthMenu: [[25, 50, 100, 250, -1], [25, 50, 100, 250, 'All']],
        order: [[1, 'desc']],
        dom: "<'rp-dt-top'lf>t<'rp-dt-bottom'ip>",
        ajax: {
            url: @json(route('item_master.json')),
            type: 'POST',
            global: false,
            data: function (d) {
                d.category_id = $('#f-category').val();
                d.condition_id = $('#f-condition').val();
            }
        },
        columns: [
            { data: 'inventory_number', orderable: false, className: 'rp-no', render: function () { return ''; } },
            { data: 'inventory_number', className: 'rf-nowrap',
              render: function (d, t, r) { return '<a href="' + r.urls.read + '" class="rf-code">' + d + '</a>'; } },
            { data: 'name', className: 'rp-strong' },
            { data: 'description',
              render: function (d, t, r) { return d ? '<span title="' + d + '">' + r.description_short + '</span>' : dash; } },
            { data: 'type', render: text },
            { data: 'fund_name', render: text },
            { data: 'category_name', render: text },
            { data: 'sub_category_name', render: text },
            { data: 'location_name', render: text },
            { data: 'department_name', render: text },
            { data: 'staff_name',
              render: function (d, t, r) { return d ? '<a href="' + r.urls.staff + '" class="rp-strong">' + d + '</a>' : dash; } },
            { data: 'purpose', render: text },
            { data: 'condition_name',
              render: function (d, t, r) {
                  return d ? '<span class="rf-badge" style="--c:' + r.condition_color + '">' + d + '</span>'
                           : '<span class="rf-badge rf-badge-muted">No history</span>';
              } },
            { data: 'remark', render: text },
            { data: 'urls', orderable: false, className: 'text-right',
              render: function (urls, t, r) {
                  var html = '<div class="rp-actions">' +
                      '<a href="' + urls.read + '" class="rp-act" title="View"><i class="bi bi-eye"></i></a>';
                  if (urls.edit) {
                      html += '<a href="' + urls.edit + '" class="rp-act rp-act-edit" title="Edit"><i class="bi bi-pencil-square"></i></a>';
                  }
                  if (r.can_delete) {
                      html += '<button type="button" class="rp-act rp-act-del" title="Delete" data-id="' + r.inventory_number +
                          '" data-name="' + r.name + '"><i class="bi bi-trash3"></i></button>';
                  }
                  return html + '</div>';
              } }
        ],
        columnDefs: [
            { responsivePriority: 1, targets: 1 },
            { responsivePriority: 2, targets: 2 },
            { responsivePriority: 3, targets: 14 },
            { responsivePriority: 4, targets: 12 },
            { responsivePriority: 5, targets: 10 }
        ],
        language: {
            processing: '<span class="rp-spinner"></span> Loading…',
            search: '', searchPlaceholder: 'Search number, name, serial, person…',
            lengthMenu: 'Show _MENU_',
            info: 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty: 'No records',
            infoFiltered: '(filtered from _MAX_)',
            zeroRecords: '<div class="rp-empty"><i class="bi bi-search"></i>No matching items</div>',
            emptyTable: '<div class="rp-empty"><i class="bi bi-inbox"></i>No items in this location yet.</div>',
            paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
        },
        rowCallback: function (row, data, index) {
            var info = this.api().page.info();
            $('td:eq(0)', row).html(info.start + index + 1);
        }
    });

    // Cari otomatis saat berhenti mengetik (CI3: harus tekan Enter)
    var timer;
    $('#inventory-table_filter input').off('.DT').on('input.DT', function () {
        var value = this.value;
        clearTimeout(timer);
        timer = setTimeout(function () { table.search(value).draw(); }, 400);
    });

    // Filter
    $('#f-category, #f-condition').on('change', function () {
        $('#f-reset').toggle(!!($('#f-category').val() || $('#f-condition').val()));
        table.ajax.reload();
    });
    $('#f-reset').on('click', function () {
        $('#f-category, #f-condition').val('');
        $(this).hide();
        table.ajax.reload();
    });

    // Tandai baris yang diklik (seperti CI3)
    $('#inventory-table tbody').on('click', 'tr', function () {
        $(this).toggleClass('rf-selected').siblings().removeClass('rf-selected');
    });

    // Hapus
    $('#inventory-table').on('click', '.rp-act-del', function (e) {
        e.stopPropagation();
        $('#delete-id').val($(this).data('id'));
        $('[data-delete-name]').text($(this).data('id') + ' · ' + $(this).data('name'));
        $('#delete-modal').modal('show');
    });
});
</script>
@endpush
