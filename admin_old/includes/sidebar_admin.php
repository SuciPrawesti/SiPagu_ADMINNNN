<!-- Sidebar -->
<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">

        <!-- Brand Logo Full -->
        <div class="sidebar-brand">
            <a href="<?= BASE_URL ?>admin/index.php">
                <img
                    src="<?= ASSETS_URL ?>/img/logoSiPagu.png"
                    alt="Logo SiPagu"
                    style="max-height: 40px; max-width: 150px; object-fit: contain;"
                >
            </a>
        </div>

        <!-- Brand Logo Small (Mini Sidebar) -->
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="<?= BASE_URL ?>admin/index.php">
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
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                <a href="<?= BASE_URL ?>admin/index.php" class="nav-link">
                    <i class="fas fa-fire"></i><span>Dashboard</span>
                </a>
            </li>

            <!-- ================= UPLOADS ================= -->
            <li class="menu-header">Uploads Excel</li>
            <?php 
            $upload_pages = ['upload_user', 'upload_panitia', 'upload_tu', 'upload_tpata', 'upload_thd', 'upload_jadwal', 'upload_honor_dosen'];
            $is_upload_page = in_array(basename($_SERVER['PHP_SELF'], '.php'), $upload_pages);
            ?>
            <li class="dropdown <?= $is_upload_page ? 'active' : ''; ?>">
                <a href="#" class="nav-link has-dropdown">
                    <i class="fas fa-file-upload"></i><span>Upload Data</span>
                </a>
                <ul class="dropdown-menu">
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'upload_user.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/upload_data/upload_user.php">Upload Data User</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'upload_panitia.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/upload_data/upload_panitia.php">Upload Data Panitia</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'upload_tu.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/upload_data/upload_tu.php">Upload Transaksi Ujian</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'upload_tpata.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/upload_data/upload_tpata.php">Upload Panitia PA/TA</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'upload_jadwal.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/upload_data/upload_jadwal.php">Upload Jadwal</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'upload_honor_dosen.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/upload_data/upload_honor_dosen.php">Upload Honor Dosen</a>
                    </li>
                </ul>
            </li>

            <!-- ================= MASTER DATA ================= -->
            <li class="menu-header">Master Data</li>
            <li class="dropdown <?= in_array(basename($_SERVER['PHP_SELF']), 
                ['data_admin.php', 'data_jadwal.php', 'data_koordinator.php', 'data_panitia.php', 
                 'data_staff.php', 'data_thd.php', 'data_tpata.php', 'data_tu.php']) ? 'active' : ''; ?>">
                <a href="#" class="nav-link has-dropdown">
                    <i class="fas fa-database"></i><span>Master Data</span>
                </a>
                <ul class="dropdown-menu">
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'data_admin.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/master_data/data_admin.php">Data Admin</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'data_staff.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/master_data/data_staff.php">Data Staff</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'data_koordinator.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/master_data/data_koordinator.php">Data Koordinator</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'data_jadwal.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/master_data/data_jadwal.php">Data Jadwal</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'data_panitia.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/master_data/data_panitia.php">Data Panitia</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'data_thd.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/master_data/data_thd.php">Data Honor Dosen</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'data_tpata.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/master_data/data_tpata.php">Data PA/TA</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'data_tu.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/master_data/data_tu.php">Data Transaksi Ujian</a>
                    </li>
                </ul>
            </li>

        </ul>
        
        <!-- ================= FOOTER SIDEBAR ================= -->
        <div class="sidebar-footer mt-4 mb-4 p-3" style="margin-top: auto !important;">
            <!-- Logout Button with Proper Alignment -->
            <button type="button" onclick="showLogoutModal()" class="btn btn-danger btn-lg btn-block btn-icon-split d-flex align-items-center justify-content-center">
                <i class="fas fa-sign-out-alt mr-2"></i>
                <span class="flex-grow-1 text-center">Logout</span>
            </button>
        </div>
    </aside>
</div>
<!-- End Sidebar -->

<style>
/* ========== LOGOUT MODAL STYLES ========== */
.logout-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.logout-modal-overlay.active {
    display: flex;
    opacity: 1;
}

