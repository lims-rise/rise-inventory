@extends('layouts.app')

@section('title', $item->inventory_number)

@section('content')
@php
    $imageDir = \App\Http\Controllers\ItemMasterController::IMAGE_DIR;
    $receiptDir = \App\Http\Controllers\ItemMasterController::RECEIPT_DIR;
    $price = $item->purchase_price !== null && $item->purchase_price !== ''
        ? trim(($item->currency ?? '').' '.number_format((float) $item->purchase_price, fmod((float) $item->purchase_price, 1) ? 2 : 0))
        : null;
    $field = fn ($value) => filled($value) ? e($value) : '<span class="text-muted">—</span>';
    $multiline = fn ($value) => filled($value) ? nl2br(e($value)) : '<span class="text-muted">—</span>';

    $badge = $item->condition_name
        ? '<span class="rf-badge" style="--c:'.\App\Support\ConditionStyle::color($item->condition_id).'">'.e($item->condition_name).'</span>'
        : '<span class="rf-badge rf-badge-muted">No history</span>';

    $actions = '<a href="'.e($backUrl).'" class="btn btn-default"><i class="bi bi-arrow-left"></i> Back</a>';
    if ($canEdit) {
        $actions .= '<a href="'.e(route('item_master.edit', ['id' => $item->inventory_number])).'" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>';
    }
    if ($canDelete) {
        $actions .= '<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#delete-modal"><i class="bi bi-trash3"></i> Delete</button>';
    }
@endphp

@include('inventory._head', [
    'heading' => $item->name,
    'crumb' => $item->inventory_number,
    'sub' => '<span class="rf-code">'.e($item->inventory_number).'</span> '.$badge
        .($item->staff_name ? ' <span class="rf-holder"><i class="bi bi-person"></i> '.e($item->staff_name).'</span>' : ''),
    'actions' => $actions,
])

