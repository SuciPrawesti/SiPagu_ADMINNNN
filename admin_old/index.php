<?php
// =====================================================
// ADMIN DASHBOARD - SiPagu Universitas Dian Nuswantoro
// =====================================================
require_once __DIR__ . '/../config.php';

// Query untuk statistik
$query_user = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_user");
$row_user = mysqli_fetch_assoc($query_user);
$total_user = $row_user['total'];

$query_transaksi = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_ujian");
$row_transaksi = mysqli_fetch_assoc($query_transaksi);
$total_transaksi = $row_transaksi['total'];

$query_panitia = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_panitia");
$row_panitia = mysqli_fetch_assoc($query_panitia);
$total_panitia = $row_panitia['total'];

// Data untuk quick actions
$quick_actions = [
    ['title' => 'Upload User', 'url' => 'upload_user.php', 'icon' => 'fa-users', 'color' => 'primary'],
    ['title' => 'Transaksi Ujian', 'url' => 'upload_tu.php', 'icon' => 'fa-file-invoice-dollar', 'color' => 'success'],
    ['title' => 'Panitia PA/TA', 'url' => 'upload_tpata.php', 'icon' => 'fa-user-tie', 'color' => 'warning'],
    ['title' => 'Data Panitia', 'url' => 'upload_panitia.php', 'icon' => 'fa-clipboard-list', 'color' => 'danger'],
    ['title' => 'Jadwal', 'url' => 'upload_thd.php', 'icon' => 'fa-calendar-alt', 'color' => 'info'],
    ['title' => 'Jadwal Lain', 'url' => 'upload_jadwal.php', 'icon' => 'fa-clock', 'color' => 'secondary'],
];
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<?php include __DIR__ . '/includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <!-- Header Minimalist -->
        <div class="section-header pt-4 pb-0">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <h1 class="h3 font-weight-normal text-dark mb-1">Dashboard Admin</h1>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
                </div>
                <div class="text-muted">
                    <?php echo date('l, d F Y'); ?>
                </div>
            </div>
        </div>

        <div class="section-body">
            <!-- Stats Grid - Simple -->
            <div class="row mb-5">
                <!-- Stat Card 1: Total User -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-icon bg-primary-soft text-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $total_user; ?></h3>
                            <p class="stat-label">Total User</p>
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

                <!-- Stat Card 3: Panitia PA/TA -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-icon bg-warning-soft text-warning">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $total_panitia; ?></h3>
                            <p class="stat-label">Panitia PA/TA</p>
                        </div>
                    </div>
                </div>

                <!-- Stat Card 4: Semester Aktif -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-info">
                        <div class="stat-icon bg-info-soft text-info">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">20241</h3>
                            <p class="stat-label">Semester Aktif</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions - Grid Style -->
            <div class="row">
                <div class="col-12 mb-5">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h4 class="h5 font-weight-normal text-dark mb-0">Quick Actions</h4>
                        <a href="#" class="text-muted small">View all <i class="fas fa-chevron-right ml-1"></i></a>
                    </div>
                    
                    <div class="row">
                        <?php foreach ($quick_actions as $action): ?>
                        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>admin/<?php echo $action['url']; ?>" 
                               class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas <?php echo $action['icon']; ?> fa-2x text-<?php echo $action['color']; ?>"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark"><?php echo $action['title']; ?></h6>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Data & System Info - Updated Style -->
            <div class="row">
                <!-- Recent Activity Card -->
                <div class="col-lg-8 mb-4">
                    <div class="content-card content-card-primary">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">Recent Activity</h5>
                            <a href="#" class="text-primary small">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="activity-list">
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-upload text-primary"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="mb-1">Upload Data User - 25 data baru</p>
                                        <small class="text-muted">2 hours ago</small>
                                    </div>
                                </div>
                                
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-file-invoice-dollar text-success"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="mb-1">Update Transaksi Ujian Semester 20241</p>
                                        <small class="text-muted">1 day ago</small>
                                    </div>
                                </div>
                                
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-calendar-alt text-info"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="mb-1">Import Jadwal dari jadwal_20241.xlsx</p>
                                        <small class="text-muted">3 days ago</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Info Card -->
                <div class="col-lg-4 mb-4">
                    <div class="content-card content-card-info">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">System Status</h5>
                            <i class="fas fa-server text-info"></i>
                        </div>
                        <div class="card-body">
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Database</span>
                                    <span class="badge badge-success">Normal</span>
                                </div>
                                <div class="progress-bar-thin">
                                    <div class="progress-fill bg-success" style="width: 100%"></div>
                                </div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Storage</span>
                                    <span class="badge badge-warning">65%</span>
                                </div>
                                <div class="progress-bar-thin">
                                    <div class="progress-fill bg-warning" style="width: 65%"></div>
                                </div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Uptime</span>
                                    <span class="badge badge-info">99.8%</span>
                                </div>
                                <div class="progress-bar-thin">
                                    <div class="progress-fill bg-info" style="width: 99.8%"></div>
                                </div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Role</span>
                                    <span class="font-weight-medium text-primary">Administrator</span>
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

            <!-- Semester Info - Minimalist -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="content-card content-card-success">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-2">Semester 20241</h5>
                                    <p class="text-muted mb-3">Ganjil 2024/2025</p>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-4">
                                            <div class="text-muted small">Start</div>
                                            <div class="font-weight-medium">Sep 2024</div>
                                        </div>
                                        <div class="mr-4">
                                            <div class="text-muted small">End</div>
                                            <div class="font-weight-medium">Feb 2025</div>
                                        </div>
                                        <div>
                                            <div class="text-muted small">Status</div>
                                            <div class="badge badge-success-light text-success">Active</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <div class="d-flex align-items-center">
                                        <div class="progress-circular mr-4">
                                            <div class="progress-circle" data-percentage="60">
                                                <span class="progress-circle-value">60%</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-muted small mb-1">Semester Progress</div>
                                            <div class="font-weight-medium">On Track</div>
                                        </div>
                                    </div>
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

