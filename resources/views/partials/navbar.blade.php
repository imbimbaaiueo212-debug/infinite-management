<nav class="sb-topnav navbar navbar-expand navbar-light bg-light fixed-top">
    <!-- Hamburger Button (hanya muncul di mobile) -->


    <!-- Logo – Tengah di mobile, kiri di desktop -->
    <a class="navbar-brand mx-auto mx-lg-0 px-2 px-lg-3 order-2 order-lg-1" href="{{ route('unit.index') }}">
        <img src="{{ asset('template/img/finaly.png') }}"
             alt="Infinite Management"
             class="d-block"
             height="40">
    </a>

    <!-- Spacer untuk dorong user ke kanan di mobile -->
    <div class="d-lg-none flex-grow-1 order-3"></div>

    <!-- User Dropdown (selalu di kanan) -->
    <ul class="navbar-nav ms-auto me-2 me-lg-4 order-4">
        @auth
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center py-1 text-black"
   id="navbarDropdown"
   href="#"
   role="button"
   data-bs-toggle="dropdown"
   aria-expanded="false">
    @if (Auth::user()->photo)
        <img src="{{ asset('public/storage/' . Auth::user()->photo) }}"
             class="rounded-circle border border-2 border-white me-2"
             width="34" height="34" alt="Profile">
    @else
         <img src="{{ asset('public/template/img/user.png') }}"
             class="rounded-circle border border-2 border-white me-2"
             width="34" height="34" alt="Profile">
    @endif
    <span class="d-none d-lg-block fw-medium">{{ auth()->user()->name }}</span>
</a>
                <ul class="dropdown-menu dropdown-menu-end shadow mt-2">
                    <li><a class="dropdown-item" href="{{ route('users.show', Auth::user()->id) }}"><i class="fas fa-id-card me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </li>
                <button class="btn btn-link text-white d-lg-none p-2 order-1" id="sidebarToggle" type="button" aria-label="Toggle Sidebar">
        <i class="fas fa-bars fs-4"></i>
    </button>
        @endauth
    </ul>
</nav>


<!-- SCRIPT SIDEBAR MOBILE TOGGLE — VERSI FINAL & PASTI WORK -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar   = document.querySelector('.sb-sidenav');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (!sidebar || !toggleBtn) return;

    // Buat overlay gelap
    let overlay = document.getElementById('sidebarOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sidebarOverlay';
        document.body.appendChild(overlay);
    }

    const openSidebar = () => {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.classList.add('sidebar-open');
    };

    const closeSidebar = () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.classList.remove('sidebar-open');
    };

    // Klik hamburger → buka atau tutup
    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation(); // penting agar tidak trigger overlay
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    // KLIK OVERLAY → TUTUP SIDEBAR (klik di mana saja di luar sidebar)
    overlay.addEventListener('click', closeSidebar);

    // Klik link menu di sidebar → tutup otomatis di mobile (UX terbaik)
    sidebar.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });

    // Tekan ESC → tutup
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
});
</script>