<div class="row">
    {{-- ================= Detail ================= --}}
    <div class="col-lg-7">
        <div class="rp-card">
            <h3 class="rf-section-title"><i class="bi bi-card-list"></i> Details</h3>
            <dl class="rf-grid">
                <div><dt>Inventory Number</dt><dd class="rf-mono">{{ $item->inventory_number }}</dd></div>
                <div><dt>Name</dt><dd>{!! $field($item->name) !!}</dd></div>
                <div><dt>Category</dt><dd>{!! $field($item->category_name) !!}</dd></div>
                <div><dt>Sub Category</dt><dd>{!! $field($item->sub_category_name) !!}</dd></div>
                <div><dt>Type</dt><dd>{!! $field($item->type) !!}</dd></div>
                <div><dt>Quantity</dt><dd>{!! $field($item->qty) !!}</dd></div>
                <div class="rf-span"><dt>Serial Number</dt><dd>{!! $multiline($item->serial_number) !!}</dd></div>
                <div class="rf-span"><dt>Description</dt><dd>{!! $multiline($item->description) !!}</dd></div>
            </dl>

            <h3 class="rf-section-title"><i class="bi bi-receipt"></i> Purchase</h3>
            <dl class="rf-grid">
                <div><dt>Purchase Date</dt><dd>{{ $item->purchase_date ? \Illuminate\Support\Carbon::parse($item->purchase_date)->format('d M Y') : '—' }}</dd></div>
                <div><dt>Purchase Price</dt><dd>{!! $field($price) !!}</dd></div>
                <div><dt>Fund</dt><dd>{!! $field($item->fund_name) !!}</dd></div>
                <div><dt>Manufacture</dt><dd>{!! $field($item->manufacture_name) !!}</dd></div>
                <div><dt>Supplier/Agent</dt><dd>{!! $field($item->supplier_name) !!}</dd></div>
            </dl>

            <h3 class="rf-section-title"><i class="bi bi-geo-alt"></i> Location</h3>
            <dl class="rf-grid">
                <div><dt>Location</dt><dd>{!! $field($item->location_name) !!}</dd></div>
                <div><dt>Location Detail</dt><dd>{!! $field($item->location_detail_name) !!}</dd></div>
                <div><dt>Scheduled check</dt>
                    <dd>@if ($item->scheduled === 'YES')<span class="rf-badge" style="--c:#14b8a6"><i class="bi bi-calendar-check"></i> Yes</span>
                        @elseif ($item->scheduled === 'NO')<span class="rf-badge rf-badge-muted">No</span>
                        @else — @endif</dd></div>
                <div><dt>Added</dt><dd>{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d M Y') : '—' }}
                    @if ($createdBy)<small class="text-muted">by {{ $createdBy }}</small>@endif</dd></div>
            </dl>
        </div>
    </div>

    {{-- ================= Foto & struk ================= --}}
    <div class="col-lg-5">
        <div class="rp-card">
            <h3 class="rf-section-title"><i class="bi bi-images"></i> Photos <small>{{ $images->count() }}</small></h3>
            @if ($images->isEmpty())
                <div class="rp-empty"><i class="bi bi-image"></i>No photos yet.</div>
            @else
                <a href="{{ asset($imageDir.'/'.$images->first()) }}" target="_blank" class="rf-stage" id="gallery-stage">
                    <img src="{{ asset($imageDir.'/'.$images->first()) }}" alt="{{ $item->name }}">
                    <span class="rf-stage-zoom"><i class="bi bi-arrows-fullscreen"></i></span>
                </a>
                @if ($images->count() > 1)
                    <div class="rf-strip">
                        @foreach ($images as $i => $file)
                            <button type="button" class="{{ $i === 0 ? 'active' : '' }}" data-src="{{ asset($imageDir.'/'.$file) }}">
                                <img src="{{ asset($imageDir.'/'.$file) }}" alt="">
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        <div class="rp-card">
            <h3 class="rf-section-title"><i class="bi bi-file-earmark-text"></i> Receipts <small>{{ $receipts->count() }}</small></h3>
            @if ($receipts->isEmpty())
                <p class="text-muted" style="margin:0">No receipt attached.</p>
            @else
                <ul class="rf-files">
                    @foreach ($receipts as $file)
                        <li>
                            <i class="bi {{ str_ends_with(strtolower($file), '.pdf') ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' }}"></i>
                            <a href="{{ asset($receiptDir.'/'.$file) }}" target="_blank">{{ $file }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

{{-- ================= Riwayat ================= --}}
<div class="rp-card">
    <div class="rf-card-head">
        <h3 class="rf-section-title"><i class="bi bi-clock-history"></i> History Details <small>{{ $history->count() }}</small></h3>
        <div class="rf-card-actions">
            @if ($canEdit)
                <button type="button" class="btn btn-primary btn-sm" data-open="history"><i class="bi bi-plus-lg"></i> New Data</button>
            @endif
            <a href="{{ route('item_transaction.excel', ['id' => $item->inventory_number]) }}" class="btn rp-btn-excel btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
        </div>
    </div>
    @if ($history->isEmpty())
        <div class="rp-empty"><i class="bi bi-clock-history"></i>No history recorded for this item.</div>
    @else
        <div class="table-responsive">
            <table class="table rp-table">
                <thead>
                    <tr><th>No</th><th>Date</th><th>Responsible Person</th><th>Department</th><th>Location</th><th>Purpose</th><th>Condition</th><th>Remark</th>
                        @if ($canEdit)<th class="text-right">Action</th>@endif</tr>
                </thead>
                <tbody>
                    @foreach ($history as $i => $h)
                        <tr>
                            <td class="rp-no">{{ $i + 1 }}</td>
                            <td class="rf-nowrap">{{ $h->transaction_at ? \Illuminate\Support\Carbon::parse($h->transaction_at)->format('d M Y') : '—' }}
                                @if ($i === 0)<br><small class="rf-latest">Latest</small>@endif</td>
                            <td>@if ($h->staff_id)<a href="{{ url('staff/read?id='.$h->staff_id) }}" class="rp-strong">{{ $h->staff_name }}</a>@else — @endif</td>
                            <td>{{ $h->department_name ?: '—' }}</td>
                            <td>{{ $h->location_name ?: '—' }}</td>
                            <td>{{ $h->purpose ?: '—' }}</td>
                            <td>@if ($h->condition_name)<span class="rf-badge" style="--c:{{ \App\Support\ConditionStyle::color($h->condition_id) }}">{{ $h->condition_name }}</span>@else — @endif</td>
                            <td>{{ $h->remark ?: '—' }}</td>
                            @if ($canEdit)
                                <td class="text-right">
                                    <div class="rp-actions">
                                        <button type="button" class="rp-act rp-act-edit" title="Edit" data-open="history"
                                                data-row="{{ json_encode([
                                                    'id' => $h->id,
                                                    'transaction_at' => $h->transaction_at ? substr($h->transaction_at, 0, 10) : '',
                                                    'staff_id' => $h->staff_id, 'department_id' => $h->department_id,
                                                    'location_id' => $h->location_id, 'condition_id' => $h->condition_id,
                                                    'purpose' => $h->purpose, 'remark' => $h->remark,
                                                ]) }}"><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="rp-act rp-act-del" title="Delete" data-remove="history" data-id="{{ $h->id }}"
                                                data-label="{{ ($h->transaction_at ? substr($h->transaction_at, 0, 10) : '').' · '.($h->staff_name ?? '') }}"><i class="bi bi-trash3"></i></button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ================= Maintenance ================= --}}
