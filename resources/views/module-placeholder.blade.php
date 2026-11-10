@extends('layouts.app')

@section('title', $title)
@section('page_header', $title)

@section('content')
<div class="box box-default">
    <div class="box-body text-center" style="padding:48px 16px;">
        <i class="fa fa-wrench fa-3x text-muted"></i>
        <h3>Modul "{{ $title }}" sedang dimigrasi</h3>
        <p class="text-muted">Halaman ini akan tersedia setelah modul <code>{{ $module }}</code> selesai dipindahkan ke Laravel.</p>
        <a href="{{ url('welcome') }}" class="btn btn-primary"><i class="fa fa-home"></i> Kembali ke Dashboard</a>
    </div>
</div>
@endsection
