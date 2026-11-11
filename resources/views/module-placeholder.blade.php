@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="rp-card" style="text-align:center;padding:56px 16px;">
    <i class="bi bi-cone-striped" style="font-size:42px;color:#b45309"></i>
    <h3 style="font-weight:800;margin:14px 0 6px">"{{ $title }}" is not available yet</h3>
    <p class="text-muted">This menu (<code>{{ $module }}</code>) has not been moved to the new version of the app.</p>
    <a href="{{ url('welcome') }}" class="btn btn-primary"><i class="bi bi-house-door"></i> Back to Dashboard</a>
</div>
@endsection
