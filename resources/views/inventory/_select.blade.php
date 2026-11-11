{{--
    Dropdown select2 untuk form aset.
    $name, $label, $options (id => nama), $value, $fallback (nama untuk nilai lama yang sudah non-aktif),
    $required (bool), $col (kelas kolom bootstrap)
--}}
@php
    $current = (string) ($value ?? '');
    $known = collect($options)->keys()->map(fn ($k) => (string) $k)->contains($current);
@endphp
<div class="form-group {{ $col ?? '' }} {{ $errors->has($name) ? 'has-error' : '' }}">
    <label for="f-{{ $name }}">{{ $label }} @if (! empty($required))<span class="rp-req">*</span>@endif</label>
    <select id="f-{{ $name }}" name="{{ $name }}" class="form-control select2" style="width:100%"
            data-placeholder="Select {{ strtolower($label) }}" data-allow-clear="{{ empty($required) ? 'true' : 'false' }}">
        <option value=""></option>
        @if ($current !== '' && ! $known)
            {{-- nilai lama yang sudah dihapus/non-aktif tetap tampil supaya data tidak hilang --}}
            <option value="{{ $current }}" selected>{{ $fallback ?: '#'.$current }} (inactive)</option>
        @endif
        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected((string) $optValue === $current)>{{ $optLabel }}</option>
        @endforeach
    </select>
    @error($name)<span class="help-block"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
</div>
