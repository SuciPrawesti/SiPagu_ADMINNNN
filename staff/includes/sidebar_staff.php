<!-- Sidebar -->
<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">

        <!-- Brand Logo Full -->
        <div class="sidebar-brand">
            <a href="<?= BASE_URL ?>staff/index.php">
                <img
                    src="<?= ASSETS_URL ?>/img/logoSiPagu.png"
                    alt="Logo SiPagu"
                    style="max-height: 40px; max-width: 150px; object-fit: contain;"
                >
            </a>
        </div>

        <!-- Brand Logo Small (Mini Sidebar) -->
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="<?= BASE_URL ?>staff/index.php">
                <img
                    src="<?= ASSETS_URL ?>/img/logoSiPagu.png"
                    alt="Logo SiPagu"
                    style="max-height: 30px; max-width: 40px; object-fit: contain;"
                >
            </a>
        </div>

        <!-- Menu Items -->
        <ul class="sidebar-menu">
            
            <!-- ================= DASHBOARD ================= -->
            <li class="menu-header">Dashboard</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], 'staff') !== false ? 'active' : ''; ?>">
                <a href="<?= BASE_URL ?>staff/index.php" class="nav-link">
                    <i class="fas fa-fire"></i><span>Dashboard</span>
                </a>
            </li>

            <!-- ================= JADWAL ================= -->
            <li class="menu-header">Jadwal</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'jadwal_saya.php' ? 'active' : ''; ?>">
                <a href="<?= BASE_URL ?>staff/jadwal/jadwal_saya.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i><span>Jadwal Saya</span>
                </a>
            </li>

            <!-- ================= HONOR ================= -->
            <li class="menu-header">Honor</li>
            <?php 
            $honor_pages = ['honor_saya.php', 'input_honor.php'];
            $is_honor_page = in_array(basename($_SERVER['PHP_SELF']), $honor_pages);
            ?>
            <li class="dropdown <?= $is_honor_page ? 'active' : ''; ?>">
                <a href="#" class="nav-link has-dropdown">
                    <i class="fas fa-money-bill-wave"></i><span>Honor Mengajar</span>
                </a>
                <ul class="dropdown-menu">
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'honor_saya.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>staff/honor/honor_saya.php">Riwayat Honor</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'input_honor.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>staff/honor/input_honor.php">Input Honor</a>
                    </li>
                </ul>
            </li>

            <!-- ================= PA/TA ================= -->
            <li class="menu-header">PA / TA</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'pa_ta_saya.php' ? 'active' : ''; ?>">
                <a href="<?= BASE_URL ?>staff/pa_ta/pa_ta_saya.php" class="nav-link">
                    <i class="fas fa-user-tie"></i><span>Data PA / TA</span>
                </a>
            </li>

            <!-- ================= PROFILE ================= -->
            <li class="menu-header">Akun</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], 'profile') !== false ? 'active' : ''; ?>">
                <a href="<?= BASE_URL ?>staff/profile/index.php" class="nav-link">
                    <i class="far fa-user"></i><span>Profile</span>
                </a>
            </li>

        </ul>
        
        <!-- ================= FOOTER SIDEBAR ================= -->
        <div class="sidebar-footer mt-4 mb-4 p-3" style="margin-top: auto !important;">
            <button type="button" onclick="showLogoutModal()" class="btn btn-danger btn-lg btn-block btn-icon-split d-flex align-items-center justify-content-center">
                <i class="fas fa-sign-out-alt mr-2"></i>
                <span class="flex-grow-1 text-center">Logout</span>
            </button>
        </div>
    </aside>
</div>
<!-- End Sidebar -->

<!-- Logout Modal Sidebar -->
<div class="logout-modal-overlay" id="logoutModalSidebar">
    <div class="logout-modal">
        <div class="logout-modal-header">
            <h3 class="logout-modal-title">Logout Confirmation</h3>
            <p class="logout-modal-subtitle">Confirm your action</p>
        </div>
        <div class="logout-modal-body">
            <p class="logout-modal-message">
                Apakah Anda yakin ingin logout dari sistem? 
                Anda akan dialihkan ke halaman login.
            </p>
            <div class="logout-modal-actions">
                <button class="logout-modal-btn logout-modal-btn-cancel" onclick="hideLogoutModalSidebar()">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <a href="<?= BASE_URL ?>logout.php" class="logout-modal-btn logout-modal-btn-confirm">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.sidebar-footer {
    flex-shrink: 0;
    position: sticky;
    bottom: 0;
    background: #fff;
    z-index: 10;
    padding: 12px 15px !important;
    margin-top: auto;
    border-top: 1px solid #f0f0f0;
}

.btn-icon-split {
    padding: 10px 15px;
    border-radius: 6px;
    transition: all 0.3s ease;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    width: 100%;
}

.btn-icon-split:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

body.sidebar-mini .sidebar-footer { width: 65px; padding: 10px !important; }
body.sidebar-mini .btn-icon-split span { display: none; }
body.sidebar-mini .btn-icon-split i { margin-right: 0; font-size: 18px; }

@media (max-width: 768px) {
    .sidebar-menu { max-height: 60vh; }
    .sidebar-footer { position: relative; margin-top: 20px; border-top: 1px solid #e0e0e0; }
}
</style>

<script>
function showLogoutModal() {
    const modal = document.getElementById('logoutModalSidebar');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function hideLogoutModalSidebar() {
    const modal = document.getElementById('logoutModalSidebar');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

document.getElementById('logoutModalSidebar').addEventListener('click', function(e) {
    if (e.target === this) { hideLogoutModalSidebar(); }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebarModal = document.getElementById('logoutModalSidebar');
        const navbarModal = document.getElementById('logoutModal');
        if (sidebarModal && sidebarModal.classList.contains('active')) { hideLogoutModalSidebar(); }
        if (navbarModal && navbarModal.classList.contains('active')) { hideLogoutModal(); }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const sidebarMenu = document.querySelector('.sidebar-menu');
    const sidebarFooter = document.querySelector('.sidebar-footer');

    function adjustMenuHeight() {
        if (!sidebarMenu || !sidebarFooter) return;
        const windowHeight = window.innerHeight;
        const footerHeight = sidebarFooter.offsetHeight;
        const headerHeight = 120;
        const maxMenuHeight = windowHeight - footerHeight - headerHeight;
        sidebarMenu.style.maxHeight = Math.max(maxMenuHeight, 200) + 'px';
        sidebarMenu.style.overflowY = sidebarMenu.scrollHeight > sidebarMenu.clientHeight ? 'auto' : 'visible';
    }

    adjustMenuHeight();
    window.addEventListener('resize', adjustMenuHeight);
});
</script>