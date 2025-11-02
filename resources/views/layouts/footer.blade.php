{{-- Core JS --}}
<script src="{{ asset('assets/js/jquery.min.js') }}"></script> {{-- jika tidak ada, hapus baris ini --}}
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

{{-- Plugins untuk sidebar --}}
<script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
<script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.min.js') }}"></script>

{{-- App JS (jika tema kamu punya) --}}
<script src="{{ asset('assets/js/app.js') }}"></script>

<script>
  // SimpleBar (sesuai atribut data-simplebar="true" di wrapper)
  (function () {
    var sb = document.querySelector('.sidebar-wrapper[data-simplebar="true"]');
    if (sb && typeof SimpleBar !== 'undefined') {
      // instantiate sekali saja
      if (!sb.SimpleBar) new SimpleBar(sb);
    }
  })();

  // PerfectScrollbar (optional; kalau pakai SimpleBar, ini tidak wajib)
  // new PerfectScrollbar('.sidebar-wrapper');

  // MetisMenu init (penting agar bullet & nested menu jadi gaya metis)
  (function () {
    var menu = document.getElementById('menu');
    if (menu) {
      // jQuery plugin:
      if (window.jQuery && typeof jQuery(menu).metisMenu === 'function') {
        jQuery(menu).metisMenu();
      }
      // kalau kamu pakai versi vanilla (metismenujs), gunakan:
      // new MetisMenu(menu);
    }
  })();
</script>
