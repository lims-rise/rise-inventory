@extends('layouts.app')

@section('title', 'Staff')

@section('content')
@php
    $isEdit = $editing !== null;
    $v = fn (string $key, $default = null) => old($key, $editing->$key ?? $default);
    $photo = old('file')
        ? asset(\App\Http\Controllers\StaffController::IMAGE_DIR.'/'.old('file'))
        : \App\Http\Controllers\StaffController::photoUrl($editing->image ?? null);
    $actions = '<a href="'.e(route('staff.excel')).'" class="btn rp-btn-excel"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>';
    if ($canEdit && $isEdit) {
        $actions = '<a href="'.e(route('staff.index')).'" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Data</a>'.$actions;
    }
@endphp

<div class="rp-head">
    <div class="rp-head-title">
        <span class="rp-head-icon"><i class="bi bi-people"></i></span>
        <div>
            <ol class="rp-crumb">
                <li><a href="{{ url('welcome') }}"><i class="bi bi-house-door"></i></a></li>
                <li>Staff</li>
            </ol>
            <h1>Staff</h1>
            <p><b>{{ number_format($total) }}</b> active staff in {{ $locationLabel ?: 'all locations' }}</p>
        </div>
    </div>
    <div class="rp-head-actions">{!! $actions !!}</div>
</div>

<div class="row">
    {{-- ================= Panel form (kiri), seperti CI3 ================= --}}
    @if ($canEdit)
        <div class="col-lg-4">
            <form method="POST" action="{{ $isEdit ? route('staff.update') : route('staff.store') }}" class="rp-card sf-panel {{ $isEdit ? 'sf-panel-edit' : '' }}" id="staff-form" novalidate>
                @csrf
                <input type="hidden" name="file" id="f-file" value="{{ old('file') }}">
                @if ($isEdit)<input type="hidden" name="id" value="{{ $editing->id }}">@endif

                <div class="sf-panel-tag">{{ $isEdit ? 'Edit staff' : 'New staff' }}</div>

                <button type="button" class="sf-avatar" id="avatar-button" title="Change photo">
                    <img src="{{ $photo }}" alt="" id="avatar-img" onerror="this.onerror=null;this.src='{{ asset('images/avatar-default.svg') }}'">
                    <span class="sf-avatar-cam"><i class="bi bi-camera"></i></span>
                    <span class="sf-avatar-busy"><span class="rp-spinner"></span></span>
                </button>
                <input type="file" id="avatar-input" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
                <h3 class="sf-preview-name" id="preview-name">{{ $v('name') ?: 'Staff Name' }}</h3>
                <p class="sf-preview-dept" id="preview-dept">{{ $editing->department_name ?? 'Department Name' }}</p>
                @error('file')<p class="text-danger text-center">{{ $message }}</p>@enderror

                <div class="rf-section sf-fields">
                    <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                        <label for="f-name">Name <span class="rp-req">*</span></label>
                        <input type="text" id="f-name" name="name" class="form-control" value="{{ $v('name') }}" placeholder="Full name" maxlength="100" autocomplete="off">
                        @error('name')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                    @include('inventory._select', ['name' => 'department_id', 'label' => 'Department', 'options' => $departments,
                        'value' => $v('department_id'), 'fallback' => $editing->department_name ?? null, 'required' => true])
                    @include('inventory._select', ['name' => 'location_id', 'label' => 'Location', 'options' => $locations,
                        'value' => $v('location_id', $defaultLocation), 'fallback' => $editing->location_name ?? null])
                </div>

                <button type="submit" class="btn btn-primary btn-block sf-submit">
                    <i class="bi {{ $isEdit ? 'bi-check2-circle' : 'bi-person-plus' }}"></i> {{ $isEdit ? 'Save changes' : 'Add staff' }}
                </button>
                @if ($isEdit)
                    <a href="{{ route('staff.index') }}" class="btn btn-default btn-block">Cancel</a>
                @endif
            </form>
        </div>
    @endif

    {{-- ================= Tabel staff ================= --}}
    <div class="{{ $canEdit ? 'col-lg-8' : 'col-lg-12' }}">
        <div class="rp-card">
            <table class="table rp-table dt-responsive" id="staff-table" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Location</th>
                        <th class="text-center">Items</th>
                        <th class="text-right" style="width:140px">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@if ($canDelete)
    <div class="modal fade rp-modal" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="delete-title">
        <div class="modal-dialog modal-sm" role="document">
            <form method="POST" class="modal-content" id="delete-form">
                @csrf
                @method('DELETE')
                <div class="modal-body text-center">
                    <span class="rp-modal-icon"><i class="bi bi-person-x"></i></span>
                    <h4 id="delete-title">Delete this staff?</h4>
                    <p><strong data-delete-name></strong> will be removed from the staff list. Their history stays.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash3"></i> Delete</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