<div class="rp-card">
    <div class="rf-card-head">
        <h3 class="rf-section-title"><i class="bi bi-tools"></i> Maintenance References <small>{{ $maintenance->count() }}</small></h3>
        <div class="rf-card-actions">
            @if ($canEdit)
                <button type="button" class="btn btn-primary btn-sm" data-open="maintenance"><i class="bi bi-plus-lg"></i> New Data</button>
            @endif
            @if ($maintenance->isNotEmpty())
                <a href="{{ route('item_transaction.excel_maintenance', ['id' => $item->inventory_number]) }}" class="btn rp-btn-excel btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
            @endif
        </div>
    </div>
    @if ($maintenance->isEmpty())
        <div class="rp-empty"><i class="bi bi-tools"></i>No maintenance recorded for this item.</div>
    @else
        <div class="table-responsive">
            <table class="table rp-table">
                <thead>
                    <tr><th>No</th><th>Latest services date</th><th>Services type</th><th>Services provider contact</th><th>Lab PIC</th><th>Services description</th><th>Frequency</th><th>Services schedule</th>
                        @if ($canEdit)<th class="text-right">Action</th>@endif</tr>
                </thead>
                <tbody>
                    @foreach ($maintenance as $i => $m)
                        <tr>
                            <td class="rp-no">{{ $i + 1 }}</td>
                            <td class="rf-nowrap">{{ $m->last_service_date ? \Illuminate\Support\Carbon::parse($m->last_service_date)->format('d M Y') : '—' }}</td>
                            <td>{{ $m->services_type ?: '—' }}</td>
                            <td>{{ $m->provider_contact ?: '—' }}</td>
                            <td>@if ($m->staff_id)<a href="{{ url('staff/read?id='.$m->staff_id) }}" class="rp-strong">{{ $m->staff_name }}</a>@else — @endif</td>
                            <td>{{ $m->service_desc ?: '—' }}</td>
                            <td>{{ $m->frequency ?: '—' }}</td>
                            <td>{{ $m->service_schedule !== null ? $m->service_schedule.' weeks prior' : '—' }}</td>
                            @if ($canEdit)
                                <td class="text-right">
                                    <div class="rp-actions">
                                        <button type="button" class="rp-act rp-act-edit" title="Edit" data-open="maintenance"
                                                data-row="{{ json_encode([
                                                    'id_maintenance' => $m->id_maintenance,
                                                    'last_service_date' => $m->last_service_date ? substr($m->last_service_date, 0, 10) : '',
                                                    'id_services' => $m->id_services, 'provider_contact' => $m->provider_contact,
                                                    'staff_id' => $m->staff_id, 'service_desc' => $m->service_desc,
                                                    'id_frequency' => $m->id_frequency, 'service_schedule' => $m->service_schedule,
                                                ]) }}"><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="rp-act rp-act-del" title="Delete" data-remove="maintenance" data-id="{{ $m->id_maintenance }}"
                                                data-label="{{ ($m->last_service_date ? substr($m->last_service_date, 0, 10) : '').' · '.($m->services_type ?? 'service') }}"><i class="bi bi-trash3"></i></button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@if ($canEdit)
    @include('inventory._history_modals')
@endif

