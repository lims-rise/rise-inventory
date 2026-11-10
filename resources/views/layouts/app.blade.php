<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') | @endif Assets DB</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    {{-- Urutan dan path sama dengan template.php CI3. Folder assets/ disalin ke public/assets --}}
    {{-- jQuery UI 1.13 (sebelumnya 1.10 dari folder assets, tidak cocok dengan jQuery 3) --}}
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.min.css">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/bower_components/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/bower_components/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/bower_components/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/bower_components/Ionicons/css/ionicons.min.css') }}">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.1.5/css/fixedHeader.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/dist/css/AdminLTE.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/dist/css/skins/_all-skins.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Skin RISE (senada halaman login). Dimuat paling akhir supaya menimpa AdminLTE --}}
    <link rel="stylesheet" href="{{ asset('css/rise-theme.css') }}?v={{ @filemtime(public_path('css/rise-theme.css')) }}">

    <style>
        .image-area { position: relative; width: 80px; }
        .image-area img { max-width: 100%; height: auto; }
        .remove-image {
            display: none; position: absolute; top: -10px; right: -10px;
            border-radius: 10em; padding: 2px 6px 3px; text-decoration: none;
            font: 700 21px/20px sans-serif; background: #555; border: 3px solid #fff; color: #FFF;
            box-shadow: 0 2px 6px rgba(0,0,0,.5), inset 0 2px 4px rgba(0,0,0,.3);
            text-shadow: 0 1px 2px rgba(0,0,0,.5); transition: background .5s;
        }
        .remove-image:hover { background: #E54E4E; padding: 3px 7px 5px; top: -11px; right: -11px; }
        .remove-image:active { background: #E54E4E; top: -10px; right: -11px; }

        .loadscreen {
            text-align: center; position: fixed; width: 100%; left: 0; right: 0; margin: auto; top: 0;
            height: 100vh; background: #ffffff97; z-index: 9999999;
        }
        .loadscreen .loader { position: absolute; top: calc(50vh - 50px); left: 0; right: 0; margin: auto; }
        .loadscreen .logo { display: inline-block !important; width: 80px; height: 80px; }

        .highcharts-figure, .highcharts-data-table table { min-width: 320px; max-width: 660px; margin: 1em auto; }
        .highcharts-data-table table {
            font-family: Verdana, sans-serif; border-collapse: collapse; border: 1px solid #ebebeb;
            margin: 10px auto; text-align: center; width: 100%; max-width: 500px;
        }
        .highcharts-data-table caption { padding: 1em 0; font-size: 1.2em; color: #555; }
        .highcharts-data-table th { font-weight: 600; padding: .5em; }
        .highcharts-data-table td, .highcharts-data-table th, .highcharts-data-table caption { padding: .5em; }
        .highcharts-data-table thead tr, .highcharts-data-table tr:nth-child(even) { background: #f8f8f8; }
        .highcharts-data-table tr:hover { background: #f1f7ff; }
    </style>
    @stack('styles')

    <script>
        // Pengaman: apa pun yang terjadi (CDN lambat/diblokir, error JS), loading screen hilang maks. 4 detik
        setTimeout(function () {
            var el = document.querySelector('.loadscreen');
            if (el) { el.style.display = 'none'; }
        }, 4000);
    </script>
    {{--
        jQuery 3.7.1 (sebelumnya 1.11.2) + jQuery Migrate 3.5.2.
        Migrate menambal fungsi lama yang sudah dihapus di jQuery 3 (mis. .size(), .bind(), $.browser)
        dan di mode local menulis peringatan "JQMIGRATE" di Console untuk kode yang perlu diperbarui.
        Bila CDN gagal, otomatis memakai salinan lokal.
    --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>window.jQuery || document.write('<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"><\/script>')</script>
    <script src="https://code.jquery.com/jquery-migrate-3.5.2.min.js"></script>
    <script>window.jQuery.migrateVersion || document.write('<script src="{{ asset('assets/js/jquery-migrate-3.5.2.min.js') }}"><\/script>')</script>
    <script>
        jQuery.migrateMute = {{ app()->isProduction() ? 'true' : 'false' }};
        jQuery.migrateTrace = false;
    </script>
    <script>
        // Token CSRF otomatis untuk semua request AJAX jQuery (wajib di Laravel untuk POST/PUT/DELETE)
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    </script>
</head>
<body class="hold-transition skin-rise sidebar-mini">
<div class="wrapper">
    <div class="loadscreen">
        <div class="loader">
            <img class="logo mb-3" src="{{ asset('image/loading.gif') }}" style="display: none" alt="">
            <div class="loader-bubble loader-bubble-primary d-block"></div>
        </div>
    </div>

    @php $authUser = auth()->user(); @endphp
    <header class="main-header">
        <a href="{{ url('welcome') }}" class="logo">
            <span class="logo-mini"><b>R</b>IN</span>
            <span class="logo-lg"><b>INVENTORY</b></span>
        </a>
        <nav class="navbar navbar-static-top">
            <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>

            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
                    <li class="dropdown user user-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <img src="{{ $authUser->avatarUrl() }}" class="user-image" style="object-fit: cover;width:30px;height:30px;" alt="User Image">
                            <span class="hidden-xs">{{ $authUser->full_name }}</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="user-header">
                                <img src="{{ $authUser->avatarUrl() }}" class="img-circle" style="object-fit: cover;width:75px;height:75px;" alt="User Image">
                                <p>
                                    {{ $authUser->full_name }}
                                    <small>{{ $authUser->email }}</small>
                                </p>
                            </li>
                            <li class="user-footer">
                                <div class="pull-left">
                                    <a href="{{ url('tbl_user_profile') }}" class="btn btn-warning btn-flat">Profile</a>
                                </div>
                                <div class="pull-right">
                                    <button type="submit" form="logout-form" class="btn btn-default btn-flat">Logout</button>
                                </div>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <aside class="main-sidebar">
        @include('layouts.partials.sidebar')
    </aside>

    <div class="content-wrapper">
        @hasSection('page_header')
            <section class="content-header">
                <h1>@yield('page_header') <small>@yield('page_subheader')</small></h1>
            </section>
        @endif

        <section class="content">
            @yield('content')
        </section>
    </div>

    {{-- Notifikasi (flash message) sebagai toast di kanan atas, hilang otomatis --}}
    <div class="rp-toasts" aria-live="polite">
        @foreach (['success' => 'bi-check-circle-fill', 'error' => 'bi-x-octagon-fill', 'warning' => 'bi-exclamation-triangle-fill'] as $key => $icon)
            @if (session($key))
                <div class="rp-toast rp-toast-{{ $key }}" role="status">
                    <i class="bi {{ $icon }}"></i>
                    <span>{{ session($key) }}</span>
                    <button type="button" aria-label="Close" onclick="this.parentNode.remove()">&times;</button>
                </div>
            @endif
        @endforeach
    </div>

    <footer class="main-footer">
        <div class="pull-right hidden-xs"><b>Version</b> 1.1.0</div>
        <strong>Copyright &copy; 2023 RISE</strong>
    </footer>

    <div class="control-sidebar-bg"></div>
</div>

{{-- Satu form logout (POST + CSRF) dipakai tombol di header dan menu LOGOUT di sidebar --}}
<form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none">@csrf</form>

<script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>
<script>$.ui || document.write('<script src="{{ asset('assets/js/jquery-ui-1.13.3.min.js') }}"><\/script>')</script>
<script src="{{ asset('assets/adminlte/bower_components/bootstrap/dist/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/adminlte/bower_components/jquery-slimscroll/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('assets/adminlte/bower_components/fastclick/lib/fastclick.js') }}"></script>
<script src="{{ asset('assets/adminlte/dist/js/adminlte.min.js') }}"></script>
<script src="{{ asset('assets/adminlte/dist/js/demo.js') }}"></script>
<script src="{{ asset('assets/adminlte/bower_components/select2/dist/js/select2.full.min.js') }}"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.1.5/js/dataTables.fixedHeader.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script>
    // Tutup loading screen saat HTML siap (tidak menunggu semua gambar/CDN selesai seperti 'load')
    function riseHideLoader(delay) { $('.loadscreen').stop(true, true).delay(delay || 0).fadeOut(200); }
    $(function () { riseHideLoader(150); });
    $(window).on('load', function () { riseHideLoader(); });
    $(document)
        .ajaxStart(function () { $('.loadscreen').show(); })
        .ajaxComplete(function () { $('.loadscreen').delay(300).fadeOut(); })
        .ajaxError(function (event, response) {
            $('.loadscreen').delay(300).fadeOut();
            // Sesi habis -> kembali ke login
            if (response.status === 401 || response.status === 419) {
                window.location.href = '{{ route('login') }}';
            }
        });
    $(function () {
        // Dibungkus try supaya error select2 tidak mengganggu script lain
        try { if ($.fn.select2) { $('.select2').select2(); } } catch (e) { console.error('select2:', e); }
        $('form').on('submit', function () { $('.loadscreen').show(); });
        // Toast hilang otomatis setelah 4 detik
        setTimeout(function () { $('.rp-toast').addClass('rp-toast-out'); }, 4000);
        setTimeout(function () { $('.rp-toast').remove(); }, 4600);
    });
</script>
@stack('scripts')
</body>
</html>
