<?php
/**
 * STAFF DASHBOARD - SiPagu Universitas Dian Nuswantoro
 * Lokasi: staff/index.php
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config.php';

$id_user = $_SESSION['id_user'];

// Total jadwal milik staff ini
$query_jadwal = mysqli_query($koneksi, 
    "SELECT COUNT(*) as total FROM t_jadwal WHERE id_user='$id_user'"
);
$total_jadwal = mysqli_fetch_assoc($query_jadwal)['total'];

// Total transaksi honor milik staff ini
$query_honor = mysqli_query($koneksi, 
    "SELECT COUNT(*) as total FROM t_transaksi_honor_dosen thd
     JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
     WHERE j.id_user='$id_user'"
);
$total_honor = mysqli_fetch_assoc($query_honor)['total'];

// Total PA/TA milik staff ini
$query_pata = mysqli_query($koneksi, 
    "SELECT COUNT(*) as total FROM t_transaksi_pa_ta WHERE id_user='$id_user'"
);
$total_pata = mysqli_fetch_assoc($query_pata)['total'];

// Total mahasiswa dari semua jadwal staff ini
$query_mhs = mysqli_query($koneksi, 
    "SELECT COALESCE(SUM(jml_mhs), 0) as total FROM t_jadwal WHERE id_user='$id_user'"
);
$total_mhs = mysqli_fetch_assoc($query_mhs)['total'];

// Data jadwal terbaru
$query_recent = mysqli_query($koneksi, 
    "SELECT j.*, thd.bulan, thd.jml_tm, thd.sks_tempuh, u.honor_persks
     FROM t_jadwal j
     LEFT JOIN t_transaksi_honor_dosen thd ON thd.id_jadwal = j.id_jdwl
     LEFT JOIN t_user u ON j.id_user = u.id_user
     WHERE j.id_user='$id_user'
     ORDER BY j.semester DESC, j.kode_matkul ASC
     LIMIT 5"
);
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<?php include __DIR__ . '/includes/sidebar_staff.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <!-- Header -->
        <div class="section-header pt-4 pb-0">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <h1 class="h3 font-weight-normal text-dark mb-1">Dashboard Staff</h1>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['nama_user'] ?? 'Staff'); ?></p>
                </div>
                <div class="text-muted">
                    <?php echo date('l, d F Y'); ?>
                </div>
            </div>
        </div>

        <div class="section-body">
            <!-- Stats Grid -->
            <div class="row mb-5">
                <!-- Stat Card 1: Jadwal -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-icon bg-primary-soft text-primary">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= $total_jadwal ?></h3>
                            <p class="stat-label">Jadwal Mengajar</p>
                        </div>
                    </div>
                </div>

                <!-- Stat Card 2: Honor -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-success">
                        <div class="stat-icon bg-success-soft text-success">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= $total_honor ?></h3>
                            <p class="stat-label">Transaksi Honor</p>
                        </div>
                    </div>
                </div>

                <!-- Stat Card 3: PA/TA -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-icon bg-warning-soft text-warning">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= $total_pata ?></h3>
                            <p class="stat-label">Data PA / TA</p>
                        </div>
                    </div>
                </div>

                <!-- Stat Card 4: Total Mahasiswa -->
                <div class="col-xl-3 col-lg-6 mb-4">
                    <div class="stat-card stat-card-info">
                        <div class="stat-icon bg-info-soft text-info">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= $total_mhs ?></h3>
                            <p class="stat-label">Total Mahasiswa</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h4 class="h5 font-weight-normal text-dark mb-0">Quick Actions</h4>
                    </div>
                    <div class="row">
                        <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>staff/jadwal/jadwal_saya.php" class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark">Jadwal Saya</h6>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>staff/honor/input_honor.php" class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-plus-circle fa-2x text-success"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark">Input Honor</h6>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>staff/honor/honor_saya.php" class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-money-bill-wave fa-2x text-warning"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark">Riwayat Honor</h6>
                            </a>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 mb-4">
                            <a href="<?= BASE_URL ?>staff/pa_ta/pa_ta_saya.php" class="action-card d-block text-center p-4">
                                <div class="action-icon mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-user-tie fa-2x text-info"></i>
                                    </div>
                                </div>
                                <h6 class="action-title mb-0 text-dark">Data PA / TA</h6>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Data -->
            <div class="row">
                <div class="col-12">
                    <div class="content-card content-card-primary">
                        <div class="card-header-simple">
                            <h5 class="card-title mb-0">Jadwal Mengajar Terbaru</h5>
                            <a href="<?= BASE_URL ?>staff/jadwal/jadwal_saya.php" class="text-primary small">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Semester</th>
                                            <th>Kode MK</th>
                                            <th>Nama Mata Kuliah</th>
                                            <th>Jml Mahasiswa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($query_recent) > 0): ?>
                                            <?php while ($row = mysqli_fetch_assoc($query_recent)): ?>
                                            <tr>
                                                <td><span class="badge badge-primary-light"><?= htmlspecialchars($row['semester']) ?></span></td>
                                                <td><code><?= htmlspecialchars($row['kode_matkul']) ?></code></td>
                                                <td><?= htmlspecialchars($row['nama_matkul']) ?></td>
                                                <td><?= htmlspecialchars($row['jml_mhs']) ?> mhs</td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                    Belum ada jadwal mengajar
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
:root {
    --primary-soft: rgba(102, 126, 234, 0.08);
    --success-soft: rgba(40, 199, 111, 0.08);
    --warning-soft: rgba(255, 159, 67, 0.08);
    --info-soft: rgba(0, 207, 232, 0.08);
    --card-bg: #ffffff;
    --border-color: #eef2f7;
    --text-primary: #2d3748;
    --text-secondary: #718096;
}

.stat-card {
    display: flex;
    align-items: center;
    padding: 20px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 15px;
    transition: all 0.3s ease;
}

.stat-card-primary { box-shadow: -8px 7px 12px rgb(102 126 234 / 40%); border: 1px solid rgba(102, 126, 234, 0.1); }
.stat-card-primary:hover { transform: translate(12px, -14px); box-shadow: -20px 18px 32px rgb(102 126 234 / 45%); border-color: #667eea; }
.stat-card-success { box-shadow: -8px 7px 12px rgb(40 199 111 / 40%); border: 1px solid rgba(40, 199, 111, 0.1); }
.stat-card-success:hover { transform: translate(12px, -14px); box-shadow: -20px 18px 32px rgba(40, 199, 111, 0.2); border-color: #28c76f; }
.stat-card-warning { box-shadow: -8px 7px 12px rgb(255 159 67 / 40%); border: 1px solid rgba(255, 159, 67, 0.1); }
.stat-card-warning:hover { transform: translate(12px, -14px); box-shadow: -20px 18px 32px rgba(255, 159, 67, 0.2); border-color: #ff9f43; }
.stat-card-info { box-shadow: -8px 7px 12px rgb(0 207 232 / 40%); border: 1px solid rgba(0, 207, 232, 0.1); }
.stat-card-info:hover { transform: translate(12px, -14px); box-shadow: -20px 18px 32px rgba(0, 207, 232, 0.2); border-color: #00cfe8; }

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

.stat-content { flex: 1; }
.stat-number { font-size: 1.75rem; font-weight: 600; color: var(--text-primary); margin: 0; line-height: 1; }
.stat-label { font-size: 0.875rem; color: var(--text-secondary); margin: 4px 0 0 0; }

.content-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 15px;
    transition: all 0.3s ease;
}

.content-card-primary {
    box-shadow: -8px 7px 12px rgb(102 126 234 / 30%);
    border: 1px solid rgba(102, 126, 234, 0.08);
}

.content-card-primary:hover {
    transform: translate(6px, -7px);
    box-shadow: -12px 10px 20px rgb(102 126 234 / 35%);
}

.card-header-simple {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.25rem 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

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

.action-card:hover .icon-wrapper { background: var(--primary-soft); transform: scale(1.1); }
.action-title { font-size: 0.875rem; font-weight: 500; }
.action-card:hover .action-title { color: #667eea !important; }

.bg-primary-soft { background-color: var(--primary-soft) !important; }
.bg-success-soft { background-color: var(--success-soft) !important; }
.bg-warning-soft { background-color: var(--warning-soft) !important; }
.bg-info-soft { background-color: var(--info-soft) !important; }

.badge-primary-light { background-color: rgba(102, 126, 234, 0.1); color: #667eea; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }

.section-header { border-bottom: 1px solid var(--border-color); }
.h3 { font-size: 1.75rem; font-weight: 400; }
.h5 { font-size: 1.125rem; font-weight: 400; }
.font-weight-normal { font-weight: 400 !important; }
.section-body { padding-top: 0; }
</style>
