<header>
    <div class="topbar d-flex align-items-center">
        <nav class="navbar navbar-expand">
            <div class="mobile-toggle-menu"><i class='bx bx-menu'></i></div>
            <div class="search-bar flex-grow-1"></div>
            <div class="top-menu ms-auto">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item mobile-search-icon">
                        <a class="nav-link" href="#"><i class='bx bx-search'></i></a>
                    </li>
                </ul>
            </div>
            <div class="user-box dropdown">
                @guest
                    <div class="user-info ps-3">
                        <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                    </div>
                @else
                    <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ asset('assets/images/avatars/avatar-2.png') }}" class="user-img" alt="user avatar">
                        <div class="user-info ps-3">
                            <p class="user-name mb-0">{{ Auth::user()->name }}</p>
                            <p class="designattion mb-0">{{ Auth::user()->email }}</p>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="javascript:;"><i class="bx bx-user"></i><span>Profile</span></a>
                        </li>
                        <div class="dropdown-divider mb-0"></div>
                        <li>
                            @if(strtolower(Auth::user()->roles) === 'staff')
                                <a class="dropdown-item" href="#" onclick="event.preventDefault(); showRevenueModal();">
                                    <i class='bx bx-log-out-circle'></i><span>{{ __('Logout') }}</span>
                                </a>
                            @else
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class='bx bx-log-out-circle'></i><span>{{ __('Logout') }}</span>
                                </a>
                            @endif
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    </ul>
                @endguest
            </div>
        </nav>
    </div>

    {{-- Modal input pendapatan harian (khusus staff) --}}
    @if(Auth::check() && strtolower(Auth::user()->roles) === 'staff')
    <div class="modal fade" id="revenueModal" tabindex="-1" aria-labelledby="revenueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="revenueForm" method="POST" action="{{ route('staff.submitRevenueAndLogout') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="revenueModalLabel">Pendapatan Hari Ini</h5>
                    </div>
                    <div class="modal-body">
                        <label for="amount">Masukkan pendapatan Anda hari ini:</label>
                        <input type="number" name="amount" id="amount" class="form-control" required min="0" autofocus>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Submit & Logout</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif
</header>

{{-- Script Modal Logout Staff --}}
@if(Auth::check() && strtolower(Auth::user()->roles) === 'staff')
    <script>
        function showRevenueModal() {
            const modal = new bootstrap.Modal(document.getElementById('revenueModal'));
            modal.show();
        }
    </script>
@endif
