<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div>
            <img src="{{asset('assets/images/logo-icon.png')}}" class="logo-icon" alt="logo icon">
        </div>
        <div>
            <h4 class="logo-text" style="color:black">GROCARIES</h4>
        </div>
        <div class="toggle-icon ms-auto">
            <i class='bx bx-arrow-to-left' style="color:black"></i>
        </div>
    </div>
    <!--navigation-->
    <ul class="metismenu" id="menu">

        {{-- Dashboard / Laba --}}
        <li>
            <a href="{{route('home')}}">
                <div class="parent-icon"><i class='bx bx-home-circle'></i></div>
                <div class="menu-title">Laba</div>
            </a>
        </li>

        {{-- Sales --}}
        <li>
            <a href="{{route('sales.index')}}">
                <div class="parent-icon"><i class='bx bx-user-circle'></i></div>
                <div class="menu-title">Sales</div>
            </a>
        </li>

        {{-- Supplier (Admin / Superadmin only) --}}
        @if(Auth::user()->roles == 'superadmin' || Auth::user()->roles == 'admin')
        <li>
            <a href="{{route('supplier.index')}}">
                <div class="parent-icon"><i class='bx bx-book'></i></div>
                <div class="menu-title">Kelola Supplier</div>
            </a>
        </li>
        @endif

        {{-- Store (Superadmin only) --}}
        @if(Auth::user()->roles == 'superadmin')
        <li>
            <a href="{{route('store.index')}}">
                <div class="parent-icon"><i class='bx bx-store'></i></div>
                <div class="menu-title">Kelola Toko</div>
            </a>
        </li>
        @endif

        @if(Auth::user()->roles == 'superadmin' || Auth::user()->roles == 'admin')
        {{-- User --}}
        <li>
            <a href="{{route('user.index')}}">
                <div class="parent-icon"><i class='bx bx-user'></i></div>
                <div class="menu-title">Kelola User</div>
            </a>
        </li>
        @endif

        @if(Auth::user()->roles == 'superadmin' || Auth::user()->roles == 'admin')
        {{-- Customer --}}
        <li>
            <a href="{{route('customer.index')}}">
                <div class="parent-icon"><i class="lni lni-customer"></i></div>
                <div class="menu-title">Kelola Customer</div>
            </a>
        </li>
        @endif

        {{-- Master Data --}}
        @if(Auth::user()->roles == 'superadmin' || Auth::user()->roles == 'admin')
        <li>
            <a href="javascript:void(0)" class="has-arrow">
                <div class="parent-icon"><i class="bx bx-category"></i></div>
                <div class="menu-title">Master Data</div>
            </a>
            <ul>
                <li><a href="{{route('master-types.index')}}"><i class="bx bx-right-arrow-alt"></i>Kelola Jenis</a></li>
                <li><a href="{{route('master-brand.index')}}"><i class="bx bx-right-arrow-alt"></i>Kelola Merek</a></li>
                <li><a href="{{ route('master-product.index') }}"><i class="bx bx-right-arrow-alt"></i>Kelola Produk</a></li>
                <li><a href="{{route('master-unit.index')}}"><i class="bx bx-right-arrow-alt"></i>Kelola Satuan</a></li>
            </ul>
        </li>
        @endif
        @if(Auth::user()->roles =='staff' )
 <li>
            <a href="{{route('purchase.index')}}">
                <div class="parent-icon"><i class="bx bx-right-arrow-alt"></i></div>
                <div class="menu-title">Pembelian</div>
            </a>
        </li>

        @endif
        @if(Auth::user()->roles =='superadmin' || Auth::user()->roles == 'admin')
        {{-- Stock Opname --}}
        <li>
            <a href="javascript:void(0)" class="has-arrow">
                <div class="parent-icon"><i class="bx bx-category"></i></div>
                <div class="menu-title">Stock Opname</div>
            </a>
            <ul>
                <li><a href="{{route('purchase.index')}}"><i class="bx bx-right-arrow-alt"></i>Pembelian</a></li>
                <li><a href="{{route('inventory.index')}}"><i class="bx bx-right-arrow-alt"></i>Inventory</a></li>
                <li><a href="{{route('sell.index')}}"><i class="bx bx-right-arrow-alt"></i>Barang Keluar</a></li>
            </ul>

        </li>
        @endif

        @if(Auth::user()->roles =='superadmin' || Auth::user()->roles == 'admin')
        {{-- Stock Opname --}}
        <li>
            <a href="javascript:void(0)" class="has-arrow">
                <div class="parent-icon"><i class="bx bx-building"></i></div>
                <div class="menu-title">Laporan</div>
            </a>
            <ul>
                <li><a href="{{route('report.index')}}"><i class="bx bx-right-arrow-alt"></i>Laporan Kasir</a></li>
            </ul>

        </li>
        @endif
        <li>
            <a href="javascript:void(0)" class="has-arrow">
                <div class="parent-icon"><i class="bx bx-category"></i></div>
                <div class="menu-title">Settings</div>
            </a>
            <ul>
                <li><a href="{{route('settings.roles.index')}}"><i class="bx bx-right-arrow-alt"></i>Roles</a></li>
                <li><a href="{{route('settings.menus.index')}}"><i class="bx bx-right-arrow-alt"></i>Menus</a></li>
            </ul>

        </li>
        {{-- 🔄 Sinkronisasi (tampil untuk semua user yang login) --}}
        <!-- <li>
            <a href="{{ route('sync.manual') }}">
                <div class="parent-icon"><i class='bx bx-sync'></i></div>
                <div class="menu-title">Sinkronkan Data</div>
            </a>
        </li> -->

    </ul>
    <!--end navigation-->
</div>
