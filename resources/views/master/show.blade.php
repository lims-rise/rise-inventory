@extends('layouts.app')

@section('title', 'Detail '.$resource->title)

@section('content')
@include('master._header', [
    'heading' => $row->name ?? ('#'.$row->getKey()),
    'crumb' => 'Detail',
    'sub' => 'Detail '.strtolower($resource->title),
])

<div class="row">
    <div class="col-lg-7 col-md-9">
        <div class="rp-card">
            <dl class="rp-detail">
                @foreach ($resource->fields as $key => $field)
                    <dt>{{ $field['label'] }}</dt>
                    <dd>{{ $resource->display($row, $key) ?: '-' }}</dd>
                @endforeach
                <dt>Last updated</dt>
                <dd>
                    {{ $row->created_at ? $row->created_at->format('d M Y, H:i') : '-' }}
                    @if ($createdBy) <span class="text-muted">by {{ $createdBy }}</span> @endif
                </dd>
            </dl>

            <div class="rp-form-actions">
                <a href="{{ $resource->route('index') }}" class="btn btn-default"><i class="bi bi-arrow-left"></i> Back</a>
                <a href="{{ $resource->route('edit', $row->getKey()) }}" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
            </div>
        </div>
    </div>
</div>
@endsection