.logout-modal {
    background: var(--white);
    border-radius: 20px;
    box-shadow: var(--shadow-xl);
    width: 90%;
    max-width: 400px;
    overflow: hidden;
    transform: translateY(-20px);
    transition: transform 0.3s ease;
    border: 1px solid var(--border);
}

.logout-modal-overlay.active .logout-modal {
    transform: translateY(0);
}

.logout-modal-header {
    padding: 2rem 2rem 1rem;
    text-align: center;
    background: linear-gradient(135deg, rgba(0, 61, 122, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
}

.logout-modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.5rem;
}

.logout-modal-subtitle {
    color: var(--accent);
    font-size: 0.95rem;
    line-height: 1.5;
}

.logout-modal-body {
    padding: 2rem;
    text-align: center;
}

.logout-modal-message {
    font-size: 1rem;
    color: var(--primary-dark);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.logout-modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.logout-modal-btn {
    padding: 0.75rem 2rem;
    border-radius: 999px;
    font-weight: 500;
    font-size: 0.9375rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.logout-modal-btn i {
    margin-right: 8px;
    font-size: 0.9rem;
}

.logout-modal-btn-cancel {
    background: linear-gradient(135deg, var(--light-gray) 0%, #f0f9ff 100%);
    color: var(--primary);
    border: 1px solid var(--border);
}

.logout-modal-btn-cancel:hover {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.logout-modal-btn-confirm {
    background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
    color: var(--white);
}

.logout-modal-btn-confirm:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* ========== RESPONSIVE STYLES ========== */
@media (max-width: 768px) {
    .logout-modal {
        width: 95%;
        margin: 1rem;
    }
    
    .logout-modal-header {
        padding: 1.5rem 1.5rem 1rem;
    }
    
    .logout-modal-title {
        font-size: 1.35rem;
    }
    
    .logout-modal-body {
        padding: 1.75rem;
    }
    
    .logout-modal-actions {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .logout-modal-btn {
        width: 100%;
        min-width: auto;
        padding: 0.75rem 1.5rem;
    }
}

@media (max-width: 576px) {
    .logout-modal {
        width: calc(100% - 30px);
        margin: 0 15px;
    }
    
    .logout-modal-header {
        padding: 1.25rem 1.25rem 0.75rem;
    }
    
    .logout-modal-title {
        font-size: 1.25rem;
    }
    
    .logout-modal-body {
        padding: 1.5rem;
    }
    
    .logout-modal-message {
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 480px) {
    .logout-modal-header {
        padding: 1rem 1rem 0.5rem;
    }
    
    .logout-modal-title {
        font-size: 1.15rem;
        margin-bottom: 0.25rem;
    }
    
    .logout-modal-subtitle {
        font-size: 0.85rem;
    }
    
    .logout-modal-body {
        padding: 1.25rem;
    }
    
    .logout-modal-message {
        font-size: 0.9rem;
        margin-bottom: 1.25rem;
    }
}

/* ========== SIDEBAR FOOTER STYLES ========== */
/* Footer positioning */
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

/* Ensure dropdowns appear above footer */
.dropdown-menu {
    z-index: 1001 !important;
    position: relative;
}

/* Button styles */
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

.btn-icon-split i {
    font-size: 1rem;
    width: 20px;
    text-align: center;
}

/* Adjust for sidebar mini mode */
body.sidebar-mini .sidebar-footer {
    width: 65px;
    padding: 10px !important;
}

body.sidebar-mini .btn-icon-split {
    padding: 8px 10px;
}

body.sidebar-mini .btn-icon-split span {
    display: none;
}

body.sidebar-mini .btn-icon-split i {
    margin-right: 0;
    font-size: 18px;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .sidebar-menu {
        max-height: 60vh;
    }
    
    .sidebar-footer {
        position: relative;
        margin-top: 20px;
        border-top: 1px solid #e0e0e0;
    }
}
</style>

<!-- Notifikasi Logout Modal -->
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

<script>
// Fungsi untuk menampilkan modal logout dari sidebar
function showLogoutModal() {
    const modal = document.getElementById('logoutModalSidebar');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Fungsi untuk menyembunyikan modal logout sidebar
function hideLogoutModalSidebar() {
    const modal = document.getElementById('logoutModalSidebar');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Tutup modal saat klik di luar area modal
document.getElementById('logoutModalSidebar').addEventListener('click', function(e) {
    if (e.target === this) {
        hideLogoutModalSidebar();
    }
});

// Tutup modal dengan tombol ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Cek modal mana yang aktif
        const sidebarModal = document.getElementById('logoutModalSidebar');
        const navbarModal = document.getElementById('logoutModal');
        
        if (sidebarModal && sidebarModal.classList.contains('active')) {
            hideLogoutModalSidebar();
        }
        if (navbarModal && navbarModal.classList.contains('active')) {
            hideLogoutModal(); // Fungsi dari navbar.php
        }
    }
});

// JavaScript untuk mengatur footer dinamis
document.addEventListener('DOMContentLoaded', function() {
    const sidebarMenu = document.querySelector('.sidebar-menu');
    const sidebarFooter = document.querySelector('.sidebar-footer');
    const dropdownToggles = document.querySelectorAll('.nav-link.has-dropdown');
    
    if (!sidebarMenu || !sidebarFooter) return;
    
    // Fungsi untuk menyesuaikan tinggi menu
    function adjustMenuHeight() {
        const windowHeight = window.innerHeight;
        const footerHeight = sidebarFooter.offsetHeight;
        const headerHeight = 120;
        
        // Hitung tinggi maksimal untuk menu
        const maxMenuHeight = windowHeight - footerHeight - headerHeight;
        
        // Set tinggi maksimal untuk menu
        sidebarMenu.style.maxHeight = Math.max(maxMenuHeight, 200) + 'px';
        
        // Cek apakah menu membutuhkan scroll
        const menuScrollHeight = sidebarMenu.scrollHeight;
        const menuClientHeight = sidebarMenu.clientHeight;
        
        if (menuScrollHeight > menuClientHeight) {
            sidebarMenu.style.overflowY = 'auto';
        } else {
            sidebarMenu.style.overflowY = 'visible';
        }
    }
    
    // Fungsi untuk mengatur posisi footer saat dropdown dibuka
    function setupDropdownListeners() {
        dropdownToggles.forEach(toggle => {
            const originalOnClick = toggle.onclick;
            
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (typeof originalOnClick === 'function') {
                    originalOnClick.call(this, e);
                }
                
                setTimeout(() => {
                    adjustMenuHeight();
                    
                    const parentLi = this.closest('li');
                    if (parentLi && parentLi.classList.contains('show')) {
                        const dropdownMenu = parentLi.querySelector('.dropdown-menu');
                        if (dropdownMenu && dropdownMenu.style.display === 'block') {
                            const rect = parentLi.getBoundingClientRect();
                            const footerRect = sidebarFooter.getBoundingClientRect();
                            
                            if (rect.bottom + dropdownMenu.offsetHeight > footerRect.top - 10) {
                                sidebarMenu.scrollTop += dropdownMenu.offsetHeight + 10;
                            }
                        }
                    }
                }, 350);
            });
        });
    }
    
    // Inisialisasi
    adjustMenuHeight();
    setupDropdownListeners();
    
    // Adjust saat window di-resize
    window.addEventListener('resize', adjustMenuHeight);
    
    // Adjust saat sidebar di-toggle (untuk sidebar mini)
    const sidebarToggle = document.querySelector('[data-toggle="sidebar"]');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            setTimeout(adjustMenuHeight, 500);
        });
    }
    
    // Handle klik di luar dropdown untuk menutup
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            setTimeout(adjustMenuHeight, 100);
        }
    });
    
    // Mutation observer untuk mendeteksi perubahan pada dropdown
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                setTimeout(adjustMenuHeight, 100);
            }
        });
    });
    
    // Observasi semua dropdown items
    dropdownToggles.forEach(toggle => {
        const parentLi = toggle.closest('li');
        if (parentLi) {
            observer.observe(parentLi, {
                attributes: true,
                attributeFilter: ['class']
            });
        }
    });
});
</script>