/* Stat Card Primary (Users) */
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

/* Stat Card Warning (Panitia) */
.stat-card-warning {
    box-shadow: -8px 7px 12px rgb(255 159 67 / 40%);
    border: 1px solid rgba(255, 159, 67, 0.1);
}

.stat-card-warning:hover {
    transform: translate(12px, -14px);
    box-shadow: -20px 18px 32px rgba(255, 159, 67, 0.2);
    border-color: #ff9f43;
}

/* Stat Card Info (Semester) */
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

/* Content Cards */
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

.content-card-success {
    position: relative;
    box-shadow: -8px 7px 12px rgb(40 199 111 / 30%);
    border: 1px solid rgba(40, 199, 111, 0.08);
}

.content-card-success:hover {
    transform: translate(6px, -7px);
    box-shadow: -12px 10px 20px rgba(40, 199, 111, 0.35);
    border-color: rgba(40, 199, 111, 0.2);
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

/* Activity List inside content card */
.activity-list {
    padding-left: 0;
    margin: 0;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    flex-shrink: 0;
    font-size: 0.875rem;
}

.activity-content {
    flex: 1;
}

.activity-content p {
    margin: 0;
    color: var(--text-primary);
    font-size: 0.9375rem;
}

.activity-content small {
    font-size: 0.8125rem;
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

/* Progress Bars */
.progress-bar-thin {
    height: 4px;
    background: var(--border-color);
    border-radius: 2px;
    overflow: hidden;
    margin-top: 4px;
}

.progress-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s ease;
}

.info-item {
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.info-item:last-child {
    border-bottom: none;
}

/* Progress Circle */
.progress-circular {
    position: relative;
}

.progress-circle {
    width: 60px;
    height: 60px;
    position: relative;
}

.progress-circle:before {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid var(--border-color);
    border-radius: 50%;
}

.progress-circle:after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid #667eea;
    border-radius: 50%;
    border-top-color: transparent;
    border-right-color: transparent;
    transform: rotate(45deg);
    animation: progress-circle 1s ease-out forwards;
    animation-play-state: running;
}

@keyframes progress-circle {
    0% {
        transform: rotate(45deg);
    }
    100% {
        transform: rotate(calc(45deg + (360deg * 60 / 100)));
    }
}

.progress-circle-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-primary);
}

/* Badge Light */
.badge-success-light {
    background-color: rgba(40, 199, 111, 0.1);
    color: #28c76f;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Badge Styles */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
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
    .content-card-info:hover,
    .content-card-success:hover {
        transform: translateY(-2px);
    }
    
    /* Adjust shadow size on mobile */
    .stat-card-primary,
    .stat-card-success,
    .stat-card-warning,
    .stat-card-info,
    .content-card-primary,
    .content-card-info,
    .content-card-success {
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
    }
    
    .action-card:hover {
        transform: translateY(-2px);
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
    
    // Refresh status button functionality
    $('.info-item').click(function() {
        const $this = $(this);
        const $progressFill = $this.find('.progress-fill');
        const originalWidth = $progressFill.width() / $this.find('.progress-bar-thin').width() * 100;
        
        // Animate refresh
        $progressFill.css('width', '0%');
        setTimeout(() => {
            $progressFill.css('width', originalWidth + '%');
        }, 300);
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
    
    // Activity item hover effect
    $('.activity-item').on('mouseenter', function() {
        $(this).css('transform', 'translateX(2px)');
    }).on('mouseleave', function() {
        $(this).css('transform', 'translateX(0)');
    });
});
</script>