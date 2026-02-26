<?php
// =====================================================
// KOORDINATOR DASHBOARD - SiPagu Universitas Dian Nuswantoro
// =====================================================
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config.php';

// Query untuk statistik (menggunakan database yang ada)
$query_user = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_user WHERE role_user != 'admin'");
$row_user = mysqli_fetch_assoc($query_user);
$total_dosen = $row_user['total'];

$query_transaksi = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_ujian");
$row_transaksi = mysqli_fetch_assoc($query_transaksi);
$total_transaksi = $row_transaksi['total'];

$query_honor = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_honor_dosen");
$row_honor = mysqli_fetch_assoc($query_honor);
$total_honor = $row_honor['total'];

$query_pa_ta = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_pa_ta");
$row_pa_ta = mysqli_fetch_assoc($query_pa_ta);
$total_pa_ta = $row_pa_ta['total'];

// Query untuk honor yang perlu validasi (jika ada kolom status)
// CATATAN: Database tidak memiliki kolom status di tabel honor, jadi tampilkan total saja
$query_pending = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_honor_dosen");
$row_pending = mysqli_fetch_assoc($query_pending);
$pending_honor = $row_pending['total'];
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<?php include __DIR__ . '/includes/sidebar_koordinator.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <!-- Header Minimalist -->
        <div class="section-header pt-4 pb-0">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <h1 class="h3 font-weight-normal text-dark mb-1">Dashboard Koordinator</h1>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Koordinator'); ?></p>
                </div>
                <div class="text-muted">
                    <?php echo date('l, d F Y'); ?>
                </div>
            </div>
        </div>

        <div class="section-body">
            <!-- Stats Grid - Simple -->
            <div class="row mb-5">
                <!-- Stat Card 1: Total Dosen -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-icon bg-primary-soft text-primary">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $total_dosen; ?></h3>
                            <p class="stat-label">Total Dosen</p>
                        </div>
                    </div>
                </div>

                <!-- Stat Card 2: Transaksi Ujian -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-success">
                        <div class="stat-icon bg-success-soft text-success">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $total_transaksi; ?></h3>
                            <p class="stat-label">Transaksi Ujian</p>
                        </div>
                    </div>
                </div>

                <!-- Stat Card 3: Honor Dosen -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-icon bg-warning-soft text-warning">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $total_honor; ?></h3>
                            <p class="stat-label">Honor Dosen</p>
                        </div>
                    </div>
                </div>

                <!-- Stat Card 4: PA/TA -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-info">
                        <div class="stat-icon bg-info-soft text-info">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $total_pa_ta; ?></h3>
                            <p class="stat-label">PA/TA</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions - Grid Style -->
            <div class="row">
                <div class="col-12 mb-5">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h4 class="h5 font-weight-normal text-dark mb-0">Quick Access</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>koordinator/honor/honor_dosen.php" 
                               class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-money-check-alt fa-2x text-primary"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark">Honor Dosen</h6>
                            </a>
                        </div>
                        
                        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>koordinator/honor/honor_panitia.php" 
                               class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-users fa-2x text-success"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark">Honor Panitia</h6>
                            </a>
                        </div>
                        
                        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>koordinator/rekap/rekap_bulanan.php" 
                               class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-chart-bar fa-2x text-warning"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark">Rekap Bulanan</h6>
                            </a>
                        </div>
                        
                        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>koordinator/jadwal/jadwal_ujian.php" 
                               class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-calendar-alt fa-2x text-info"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark">Jadwal Ujian</h6>
                            </a>
                        </div>
                        
                        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>koordinator/laporan/laporan_honor.php" 
                               class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark">Laporan Honor</h6>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Data & System Info - Updated Style -->
            <div class="row">
                <!-- Data Honor Terbaru Card -->
                <div class="col-lg-8 mb-4">
                    <div class="content-card content-card-primary">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">Data Honor Terbaru</h5>
                            <a href="<?= BASE_URL ?>koordinator/honor/honor_dosen.php" class="text-primary small">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Semester</th>
                                            <th>Bulan</th>
                                            <th>Mata Kuliah</th>
                                            <th>TM</th>
                                            <th>SKS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = mysqli_query($koneksi, "
                                            SELECT thd.semester, thd.bulan, j.nama_matkul, thd.jml_tm, thd.sks_tempuh
                                            FROM t_transaksi_honor_dosen thd
                                            JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
                                            ORDER BY thd.id_thd DESC 
                                            LIMIT 5
                                        ");
                                        while ($row = mysqli_fetch_assoc($query)): ?>
                                        <tr>
                                            <td><span class="badge badge-light"><?= $row['semester'] ?></span></td>
                                            <td><?= ucfirst($row['bulan']) ?></td>
                                            <td class="font-weight-medium"><?= htmlspecialchars($row['nama_matkul']) ?></td>
                                            <td><span class="badge badge-primary"><?= $row['jml_tm'] ?></span></td>
                                            <td><span class="badge badge-success"><?= $row['sks_tempuh'] ?></span></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Info Card -->
                <div class="col-lg-4 mb-4">
                    <div class="content-card content-card-info">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">Informasi Sistem</h5>
                            <i class="fas fa-info-circle text-info"></i>
                        </div>
                        <div class="card-body">
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Role</span>
                                    <span class="font-weight-medium text-primary">Koordinator</span>
                                </div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Semester Aktif</span>
                                    <span class="font-weight-medium">20241</span>
                                </div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Total Data Honor</span>
                                    <span class="font-weight-medium"><?= $total_honor ?></span>
                                </div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Status Sistem</span>
                                    <span class="badge badge-success">Aktif</span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Last Updated</span>
                                    <span class="font-weight-medium"><?= date('d M Y') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
<!-- End Main Content -->

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php include __DIR__ . '/includes/footer_scripts.php'; ?>

<style>
/* Minimalist Variables */
:root {
    --primary-soft: rgba(102, 126, 234, 0.08);
    --success-soft: rgba(40, 199, 111, 0.08);
    --warning-soft: rgba(255, 159, 67, 0.08);
    --info-soft: rgba(0, 207, 232, 0.08);
    --danger-soft: rgba(234, 84, 85, 0.08);
    --secondary-soft: rgba(108, 117, 125, 0.08);
    
    --card-bg: #ffffff;
    --border-color: #eef2f7;
    --text-primary: #2d3748;
    --text-secondary: #718096;
}

/* Simple Card */
.card-simple {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    transition: all 0.2s ease;
}

.card-simple:hover {
    box-shadow: 0 4px 6px rgba(0,0,0,0.04);
}

.card-title {
    font-size: 1rem;
    font-weight: 500;
    color: var(--text-primary);
}

/* Stat Cards Base */
.stat-card {
    display: flex;
    align-items: center;
    padding: 20px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 15px;
    transition: all 0.3s ease;
}

/* Stat Card Primary (Dosen) */
.stat-card-primary {
    position: relative;
    box-shadow: -8px 7px 12px rgb(102 126 234 / 40%);
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.stat-card-primary:hover {
    transform: translate(12px, -14px);
    box-shadow: -20px 18px 32px rgb(102 126 234 / 45%);
    border-color: #667eea;
}

/* Stat Card Success (Transaksi) */
.stat-card-success {
    box-shadow: -8px 7px 12px rgb(40 199 111 / 40%);
    border: 1px solid rgba(40, 199, 111, 0.1);
}

.stat-card-success:hover {
    transform: translate(12px, -14px);
    box-shadow: -20px 18px 32px rgba(40, 199, 111, 0.2);
    border-color: #28c76f;
}

/* Stat Card Warning (Honor) */
.stat-card-warning {
    box-shadow: -8px 7px 12px rgb(255 159 67 / 40%);
    border: 1px solid rgba(255, 159, 67, 0.1);
}

.stat-card-warning:hover {
    transform: translate(12px, -14px);
    box-shadow: -20px 18px 32px rgba(255, 159, 67, 0.2);
    border-color: #ff9f43;
}

/* Stat Card Info (PA/TA) */
.stat-card-info {
    box-shadow: -8px 7px 12px rgb(0 207 232 / 40%);
    border: 1px solid rgba(0, 207, 232, 0.1);
}

.stat-card-info:hover {
    transform: translate(12px, -14px);
    box-shadow: -20px 18px 32px rgba(0, 207, 232, 0.2);
    border-color: #00cfe8;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
    font-size: 1.25rem;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin: 4px 0 0 0;
}

/* Content Cards (Updated) */
.content-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 15px;
    transition: all 0.3s ease;
    height: 100%;
}

.content-card-primary {
    position: relative;
    box-shadow: -8px 7px 12px rgb(102 126 234 / 30%);
    border: 1px solid rgba(102, 126, 234, 0.08);
}

.content-card-primary:hover {
    transform: translate(6px, -7px);
    box-shadow: -12px 10px 20px rgb(102 126 234 / 35%);
    border-color: rgba(102, 126, 234, 0.2);
}

.content-card-info {
    position: relative;
    box-shadow: -8px 7px 12px rgb(0 207 232 / 30%);
    border: 1px solid rgba(0, 207, 232, 0.08);
}

.content-card-info:hover {
    transform: translate(6px, -7px);
    box-shadow: -12px 10px 20px rgba(0, 207, 232, 0.35);
    border-color: rgba(0, 207, 232, 0.2);
}

.card-header-simple {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.25rem 0.75rem;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 0;
}

.card-body {
    padding: 1.25rem;
}

/* Table inside content card */
.content-card .table {
    margin-bottom: 0;
}

.content-card .table thead th {
    border-top: none;
    background-color: rgba(0, 0, 0, 0.02);
    font-weight: 500;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    border-bottom: 1px solid var(--border-color);
}

.content-card .table tbody td {
    vertical-align: middle;
    border-top: 1px solid var(--border-color);
}

.content-card .table-hover tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.03);
}

