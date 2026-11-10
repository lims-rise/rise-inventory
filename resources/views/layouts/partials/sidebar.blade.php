{{--
    Sidebar: dropdown lokasi (negara) + menu dari tbl_menu/tbl_hak_akses + LOGOUT.
    Data dari AppServiceProvider: $sidebarMenus, $locationOptions, $currentLocationId, $canSwitchLocation
--}}
@php
    $current = strtolower((string) request()->segment(1));
    $moduleOf = fn ($url) => \App\Services\MenuService::moduleOf($url);
    $href = fn ($url) => in_array(trim((string) $url), ['', '#'], true) ? '#' : url(trim($url, '/'));
@endphp

{{-- CSS sidebar ada di layouts/app.blade.php (blok <style>) --}}
<section class="sidebar">
    {{-- Dropdown lokasi: location = negara. Pilihan disimpan di session 'location_id' --}}
    <form method="POST" action="{{ route('location.switch') }}" class="sidebar-location">
        @csrf
        <label for="sidebar-location">Location</label>
        <div class="select-wrap">
            <i class="bi bi-globe2"></i>
            <select id="sidebar-location" name="location_id" class="form-control" onchange="this.form.submit()" @disabled(! $canSwitchLocation)>
                @foreach ($locationOptions as $location)
                    <option value="{{ $location->id }}" @selected($location->id == $currentLocationId)>
                        {{ \App\Services\LocationContext::label($location->name) }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    <ul class="sidebar-menu" data-widget="tree">
        @foreach ($sidebarMenus as $menu)
            @if ($menu->children->isEmpty())
                @continue(trim($menu->url) === '#') {{-- induk tanpa sub menu yang boleh diakses: sembunyikan --}}
                <li class="{{ $moduleOf($menu->url) === $current ? 'active' : '' }}">
                    <a href="{{ $href($menu->url) }}">
                        <i class="{{ $menu->icon }}"></i> <span>{{ $menu->title }}</span>
                    </a>
                </li>
            @else
                @php $open = $menu->children->contains(fn ($c) => $moduleOf($c->url) === $current); @endphp
                <li class="treeview {{ $open ? 'active menu-open' : '' }}">
                    <a href="#">
                        <i class="{{ $menu->icon }}"></i> <span>{{ $menu->title }}</span>
                        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                    </a>
                    <ul class="treeview-menu">
                        @foreach ($menu->children as $child)
                            <li class="{{ $moduleOf($child->url) === $current ? 'active' : '' }}">
                                <a href="{{ $href($child->url) }}">
                                    <i class="{{ trim($child->icon) !== '' && trim($child->icon) !== 'fa' ? $child->icon : 'fa fa-circle-o' }}"></i>
                                    {{ $child->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endif
        @endforeach

        <li class="sidebar-logout-item">
            <a href="#" class="sidebar-logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa fa-arrow-circle-left"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</section>
