<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div>
            <img src="{{asset('assets/images/logo-icon.png')}}" class="logo-icon" alt="logo icon">
        </div>
        <div>
            <h4 class="logo-text" style="color:black">GROCARIES</h4>
        </div>
        <div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left' style="color:black"></i>
        </div>
    </div>
    <!--navigation-->
    <ul class="metismenu" id="menu">
        <li>
            <a href="#" >
                <div class="parent-icon"><i class='bx bx-home-circle'></i>
                </div>
                <div class="menu-title">Dashboard</div>
            </a>
        </li>
        <li>
            <a href="#" >
                <div class="parent-icon"><i class='bx bx-user-circle'></i>
                </div>
                <div class="menu-title">Kelola Admin</div>
            </a>
        </li>
        <li>
            <a href="#" >
                <div class="parent-icon"><i class='bx bx bx-task'></i>
                </div>
                <div class="menu-title">Kelola Kegiatan</div>
            </a>
        </li>
        <li>
            <a href="#" >
                <div class="parent-icon"><i class='bx bx-book'></i>
                </div>
                <div class="menu-title">Kelola Rapat</div>
            </a>
        </li>
        <li>
            <a href="#" >
                <div class="parent-icon"><i class='bx bx-store'></i>
                </div>
                <div class="menu-title">Kelola Toko</div>
            </a>
        </li>
        <li>
            <a href="#" >
                <div class="parent-icon"><i class='bx bx-user'></i>
                </div>
                <div class="menu-title">Kelola User</div>
            </a>
        </li>

        <li>
            <a href="javascript:void(0)" class="has-arrow">
                <div class="parent-icon"><i class="bx bx-category"></i>
                </div>
                <div class="menu-title">Master Data</div>
            </a>
            <ul>
                <li> <a href="{{route('manage-types.index')}}"><i class="bx bx-right-arrow-alt"></i>Kelola Jenis</a>
                </li>
                <li> <a href="#"><i class="bx bx-right-arrow-alt"></i>Kelola Merek</a>
                </li>
                <li> <a href="#"><i class="bx bx-right-arrow-alt"></i>Kelola Produk</a>
                </li>
                <li> <a href="#"><i class="bx bx-right-arrow-alt"></i>Kelola Satuan</a>
                </li>
            </ul>
        </li>

    </ul>
    <!--end navigation-->
</div>
