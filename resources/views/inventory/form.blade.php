@extends('layouts.app')

@section('title', $button.' Item')

@section('content')
@php
    $isCreate = $item === null;
    $v = fn (string $key, $default = null) => old($key, $item->$key ?? $default);
    $imageDir = \App\Http\Controllers\ItemMasterController::IMAGE_DIR;
    $receiptDir = \App\Http\Controllers\ItemMasterController::RECEIPT_DIR;

    // Foto & struk: yang sudah tersimpan + yang baru diunggah (dipertahankan bila validasi gagal)
    $removedImages = (array) old('remove_images', []);
    $removedReceipts = (array) old('remove_receipts', []);
    $savedImages = $images->reject(fn ($f) => in_array($f, $removedImages, true));
    $savedReceipts = $receipts->reject(fn ($f) => in_array($f, $removedReceipts, true));
    $newImages = (array) old('image_upload', []);
    $newReceipts = (array) old('receipt_upload', []);
    $scheduled = $v('scheduled', 'YES');
@endphp

@include('inventory._head', [
    'heading' => $button.' Item',
    'crumb' => $button,
    'sub' => $isCreate ? 'Add a new asset to the inventory' : 'Editing <b>'.e($item->inventory_number).'</b> · '.e($item->name),
])

<form method="POST" action="{{ $action }}" id="item-form" novalidate>
    @csrf
    @foreach ($removedImages as $f)<input type="hidden" name="remove_images[]" value="{{ $f }}">@endforeach
    @foreach ($removedReceipts as $f)<input type="hidden" name="remove_receipts[]" value="{{ $f }}">@endforeach

    @if ($errors->any())
        <div class="rf-error-summary">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><b>Please check the form.</b> {{ $errors->count() }} {{ \Illuminate\Support\Str::plural('field', $errors->count()) }} need attention.</div>
        </div>
    @endif

    <div class="row">
        {{-- ================= Kolom kiri ================= --}}
        <div class="col-lg-8">
            <section class="rp-card rf-section">
                <h3 class="rf-section-title"><i class="bi bi-tag"></i> Item</h3>
                <div class="row">
                    @include('inventory._select', ['name' => 'category_id', 'label' => 'Category', 'options' => $categories,
                        'value' => $selectedCategory, 'fallback' => $item->category_name ?? null, 'required' => true, 'col' => 'col-sm-6'])
                    @include('inventory._select', ['name' => 'sub_category_id', 'label' => 'Sub Category', 'options' => $subCategories,
                        'value' => $v('sub_category_id'), 'fallback' => $item->sub_category_name ?? null, 'col' => 'col-sm-6'])
                </div>

                <div class="form-group {{ $errors->has('inventory_number') ? 'has-error' : '' }}">
                    <label for="f-inventory_number">Inventory Number <span class="rp-req">*</span></label>
                    <div class="rf-number">
                        <input type="text" id="f-inventory_number" name="inventory_number" class="form-control input-lg rf-mono"
                               value="{{ $v('inventory_number') }}" placeholder="Choose a category to generate"
                               maxlength="20" autocomplete="off" @readonly(! $isCreate)>
                        @if ($isCreate)
                            <button type="button" class="btn btn-default" id="btn-regenerate" title="Generate the next number">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        @endif
                    </div>
                    @if ($isCreate)
                        <p class="rp-help" id="number-help"><i class="bi bi-magic"></i> Generated from category and location. You can still edit it.</p>
                    @else
                        <p class="rp-help"><i class="bi bi-lock"></i> The inventory number cannot be changed.</p>
                    @endif
                    @error('inventory_number')<span class="help-block"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                </div>

                <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                    <label for="f-name">Name <span class="rp-req">*</span></label>
                    <input type="text" id="f-name" name="name" class="form-control input-lg" value="{{ $v('name') }}"
                           placeholder="e.g. Epson Printer L1455" maxlength="100">
                    @error('name')<span class="help-block"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                </div>

                <div class="row">
                    <div class="form-group col-sm-6 {{ $errors->has('type') ? 'has-error' : '' }}">
                        <label for="f-type">Type/Model</label>
                        <input type="text" id="f-type" name="type" class="form-control" value="{{ $v('type') }}" placeholder="Type or model" maxlength="200">
                        @error('type')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-sm-6 {{ $errors->has('serial_number') ? 'has-error' : '' }}">
                        <label for="f-serial_number">Serial Number</label>
                        <textarea id="f-serial_number" name="serial_number" class="form-control" rows="1" placeholder="One or more serial numbers">{{ $v('serial_number') }}</textarea>
                        @error('serial_number')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
                    <label for="f-description">Description</label>
                    <textarea id="f-description" name="description" class="form-control" rows="3" placeholder="Description">{{ $v('description') }}</textarea>
                    @error('description')<span class="help-block">{{ $message }}</span>@enderror
                </div>
            </section>

            <section class="rp-card rf-section">
                <h3 class="rf-section-title"><i class="bi bi-receipt"></i> Purchase</h3>
                <div class="row">
                    @include('inventory._select', ['name' => 'fund_id', 'label' => 'Fund', 'options' => $funds,
                        'value' => $v('fund_id'), 'fallback' => $item->fund_name ?? null, 'col' => 'col-sm-6'])
                    <div class="form-group col-sm-6 {{ $errors->has('purchase_date') ? 'has-error' : '' }}">
                        <label for="f-purchase_date">Purchase Date</label>
                        <input type="date" id="f-purchase_date" name="purchase_date" class="form-control"
                               value="{{ $v('purchase_date') }}" max="{{ now()->format('Y-m-d') }}">
                        @error('purchase_date')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-sm-6 {{ $errors->has('purchase_price') || $errors->has('currency') ? 'has-error' : '' }}">
                        <label for="f-purchase_price">Price</label>
                        <div class="rf-price">
                            <select name="currency" id="f-currency" class="form-control" aria-label="Currency">
                                @foreach (\App\Models\ItemMaster::CURRENCIES as $cur)
                                    <option value="{{ $cur }}" @selected($v('currency', $defaultCurrency) === $cur)>{{ $cur }}</option>
                                @endforeach
                            </select>
                            <input type="number" id="f-purchase_price" name="purchase_price" class="form-control"
                                   value="{{ $v('purchase_price') }}" placeholder="0" min="0" step="any">
                        </div>
                        @error('purchase_price')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-sm-6 {{ $errors->has('qty') ? 'has-error' : '' }}">
                        <label for="f-qty">Quantity</label>
                        <input type="number" id="f-qty" name="qty" class="form-control" value="{{ $v('qty') }}" placeholder="0" min="0" step="1">
                        @error('qty')<span class="help-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="row">
                    @include('inventory._select', ['name' => 'manufacture_id', 'label' => 'Manufacture', 'options' => $manufactures,
                        'value' => $v('manufacture_id'), 'fallback' => $item->manufacture_name ?? null, 'col' => 'col-sm-6'])
                    @include('inventory._select', ['name' => 'supplier_id', 'label' => 'Supplier/Agent', 'options' => $suppliers,
                        'value' => $v('supplier_id'), 'fallback' => $item->supplier_name ?? null, 'col' => 'col-sm-6'])
                </div>
            </section>
        </div>

        {{-- ================= Kolom kanan ================= --}}
        <div class="col-lg-4">
            <section class="rp-card rf-section">
                <h3 class="rf-section-title"><i class="bi bi-geo-alt"></i> Location</h3>
                @include('inventory._select', ['name' => 'id_location', 'label' => 'Location', 'options' => $locations,
                    'value' => $selectedLocation, 'fallback' => $item->location_name ?? null, 'required' => true])
                @include('inventory._select', ['name' => 'location_det_id', 'label' => 'Location Detail', 'options' => $locationDetails,
                    'value' => $v('location_det_id'), 'fallback' => $item->location_detail_name ?? null, 'required' => true])

                <div class="form-group">
                    <label>Scheduled check</label>
                    <div class="rf-toggle" role="radiogroup" aria-label="Scheduled check">
                        <label><input type="radio" name="scheduled" value="YES" @checked($scheduled === 'YES')><span><i class="bi bi-calendar-check"></i> Yes</span></label>
                        <label><input type="radio" name="scheduled" value="NO" @checked($scheduled === 'NO')><span><i class="bi bi-calendar-x"></i> No</span></label>
                    </div>
                </div>
            </section>

            <section class="rp-card rf-section">
                <h3 class="rf-section-title"><i class="bi bi-images"></i> Photos</h3>
                <label class="rf-drop" data-drop="image">
                    <input type="file" id="image-input" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                    <i class="bi bi-cloud-arrow-up"></i>
                    <b>Drop photos here or click to browse</b>
                    <small>JPG, PNG, GIF or WEBP · max 5 MB each</small>
                </label>
                <div class="rf-thumbs" id="image-list">
                    @foreach ($savedImages as $file)
                        <div class="rf-thumb" data-existing="{{ $file }}">
                            <a href="{{ asset($imageDir.'/'.$file) }}" target="_blank"><img src="{{ asset($imageDir.'/'.$file) }}" alt=""></a>
                            <button type="button" class="rf-thumb-x" title="Remove">&times;</button>
                        </div>
                    @endforeach
                    @foreach ($newImages as $file)
                        <div class="rf-thumb">
                            <a href="{{ asset($imageDir.'/'.$file) }}" target="_blank"><img src="{{ asset($imageDir.'/'.$file) }}" alt=""></a>
                            <button type="button" class="rf-thumb-x" title="Remove">&times;</button>
                            <input type="hidden" name="image_upload[]" value="{{ $file }}">
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rp-card rf-section">
                <h3 class="rf-section-title"><i class="bi bi-file-earmark-text"></i> Receipts</h3>
                <label class="rf-drop rf-drop-sm" data-drop="receipt">
                    <input type="file" id="receipt-input" accept="application/pdf,image/jpeg,image/png,image/webp" multiple>
                    <i class="bi bi-paperclip"></i>
                    <b>Attach receipt</b>
                    <small>PDF, JPG, PNG or WEBP · max 10 MB</small>
                </label>
                <ul class="rf-files" id="receipt-list">
                    @foreach ($savedReceipts as $file)
                        <li data-existing="{{ $file }}">
                            <i class="bi {{ str_ends_with(strtolower($file), '.pdf') ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' }}"></i>
                            <a href="{{ asset($receiptDir.'/'.$file) }}" target="_blank">{{ $file }}</a>
                            <button type="button" class="rf-file-x" title="Remove">&times;</button>
                        </li>
                    @endforeach
                    @foreach ($newReceipts as $file)
                        <li>
                            <i class="bi bi-file-earmark"></i>
                            <a href="{{ asset($receiptDir.'/'.$file) }}" target="_blank">{{ $file }}</a>
                            <button type="button" class="rf-file-x" title="Remove">&times;</button>
                            <input type="hidden" name="receipt_upload[]" value="{{ $file }}">
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>

    <div class="rf-actionbar">
        <a href="{{ $isCreate ? route('item_master.index') : route('item_master.read', ['id' => $item->inventory_number]) }}" class="btn btn-default">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> {{ $button }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
    var isCreate = @json($isCreate);
    var urls = {
        sub: @json(route('item_sub_category.options')),
        detail: @json(route('location_detail.options')),
        next: @json(route('item_master.next_number')),
        image: @json(route('item_master.upload')),
        receipt: @json(route('item_master.upload_receipt'))
    };
    var currencyByLocation = @json(\App\Support\InventoryNumber::CURRENCY);
    var $form = $('#item-form');
    var esc = function (s) { return $('<div>').text(s == null ? '' : s).html(); };

    // ---------- Dropdown bertingkat (endpoint sama dengan CI3) ----------
    function loadOptions(url, parentId, $target) {
        if (!parentId) { $target.html('<option value=""></option>').val('').trigger('change'); return; }
        $.ajax({ url: url, type: 'POST', data: { id: parentId }, dataType: 'json', global: false })
            .done(function (r) { $target.html(r.data).val('').trigger('change'); });
    }

    // ---------- Nomor inventaris otomatis (CI3 memuat ulang halaman lewat create2) ----------
    var numberTouched = $('#f-inventory_number').val() !== '';
    $('#f-inventory_number').on('input', function () { numberTouched = this.value !== ''; });

    function refreshNumber(force) {
        if (!isCreate || (numberTouched && !force)) return;
        var category = $('#f-category_id').val();
        if (!category) return;
        var $btn = $('#btn-regenerate').prop('disabled', true).find('i').addClass('rf-spin').end();
        $.ajax({ url: urls.next, data: { category_id: category, id_location: $('#f-id_location').val() }, dataType: 'json', global: false })
            .done(function (r) {
                if (r.number) {
                    $('#f-inventory_number').val(r.number).addClass('rf-flash');
                    setTimeout(function () { $('#f-inventory_number').removeClass('rf-flash'); }, 900);
                    numberTouched = false;
                }
            })
            .always(function () { $btn.prop('disabled', false).find('i').removeClass('rf-spin'); });
    }
    $('#btn-regenerate').on('click', function () { refreshNumber(true); });

    $('#f-category_id').on('change', function () {
        loadOptions(urls.sub, this.value, $('#f-sub_category_id'));
        refreshNumber(false);
    });

    var currencyTouched = !isCreate;
    $('#f-currency').on('change', function () { currencyTouched = true; });
    $('#f-id_location').on('change', function () {
        loadOptions(urls.detail, this.value, $('#f-location_det_id'));
        refreshNumber(false);
        if (!currencyTouched && currencyByLocation[this.value]) $('#f-currency').val(currencyByLocation[this.value]);
    });

    // Kategori sudah dipilih (dari URL lama create2) tapi nomor masih kosong
    if (isCreate && $('#f-category_id').val() && !numberTouched) refreshNumber(false);

    // ---------- Upload foto & struk ----------
    function uploadFile(file, kind) {
        var isImage = kind === 'image';
        var fd = new FormData();
        fd.append(kind, file);

        var $item = isImage
            ? $('<div class="rf-thumb rf-uploading"><span class="rp-spinner"></span></div>').appendTo('#image-list')
            : $('<li class="rf-uploading"><span class="rp-spinner"></span> ' + esc(file.name) + '</li>').appendTo('#receipt-list');

        $.ajax({ url: isImage ? urls.image : urls.receipt, type: 'POST', data: fd, contentType: false,
                 processData: false, dataType: 'json', global: false })
            .done(function (r) {
                var hidden = '<input type="hidden" name="' + (isImage ? 'image_upload[]' : 'receipt_upload[]') + '" value="' + esc(r.file) + '">';
                if (isImage) {
                    $item.removeClass('rf-uploading').html(
                        '<a href="' + esc(r.url) + '" target="_blank"><img src="' + esc(r.url) + '" alt=""></a>' +
                        '<button type="button" class="rf-thumb-x" title="Remove">&times;</button>' + hidden);
                } else {
                    var icon = /\.pdf$/i.test(r.file) ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image';
                    $item.removeClass('rf-uploading').html(
                        '<i class="bi ' + icon + '"></i><a href="' + esc(r.url) + '" target="_blank">' + esc(r.original || r.file) + '</a>' +
                        '<button type="button" class="rf-file-x" title="Remove">&times;</button>' + hidden);
                }
            })
            .fail(function (x) {
                $item.remove();
                var msg = (x.responseJSON && (x.responseJSON.error || x.responseJSON.message)) || 'Upload failed.';
                riseToast(esc(file.name) + ': ' + esc(msg));
            });
    }

    function riseToast(html) {
        var $t = $('<div class="rp-toast rp-toast-error" role="status"><i class="bi bi-x-octagon-fill"></i><span>' + html +
                   '</span><button type="button">&times;</button></div>');
        $t.find('button').on('click', function () { $t.remove(); });
        $('.rp-toasts').append($t);
        setTimeout(function () { $t.addClass('rp-toast-out'); }, 5000);
        setTimeout(function () { $t.remove(); }, 5600);
    }

    $('#image-input').on('change', function () { $.each(this.files, function (i, f) { uploadFile(f, 'image'); }); this.value = ''; });
    $('#receipt-input').on('change', function () { $.each(this.files, function (i, f) { uploadFile(f, 'receipt'); }); this.value = ''; });

    // Seret & lepas
    $('.rf-drop').on('dragover dragenter', function (e) { e.preventDefault(); $(this).addClass('rf-drag'); })
        .on('dragleave drop', function (e) { e.preventDefault(); $(this).removeClass('rf-drag'); })
        .on('drop', function (e) {
            var kind = $(this).data('drop');
            $.each(e.originalEvent.dataTransfer.files, function (i, f) { uploadFile(f, kind); });
        });

    // Hapus: file lama ditandai dan baru benar-benar dihapus saat form disimpan (CI3 langsung menghapus)
    $('#image-list').on('click', '.rf-thumb-x', function () {
        var $thumb = $(this).closest('.rf-thumb');
        if ($thumb.data('existing')) $form.append($('<input type="hidden" name="remove_images[]">').val($thumb.data('existing')));
        $thumb.remove();
    });
    $('#receipt-list').on('click', '.rf-file-x', function () {
        var $li = $(this).closest('li');
        if ($li.data('existing')) $form.append($('<input type="hidden" name="remove_receipts[]">').val($li.data('existing')));
        $li.remove();
    });

    // Jangan simpan selagi masih ada file yang sedang diunggah
    $form.on('submit', function (e) {
        if ($('.rf-uploading').length) {
            e.preventDefault();
            e.stopImmediatePropagation();
            setTimeout(function () { $('.loadscreen').hide(); }, 0); // layout sudah menampilkan loading saat submit
            riseToast('Please wait until all uploads have finished.');
        }
    });
});
</script>
@endpush