$(function () {
    var dash = '<span class="text-muted">—</span>';
    var fallback = @json(asset('images/avatar-default.svg'));

    var table = $('#staff-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        order: [],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        dom: "<'rp-dt-top'lf>t<'rp-dt-bottom'ip>",
        ajax: { url: @json(route('staff.json')), type: 'POST', global: false },
        columns: [
            { data: 'id', orderable: false, className: 'rp-no', render: function () { return ''; } },
            { data: 'name', render: function (d, t, r) {
                return '<a href="' + r.urls.read + '" class="sf-person"><img src="' + r.photo + '" alt="" onerror="this.onerror=null;this.src=\'' + fallback + '\'">' +
                       '<b>' + d + '</b></a>';
            } },
            { data: 'department_name', render: function (d) { return d || dash; } },
            { data: 'location_name', render: function (d) { return d || dash; } },
            { data: 'items', orderable: false, className: 'text-center', render: function (d) {
                return d ? '<span class="sf-count">' + d + '</span>' : '<span class="text-muted">0</span>';
            } },
            { data: 'urls', orderable: false, className: 'text-right', render: function (u, t, r) {
                var html = '<div class="rp-actions"><a href="' + u.read + '" class="rp-act" title="View"><i class="bi bi-eye"></i></a>';
                if (u.edit) html += '<a href="' + u.edit + '" class="rp-act rp-act-edit" title="Edit"><i class="bi bi-pencil-square"></i></a>';
                if (u.delete) html += '<button type="button" class="rp-act rp-act-del" title="Delete" data-url="' + u.delete + '" data-name="' + r.name + '"><i class="bi bi-trash3"></i></button>';
                return html + '</div>';
            } }
        ],
        columnDefs: [{ responsivePriority: 1, targets: 1 }, { responsivePriority: 2, targets: 5 }],
        language: {
            processing: '<span class="rp-spinner"></span> Loading…',
            search: '', searchPlaceholder: 'Search name, department…',
            lengthMenu: 'Show _MENU_', info: 'Showing _START_–_END_ of _TOTAL_', infoEmpty: 'No staff',
            infoFiltered: '(filtered from _MAX_)',
            zeroRecords: '<div class="rp-empty"><i class="bi bi-search"></i>No matching staff</div>',
            emptyTable: '<div class="rp-empty"><i class="bi bi-people"></i>No staff in this location yet.</div>',
            paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
        },
        rowCallback: function (row, data, index) {
            $('td:eq(0)', row).html(this.api().page.info().start + index + 1);
        }
    });

    var timer;
    $('#staff-table_filter input').off('.DT').on('input.DT', function () {
        var value = this.value;
        clearTimeout(timer);
        timer = setTimeout(function () { table.search(value).draw(); }, 350);
    });

    $('#staff-table').on('click', '.rp-act-del', function () {
        $('#delete-form').attr('action', $(this).data('url'));
        $('[data-delete-name]').text($(this).data('name'));
        $('#delete-modal').modal('show');
    });

    // ---------- Pratinjau langsung (seperti CI3) ----------
    $('#f-name').on('input', function () { $('#preview-name').text(this.value || 'Staff Name'); });
    $('#f-department_id').on('change', function () {
        var text = $(this).find('option:selected').text();
        $('#preview-dept').text(this.value ? text : 'Department Name');
    });

    // ---------- Foto: klik avatar untuk memilih ----------
    $('#avatar-button').on('click', function () { $('#avatar-input').trigger('click'); });
    $('#avatar-input').on('change', function () {
        var file = this.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('image', file);
        var $btn = $('#avatar-button').addClass('sf-busy');
        $.ajax({ url: @json(route('staff.upload')), type: 'POST', data: fd, contentType: false, processData: false, dataType: 'json', global: false })
            .done(function (r) { $('#avatar-img').attr('src', r.url); $('#f-file').val(r.data); })
            .fail(function (x) { alert((x.responseJSON && (x.responseJSON.error || x.responseJSON.message)) || 'Upload failed.'); })
            .always(function () { $btn.removeClass('sf-busy'); });
        this.value = '';
    });
});
</script>
@endpush