/* Badge styles */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
}

.badge-light {
    background-color: #f8f9fa;
    color: #495057;
}

/* Action Cards */
.action-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    box-shadow: -8px 7px 12px rgb(0 0 0 / 11%);
    border-radius: 8px;
    transition: all 0.2s ease;
    text-decoration: none;
}

.action-card:hover {
    transform: translate(12px, -14px);
    box-shadow: -20px 18px 32px rgba(0, 0, 0, 0.29);
    border-color: #667eea;
    text-decoration: none;
}

.action-icon .icon-wrapper {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--border-color);
    transition: all 0.2s ease;
}

.action-card:hover .icon-wrapper {
    background: var(--primary-soft);
    transform: scale(1.1);
}

.action-title {
    font-size: 0.875rem;
    font-weight: 500;
    transition: color 0.2s ease;
}

.action-card:hover .action-title {
    color: #667eea !important;
}

/* Table Styles */
.table-sm th,
.table-sm td {
    padding: 0.5rem;
    font-size: 0.875rem;
}

/* Info Item */
.info-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.info-item:last-child {
    border-bottom: none;
}

/* Header Styles */
.section-header {
    border-bottom: 1px solid var(--border-color);
}

/* Soft Backgrounds */
.bg-primary-soft { background-color: var(--primary-soft) !important; }
.bg-success-soft { background-color: var(--success-soft) !important; }
.bg-warning-soft { background-color: var(--warning-soft) !important; }
.bg-info-soft { background-color: var(--info-soft) !important; }
.bg-danger-soft { background-color: var(--danger-soft) !important; }
.bg-secondary-soft { background-color: var(--secondary-soft) !important; }

