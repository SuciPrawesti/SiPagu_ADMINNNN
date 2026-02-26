<?php
/**
 * JADWAL SAYA - SiPagu (STAFF)
 * Halaman untuk melihat jadwal mengajar milik staff sendiri
 * Lokasi: staff/jadwal/jadwal_saya.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

$id_user = $_SESSION['id_user'];

// Filter
$semester = isset($_GET['semester']) ? mysqli_real_escape_string($koneksi, $_GET['semester']) : '';

$where = "j.id_user = '$id_user'";
if ($semester) {
    $where .= " AND j.semester = '$semester'";
}

$query = mysqli_query($koneksi, "
    SELECT j.*, 
           COUNT(thd.id_thd) as jml_transaksi,
           COALESCE(SUM(thd.jml_tm), 0) as total_tm,
           COALESCE(SUM(thd.sks_tempuh), 0) as total_sks
    FROM t_jadwal j
    LEFT JOIN t_transaksi_honor_dosen thd ON thd.id_jadwal = j.id_jdwl
    WHERE $where
    GROUP BY j.id_jdwl
    ORDER BY j.semester DESC, j.kode_matkul ASC
");

// Dropdown semester
$query_semester = mysqli_query($koneksi, 
    "SELECT DISTINCT semester FROM t_jadwal WHERE id_user='$id_user' ORDER BY semester DESC"
);

$total_jadwal = mysqli_num_rows($query);
mysqli_data_seek($query, 0);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_staff.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Jadwal Saya</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>staff/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Jadwal Saya</div>
            </div>
        </div>

        <div class="section-body">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-statistic-2">
                        <div class="card-stats">
                            <div class="card-stats-title">Total Jadwal</div>
                            <div class="card-stats-items">
                                <div class="card-stats-item">
                                    <div class="card-stats-item-count"><?= $total_jadwal ?></div>
                                    <div class="card-stats-item-label">Mata Kuliah</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-icon bg-primary shadow-primary">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Data Jadwal Mengajar</h4>
                            <div class="card-header-action">
                                <span class="badge badge-info"><i class="fas fa-user mr-1"></i> Data Milik Anda</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filter -->
                            <form method="GET" class="mb-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Filter Semester</label>
                                            <select name="semester" class="form-control" onchange="this.form.submit()">
                                                <option value="">-- Semua Semester --</option>
                                                <?php while ($s = mysqli_fetch_assoc($query_semester)): ?>
                                                    <option value="<?= $s['semester'] ?>" <?= $semester === $s['semester'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($s['semester']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-group">
                                            <a href="jadwal_saya.php" class="btn btn-secondary btn-block">Reset</a>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped" id="tableJadwal">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="50">#</th>
                                            <th>Semester</th>
                                            <th>Kode MK</th>
                                            <th>Nama Mata Kuliah</th>
                                            <th>Jml Mahasiswa</th>
                                            <th>Transaksi Honor</th>
                                            <th>Total TM</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($total_jadwal > 0): ?>
                                            <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><span class="badge badge-primary"><?= htmlspecialchars($row['semester']) ?></span></td>
                                                <td><code><?= htmlspecialchars($row['kode_matkul']) ?></code></td>
                                                <td><?= htmlspecialchars($row['nama_matkul']) ?></td>
                                                <td>
                                                    <span class="font-weight-bold"><?= $row['jml_mhs'] ?></span>
                                                    <small class="text-muted">mhs</small>
                                                </td>
                                                <td>
                                                    <?php if ($row['jml_transaksi'] > 0): ?>
                                                        <span class="badge badge-success"><?= $row['jml_transaksi'] ?> transaksi</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-light text-muted">Belum ada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $row['total_tm'] > 0 ? $row['total_tm'] . ' TM' : '-' ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5 text-muted">
                                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                    <p>Belum ada data jadwal</p>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/footer_scripts.php'; ?>
