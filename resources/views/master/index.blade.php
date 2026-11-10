@extends('layouts.app')

@section('title', $resource->title)

@section('content')
@php
    $listFields = $resource->listFields();
    $actions = '<a href="'.e($resource->route('create')).'" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Data</a>'
             .'<a href="'.e($resource->route('excel')).'" class="btn rp-btn-excel"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>';
@endphp

@include('master._header', [
    'heading' => $resource->title,
    'sub' => number_format($total).' active '.\Illuminate\Support\Str::plural('record', $total),
    'actions' => $actions,
])

<div class="rp-card">
    <table class="table rp-table" id="master-table" style="width:100%">
        <thead>
            <tr>
                <th style="width:70px">No</th>
                @foreach ($listFields as $field)
                    <th>{{ $field['label'] }}</th>
                @endforeach
                <th class="text-right" style="width:170px">Action</th>
            </tr>
        </thead>
    </table>
</div>

{{-- Form hapus bersama (DELETE + CSRF), dipakai oleh dialog konfirmasi --}}
<div class="modal fade rp-modal" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="delete-title">
    <div class="modal-dialog modal-sm" role="document">
        <form method="POST" class="modal-content" id="delete-form">
            @csrf
            @method('DELETE')
            <div class="modal-body text-center">
                <span class="rp-modal-icon"><i class="bi bi-trash3"></i></span>
                <h4 id="delete-title">Delete this {{ strtolower($resource->title) }}?</h4>
                <p><strong data-delete-name></strong> will be removed from the list. Existing assets keep their data.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash3"></i> Delete</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var columns = [{ data: 'id', orderable: false, className: 'rp-no' }];
    @foreach (array_keys($listFields) as $key)
        columns.push({ data: @json($key), className: 'rp-strong' });
    @endforeach
    columns.push({
        data: 'urls', orderable: false, className: 'text-right',
        render: function (urls, type, row) {
            var label = $('<div>').html(row[@json(array_key_first($listFields))] || '').text();
            return '<div class="rp-actions">' +
                '<a href="' + urls.read + '" class="rp-act" title="View"><i class="bi bi-eye"></i></a>' +
                '<a href="' + urls.edit + '" class="rp-act rp-act-edit" title="Edit"><i class="bi bi-pencil-square"></i></a>' +
                '<button type="button" class="rp-act rp-act-del" title="Delete" data-url="' + urls.delete + '" data-name="' +
                    $('<div>').text(label).html().replace(/"/g, '&quot;') + '"><i class="bi bi-trash3"></i></button>' +
            '</div>';
        }
    });

    var table = $('#master-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: @json($resource->route('json')), type: 'POST', global: false }, // global:false = tanpa loading screen penuh
        columns: columns,
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        dom: "<'rp-dt-top'lf>t<'rp-dt-bottom'ip>",
        language: {
            processing: '<span class="rp-spinner"></span> Loading…',
            search: '', searchPlaceholder: 'Search {{ strtolower($resource->title) }}…',
            lengthMenu: 'Show _MENU_',
            info: 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty: 'No records',
            infoFiltered: '(filtered from _MAX_)',
            zeroRecords: '<div class="rp-empty"><i class="bi bi-search"></i>No matching records</div>',
            emptyTable: '<div class="rp-empty"><i class="bi bi-inbox"></i>No data yet. Click <b>New Data</b> to add one.</div>',
            paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
        },
        rowCallback: function (row, data, index) {
            var info = this.api().page.info();
            $('td:eq(0)', row).html(info.start + index + 1);
        }
    });

    // Cari otomatis saat berhenti mengetik (CI3: harus tekan Enter)
    var timer;
    $('#master-table_filter input').off('.DT').on('input.DT', function () {
        var value = this.value;
        clearTimeout(timer);
        timer = setTimeout(function () { table.search(value).draw(); }, 350);
    });

    // Dialog konfirmasi hapus
    $('#master-table').on('click', '.rp-act-del', function () {
        $('#delete-form').attr('action', $(this).data('url'));
        $('[data-delete-name]').text($(this).data('name'));
        $('#delete-modal').modal('show');
    });
});
</script>
@endpush
