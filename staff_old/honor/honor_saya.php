<?php
/**
 * HONOR SAYA - SiPagu (STAFF)
 * Halaman untuk melihat riwayat honor mengajar milik staff sendiri
 * Lokasi: staff/honor/honor_saya.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

$id_user = $_SESSION['id_user'];

// Filter
$semester = isset($_GET['semester']) ? mysqli_real_escape_string($koneksi, $_GET['semester']) : '';
$bulan    = isset($_GET['bulan'])    ? mysqli_real_escape_string($koneksi, $_GET['bulan'])    : '';

$where = "j.id_user = '$id_user'";
if ($semester) $where .= " AND thd.semester = '$semester'";
if ($bulan)    $where .= " AND thd.bulan = '$bulan'";

$query = mysqli_query($koneksi, "
    SELECT thd.*, j.nama_matkul, j.kode_matkul, j.jml_mhs, u.honor_persks
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    JOIN t_user u ON j.id_user = u.id_user
    WHERE $where
    ORDER BY thd.semester DESC, thd.bulan DESC
");

// Dropdown semester
$query_semester = mysqli_query($koneksi, "
    SELECT DISTINCT thd.semester 
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    WHERE j.id_user = '$id_user'
    ORDER BY thd.semester DESC
");

$months = [
    'januari' => 'Januari', 'februari' => 'Februari', 'maret' => 'Maret',
    'april' => 'April', 'mei' => 'Mei', 'juni' => 'Juni',
    'juli' => 'Juli', 'agustus' => 'Agustus', 'september' => 'September',
    'oktober' => 'Oktober', 'november' => 'November', 'desember' => 'Desember'
];

$total_data = mysqli_num_rows($query);
if ($total_data > 0) mysqli_data_seek($query, 0);

// Hitung total honor
$total_honor_rupiah = 0;
while ($r = mysqli_fetch_assoc($query)) {
    $total_honor_rupiah += $r['jml_tm'] * $r['sks_tempuh'] * $r['honor_persks'];
}
if ($total_data > 0) mysqli_data_seek($query, 0);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_staff.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Riwayat Honor Mengajar</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>staff/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Honor Mengajar</div>
                <div class="breadcrumb-item">Riwayat</div>
            </div>
        </div>

        <div class="section-body">
            <!-- Summary -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-statistic-2">
                        <div class="card-stats">
                            <div class="card-stats-title">Total Transaksi</div>
                            <div class="card-stats-items">
                                <div class="card-stats-item">
                                    <div class="card-stats-item-count"><?= $total_data ?></div>
                                    <div class="card-stats-item-label">Transaksi</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-icon bg-success shadow-success">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card card-statistic-2">
                        <div class="card-stats">
                            <div class="card-stats-title">Estimasi Total Honor</div>
                            <div class="card-stats-items">
                                <div class="card-stats-item">
                                    <div class="card-stats-item-count" style="font-size: 1.2rem;">
                                        Rp <?= number_format($total_honor_rupiah, 0, ',', '.') ?>
                                    </div>
                                    <div class="card-stats-item-label">berdasarkan filter aktif</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-icon bg-warning shadow-warning">
                            <i class="fas fa-calculator"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Data Honor Mengajar</h4>
                            <div class="card-header-action">
                                <a href="<?= BASE_URL ?>staff/honor/input_honor.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Input Honor Baru
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filter -->
                            <form method="GET" class="mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Filter Semester</label>
                                            <select name="semester" class="form-control">
                                                <option value="">-- Semua Semester --</option>
                                                <?php while ($s = mysqli_fetch_assoc($query_semester)): ?>
                                                    <option value="<?= $s['semester'] ?>" <?= $semester === $s['semester'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($s['semester']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Filter Bulan</label>
                                            <select name="bulan" class="form-control">
                                                <option value="">-- Semua Bulan --</option>
                                                <?php foreach ($months as $key => $label): ?>
                                                    <option value="<?= $key ?>" <?= $bulan === $key ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-group d-flex gap-2">
                                            <button type="submit" class="btn btn-primary mr-2">Filter</button>
                                            <a href="honor_saya.php" class="btn btn-secondary">Reset</a>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Semester</th>
                                            <th>Bulan</th>
                                            <th>Mata Kuliah</th>
                                            <th>Jml TM</th>
                                            <th>SKS</th>
                                            <th>Honor/SKS</th>
                                            <th>Total Honor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($total_data > 0): ?>
                                            <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): 
                                                $total = $row['jml_tm'] * $row['sks_tempuh'] * $row['honor_persks'];
                                            ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><span class="badge badge-primary"><?= htmlspecialchars($row['semester']) ?></span></td>
                                                <td><?= ucfirst($row['bulan']) ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['nama_matkul']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($row['kode_matkul']) ?></small>
                                                </td>
                                                <td><?= $row['jml_tm'] ?> TM</td>
                                                <td><?= $row['sks_tempuh'] ?> SKS</td>
                                                <td>Rp <?= number_format($row['honor_persks'], 0, ',', '.') ?></td>
                                                <td>
                                                    <strong class="text-success">
                                                        Rp <?= number_format($total, 0, ',', '.') ?>
                                                    </strong>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">
                                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                    <p>Belum ada data honor mengajar</p>
                                                    <a href="<?= BASE_URL ?>staff/honor/input_honor.php" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-plus mr-1"></i> Input Honor Sekarang
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if ($total_data > 0): ?>
                                    <tfoot>
                                        <tr class="table-dark">
                                            <td colspan="7" class="text-right font-weight-bold">Total Estimasi Honor:</td>
                                            <td class="font-weight-bold text-warning">
                                                Rp <?= number_format($total_honor_rupiah, 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
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