/* Responsive */
@media (max-width: 768px) {
    .stat-card {
        padding: 16px;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
        margin-right: 12px;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .action-card {
        padding: 20px 12px;
    }
    
    .action-icon .icon-wrapper {
        width: 48px;
        height: 48px;
    }
    
    .action-icon i {
        font-size: 1.5rem;
    }
    
    /* Adjust hover effects on mobile */
    .stat-card-primary:hover,
    .stat-card-success:hover,
    .stat-card-warning:hover,
    .stat-card-info:hover,
    .content-card-primary:hover,
    .content-card-info:hover {
        transform: translateY(-2px);
    }
    
    /* Adjust shadow size on mobile */
    .stat-card-primary,
    .stat-card-success,
    .stat-card-warning,
    .stat-card-info,
    .content-card-primary,
    .content-card-info {
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
    }
}

/* Smooth Transitions */
* {
    transition: background-color 0.2s ease, border-color 0.2s ease;
}

/* Section Spacing */
.section-body {
    padding-top: 0;
}

.mb-5 {
    margin-bottom: 3rem !important;
}

.mb-4 {
    margin-bottom: 1.5rem !important;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

.mb-2 {
    margin-bottom: 0.5rem !important;
}

.mb-1 {
    margin-bottom: 0.25rem !important;
}

/* Text Styles */
.h3 {
    font-size: 1.75rem;
    font-weight: 400;
}

.h5 {
    font-size: 1.125rem;
    font-weight: 400;
}

.font-weight-normal {
    font-weight: 400 !important;
}

.font-weight-medium {
    font-weight: 500 !important;
}

.font-weight-semibold {
    font-weight: 600 !important;
}
</style>

<script>
$(document).ready(function() {
    // Simple hover effect for action cards
    $('.action-card').on('mouseenter', function() {
        $(this).addClass('active');
    }).on('mouseleave', function() {
        $(this).removeClass('active');
    });
    
    // Simple loading animation for stat cards
    $('.stat-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(10px)'
        });
        
        setTimeout(() => {
            $(this).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 300);
        }, index * 100);
    });
    
    // Loading animation for content cards
    $('.content-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(8px)'
        });
        
        setTimeout(() => {
            $(this).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 400);
        }, 300 + (index * 150));
    });
    
    // Table row hover effect
    $('.table-hover tbody tr').on('mouseenter', function() {
        $(this).css('transform', 'translateX(2px)');
    }).on('mouseleave', function() {
        $(this).css('transform', 'translateX(0)');
    });
});
</script>