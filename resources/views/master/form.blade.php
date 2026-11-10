@extends('layouts.app')

@section('title', $button.' '.$resource->title)

@section('content')
@include('master._header', [
    'heading' => $button.' '.$resource->title,
    'crumb' => $button,
    'sub' => $row ? 'Editing "'.($row->name ?? $row->getKey()).'"' : 'Add a new '.strtolower($resource->title).' to the master list',
])

<div class="row">
    <div class="col-lg-7 col-md-9">
        <form method="POST" action="{{ $action }}" class="rp-card rp-form" novalidate>
            @csrf
            @if ($row)
                <input type="hidden" name="id" value="{{ $row->getKey() }}">
            @endif

            @foreach ($resource->fields as $key => $field)
                @php
                    $value = old($key, $row?->getAttribute($key));
                    $maxRule = collect($field['rules'])->first(fn ($r) => is_string($r) && str_starts_with($r, 'max:'));
                    $max = $maxRule ? (int) substr($maxRule, 4) : null;
                @endphp
                <div class="form-group {{ $errors->has($key) ? 'has-error' : '' }}">
                    <label for="f-{{ $key }}">
                        {{ $field['label'] }}
                        @if (in_array('required', $field['rules'], true)) <span class="rp-req">*</span> @endif
                    </label>

                    @if ($field['type'] === 'select')
                        <select id="f-{{ $key }}" name="{{ $key }}" class="form-control select2" style="width:100%">
                            <option value="">— Select {{ strtolower($field['label']) }} —</option>
                            @foreach ($resource->options($key) as $optValue => $optLabel)
                                <option value="{{ $optValue }}" @selected((string) $optValue === (string) $value)>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($field['type'] === 'textarea')
                        <textarea id="f-{{ $key }}" name="{{ $key }}" class="form-control" rows="4"
                                  placeholder="{{ $field['placeholder'] }}">{{ $value }}</textarea>
                    @else
                        <input type="text" id="f-{{ $key }}" name="{{ $key }}" value="{{ $value }}" class="form-control input-lg"
                               placeholder="{{ $field['placeholder'] }}" @if ($loop->first) autofocus @endif
                               @if ($max) maxlength="{{ $max }}" @endif>
                    @endif

                    @error($key)
                        <span class="help-block"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                    @enderror
                </div>
            @endforeach

            <div class="rp-form-actions">
                <a href="{{ $resource->route('index') }}" class="btn btn-default"><i class="bi bi-arrow-left"></i> Back</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> {{ $button }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
