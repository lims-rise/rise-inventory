@extends('layouts.app')

@section('title', 'Users Management')

@section('content')
@php
    $photo = fn ($file) => filled($file) && is_file(public_path(\App\Http\Controllers\Config\UserController::PHOTO_DIR.'/'.$file))
        ? asset(\App\Http\Controllers\Config\UserController::PHOTO_DIR.'/'.$file)
        : asset('images/avatar-default.svg');
    $active = $users->where('is_aktif', 'y')->count();
@endphp

@include('config._head', [
    'icon' => 'bi-person-gear', 'heading' => 'Users Management',
    'sub' => '<b>'.$active.'</b> active · '.($users->count() - $active).' inactive accounts',
    'crumb' => '<li>Users Management</li>',
    'actions' => '<a href="'.e(route('user.create')).'" class="btn btn-primary"><i class="bi bi-person-plus"></i> New User</a>'
        .'<a href="'.e(route('user.excel')).'" class="btn rp-btn-excel"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>',
])

<div class="rp-card">
    <table class="table rp-table dt-responsive" id="users-table" style="width:100%">
        <thead>
            <tr>
                <th>Full Name</th><th>Email</th><th>Level</th><th>Linked Staff</th><th>Default Location</th><th>Status</th>
                <th class="text-right" style="width:110px">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $u)
                <tr class="{{ $u->is_aktif !== 'y' ? 'cf-row-inactive' : '' }}">
                    <td>
                        <span class="sf-person"><img src="{{ $photo($u->images) }}" alt=""><b>{{ $u->full_name }}</b>
                            @if ((int) $u->id_users === (int) $me->id_users)<span class="rf-badge" style="--c:#1f6feb">You</span>@endif</span>
                    </td>
                    <td>{{ $u->email }}</td>
                    <td><span class="cf-level">{{ $u->nama_level ?: '—' }}</span></td>
                    <td>@if ($u->staff_id)<a href="{{ url('staff/read?id='.$u->staff_id) }}">{{ $u->staff_name }}</a>@else<span class="text-muted">—</span>@endif</td>
                    <td>{{ $u->location_name ?: '—' }}</td>
                    <td data-order="{{ $u->is_aktif }}">
                        @if ($u->is_aktif === 'y')<span class="rf-badge" style="--c:#14b8a6">Active</span>
                        @else<span class="rf-badge rf-badge-muted">Inactive</span>@endif
                    </td>
                    <td class="text-right">
                        <div class="rp-actions">
                            @if (! ((int) $u->id_user_level === 1 && ! $me->isSuperAdmin()))
                                <a href="{{ route('user.edit', $u->id_users) }}" class="rp-act rp-act-edit" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                @if ((int) $u->id_users !== (int) $me->id_users)
                                    <button type="button" class="rp-act rp-act-del" title="Delete" data-delete="{{ route('user.destroy', $u->id_users) }}" data-name="{{ $u->full_name }}"><i class="bi bi-trash3"></i></button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('config._delete_modal', ['what' => 'user', 'note' => 'Tip: set the account to Inactive instead to keep their name on history records.'])
@endsection

@push('scripts')
<script>
$(function () {
    $('#users-table').DataTable({
        responsive: true,
        order: [[5, 'desc'], [0, 'asc']],
        pageLength: 25,
        columnDefs: [{ orderable: false, targets: 6 }, { responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: 6 }],
        dom: "<'rp-dt-top'lf>t<'rp-dt-bottom'ip>",
        language: {
            search: '', searchPlaceholder: 'Search name, email, level…',
            lengthMenu: 'Show _MENU_', info: 'Showing _START_–_END_ of _TOTAL_', infoEmpty: 'No users', infoFiltered: '(filtered from _MAX_)',
            zeroRecords: '<div class="rp-empty"><i class="bi bi-search"></i>No matching users</div>',
            paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
        }
    });
});
</script>
@endpush
