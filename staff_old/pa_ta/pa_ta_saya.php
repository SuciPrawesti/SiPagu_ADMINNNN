<?php
/**
 * PA/TA SAYA - SiPagu (STAFF)
 * Halaman untuk melihat data PA/TA milik staff sendiri
 * Lokasi: staff/pa_ta/pa_ta_saya.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

$id_user = $_SESSION['id_user'];

// Filter
$semester = isset($_GET['semester']) ? mysqli_real_escape_string($koneksi, $_GET['semester']) : '';

$where = "tpt.id_user = '$id_user'";
if ($semester) $where .= " AND tpt.semester = '$semester'";

$query = mysqli_query($koneksi, "
    SELECT tpt.*, p.jbtn_pnt, p.honor_std, p.honor_p1, p.honor_p2
    FROM t_transaksi_pa_ta tpt
    JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
    WHERE $where
    ORDER BY tpt.semester DESC, tpt.periode_wisuda DESC
");

// Dropdown semester
$query_semester = mysqli_query($koneksi, 
    "SELECT DISTINCT semester FROM t_transaksi_pa_ta WHERE id_user='$id_user' ORDER BY semester DESC"
);

$total_data = mysqli_num_rows($query);
if ($total_data > 0) mysqli_data_seek($query, 0);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_staff.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Data PA / TA Saya</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>staff/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">PA / TA</div>
            </div>
        </div>

        <div class="section-body">
            <!-- Summary -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-statistic-2">
                        <div class="card-stats">
                            <div class="card-stats-title">Total Data PA/TA</div>
                            <div class="card-stats-items">
                                <div class="card-stats-item">
                                    <div class="card-stats-item-count"><?= $total_data ?></div>
                                    <div class="card-stats-item-label">Data</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-icon bg-warning shadow-warning">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Data Panitia PA / TA</h4>
                            <div class="card-header-action">
                                <span class="badge badge-info"><i class="fas fa-eye mr-1"></i> Data Milik Anda</span>
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
                                            <a href="pa_ta_saya.php" class="btn btn-secondary btn-block">Reset</a>
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
                                            <th>Periode Wisuda</th>
                                            <th>Jabatan Panitia</th>
                                            <th>Prodi</th>
                                            <th>Jml Mhs Prodi</th>
                                            <th>Jml Mhs Bimbingan</th>
                                            <th>Ketua PGJI</th>
                                            <th>Honor Std</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($total_data > 0): ?>
                                            <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><span class="badge badge-primary"><?= htmlspecialchars($row['semester']) ?></span></td>
                                                <td><?= ucfirst($row['periode_wisuda']) ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['jbtn_pnt']) ?></strong>
                                                </td>
                                                <td><span class="badge badge-secondary"><?= htmlspecialchars($row['prodi']) ?></span></td>
                                                <td><?= $row['jml_mhs_prodi'] ?> mhs</td>
                                                <td><?= $row['jml_mhs_bimbingan'] ?> mhs</td>
                                                <td><?= htmlspecialchars($row['ketua_pgji']) ?></td>
                                                <td class="text-success font-weight-bold">
                                                    Rp <?= number_format($row['honor_std'], 0, ',', '.') ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-5 text-muted">
                                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                    <p>Belum ada data PA/TA untuk Anda</p>
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

            <!-- Info Card -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Data PA/TA dikelola oleh Administrator. Jika ada perubahan atau penambahan data, hubungi admin sistem.
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/footer_scripts.php'; ?>