@if ($canDelete)
    @include('inventory._delete_modal', ['deleteId' => $item->inventory_number, 'deleteName' => $item->inventory_number.' · '.$item->name])
@endif
@endsection

@push('scripts')
<script>
$(function () {
    @if ($canEdit)
    // ---------- Modal History & Maintenance ----------
    var inventoryNumber = @json($item->inventory_number);
    var modals = { history: $('#history-modal'), maintenance: $('#maintenance-modal') };

    // select2 di dalam modal Bootstrap 3 harus memakai dropdownParent supaya kotak pencarian bisa diketik
    $.each(modals, function (key, $modal) {
        $modal.find('select.rf-modal-select').each(function () {
            $(this).select2({ dropdownParent: $modal, width: '100%', placeholder: $(this).data('placeholder'), allowClear: !$(this).prop('required') });
        });
    });

    function clearErrors($form) {
        $form.find('.has-error').removeClass('has-error');
        $form.find('.help-block.rf-js-error').remove();
        $form.find('.rf-modal-alert').hide().text('');
    }

    $(document).on('click', '[data-open]', function () {
        var kind = $(this).data('open');
        var $modal = modals[kind];
        var $form = $modal.find('form');
        var row = $(this).data('row') || null;

        $form[0].reset();
        clearErrors($form);
        $form.find('select').val('').trigger('change.select2');
        $form.find('[name=id], [name=id_maintenance]').val('');

        if (row) {
            $.each(row, function (name, value) {
                var $field = $form.find('[name="' + name + '"]');
                $field.val(value === null ? '' : value);
                if ($field.is('select')) $field.trigger('change.select2');
            });
        } else if (kind === 'history') {
            $form.find('[name=transaction_at]').val(new Date().toISOString().slice(0, 10));
        }
        $modal.find('.rf-modal-mode').text(row ? 'Edit' : 'New');
        $modal.modal('show');
    });

    // Responsible Person dipilih -> Department & Location terisi otomatis (CI3: staff/get_detail)
    $('#h-staff_id').on('select2:select', function () {
        var $opt = $(this).find('option:selected');
        if ($opt.data('department')) $('#h-department_id').val(String($opt.data('department'))).trigger('change.select2');
        if ($opt.data('location')) $('#h-location_id').val(String($opt.data('location'))).trigger('change.select2');
    });

    $('.rf-modal-form').on('submit', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var $form = $(this);
        var $btn = $form.find('[type=submit]').prop('disabled', true);
        clearErrors($form);

        var data = $form.serializeArray();
        data.push({ name: 'inventory_number', value: inventoryNumber });

        $.ajax({ url: $form.attr('action'), type: 'POST', data: $.param(data), dataType: 'json' })
            .done(function () { window.location.reload(); })
            .fail(function (x) {
                $btn.prop('disabled', false);
                var errors = x.responseJSON && x.responseJSON.errors;
                if (errors) {
                    $.each(errors, function (field, messages) {
                        var $group = $form.find('[name="' + field + '"]').closest('.form-group').addClass('has-error');
                        $group.append($('<span class="help-block rf-js-error">').text(messages[0]));
                    });
                } else {
                    $form.find('.rf-modal-alert').text((x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.').show();
                }
            });
    });

    // Hapus (CI3 langsung menghapus tanpa konfirmasi)
    var removeUrls = { history: @json(route('item_transaction.delete')), maintenance: @json(route('item_transaction.delete_maintenance')) };
    $(document).on('click', '[data-remove]', function () {
        $('#remove-modal').data({ url: removeUrls[$(this).data('remove')], id: $(this).data('id') })
            .find('[data-remove-label]').text($(this).data('label')).end()
            .modal('show');
    });
    $('#remove-confirm').on('click', function () {
        var $modal = $('#remove-modal');
        $(this).prop('disabled', true);
        $.ajax({ url: $modal.data('url'), type: 'POST', data: { id: $modal.data('id') }, dataType: 'json' })
            .always(function () { window.location.reload(); });
    });
    @endif

    $('.rf-strip').on('click', 'button', function () {
        var src = $(this).data('src');
        $('#gallery-stage').attr('href', src).find('img').attr('src', src);
        $(this).addClass('active').siblings().removeClass('active');
    });
});
</script>
@endpush
