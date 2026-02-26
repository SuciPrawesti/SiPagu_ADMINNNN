<?php
/**
 * HONOR DOSEN - SiPagu (KOORDINATOR)
 * Halaman untuk melihat data honor dosen (READ ONLY)
 * Lokasi: koordinator/honor/honor_dosen.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Ambil filter dari GET
$semester = $_GET['semester'] ?? '';
$bulan = $_GET['bulan'] ?? '';

// Query data honor dosen dengan filter - FIXED VERSION
$where_conditions = [];
$params = [];
$types = "";

if ($semester) {
    $where_conditions[] = "thd.semester = ?";
    $params[] = $semester;
    $types .= "s";
}

if ($bulan) {
    $where_conditions[] = "thd.bulan = ?";
    $params[] = $bulan;
    $types .= "s";
}

$where_sql = "1=1";
if (!empty($where_conditions)) {
    $where_sql = implode(" AND ", $where_conditions);
}

// Query utama untuk data honor dosen
if (!empty($params)) {
    $stmt = mysqli_prepare($koneksi, "
        SELECT thd.*, j.nama_matkul, j.kode_matkul, u.nama_user, u.npp_user, u.honor_persks
        FROM t_transaksi_honor_dosen thd
        JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
        JOIN t_user u ON j.id_user = u.id_user
        WHERE $where_sql
        ORDER BY thd.id_thd DESC
    ");
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, "
        SELECT thd.*, j.nama_matkul, j.kode_matkul, u.nama_user, u.npp_user, u.honor_persks
        FROM t_transaksi_honor_dosen thd
        JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
        JOIN t_user u ON j.id_user = u.id_user
        WHERE $where_sql
        ORDER BY thd.id_thd DESC
    ");
}

// Query untuk dropdown filter semester
$semesters = [];
$query_semester = mysqli_query($koneksi, "SELECT DISTINCT semester FROM t_transaksi_honor_dosen ORDER BY semester DESC");
while ($row = mysqli_fetch_assoc($query_semester)) {
    $semesters[] = $row['semester'];
}

$months = [
    'januari' => 'Januari',
    'februari' => 'Februari', 
    'maret' => 'Maret',
    'april' => 'April',
    'mei' => 'Mei',
    'juni' => 'Juni',
    'juli' => 'Juli',
    'agustus' => 'Agustus',
    'september' => 'September',
    'oktober' => 'Oktober',
    'november' => 'November',
    'desember' => 'Desember'
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_koordinator.php';

// Hitung total data untuk summary
$total_data = mysqli_num_rows($result);

// Reset pointer untuk digunakan kembali
if ($total_data > 0) {
    mysqli_data_seek($result, 0);
}

// Buat where clause untuk query summary (tanpa prepared statement karena untuk COUNT)
$where_summary = "1=1";
if ($semester) {
    $where_summary .= " AND thd.semester = '$semester'";
}
if ($bulan) {
    $where_summary .= " AND thd.bulan = '$bulan'";
}
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Data Honor Dosen</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Honor Dosen</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Data Honor Mengajar Dosen</h4>
                            <div class="card-header-action">
                                <div class="badge badge-warning">
                                    <i class="fas fa-eye mr-1"></i> Read Only
                                </div>
                                <div class="dropdown d-inline">
                                    <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-download mr-1"></i> Export
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="exportDropdown">
                                        <a class="dropdown-item" href="#" onclick="exportToPDF()">
                                            <i class="fas fa-file-pdf text-danger mr-1"></i> PDF
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="exportToExcel()">
                                            <i class="fas fa-file-excel text-success mr-1"></i> Excel
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="exportToPrint()">
                                            <i class="fas fa-print text-primary mr-1"></i> Print
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filter Card -->
                            <div class="card card-primary mb-4">
                                <div class="card-header">
                                    <h4><i class="fas fa-filter mr-2"></i>Filter Data</h4>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="filter-form">
                                        <div class="row align-items-end">
                                            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                                <div class="form-group mb-0">
                                                    <label class="form-label">Semester</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text">
                                                                <i class="fas fa-graduation-cap"></i>
                                                            </div>
                                                        </div>
                                                        <select name="semester" class="form-control select2">
                                                            <option value="">Semua Semester</option>
                                                            <?php foreach ($semesters as $sem): ?>
                                                            <option value="<?= htmlspecialchars($sem) ?>" <?= $sem == $semester ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($sem) ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                                <div class="form-group mb-0">
                                                    <label class="form-label">Bulan</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text">
                                                                <i class="fas fa-calendar-alt"></i>
                                                            </div>
                                                        </div>
                                                        <select name="bulan" class="form-control select2">
                                                            <option value="">Semua Bulan</option>
                                                            <?php foreach ($months as $key => $month): ?>
                                                            <option value="<?= $key ?>" <?= $key == $bulan ? 'selected' : '' ?>>
                                                                <?= $month ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group mb-0">
                                                    <label class="form-label d-none d-md-block">&nbsp;</label>
                                                    <div class="btn-group w-100" role="group">
                                                        <button type="submit" class="btn btn-primary btn-lg">
                                                            <i class="fas fa-filter mr-1"></i> Terapkan Filter
                                                        </button>
                                                        <a href="honor_dosen.php" class="btn btn-outline-secondary btn-lg">
                                                            <i class="fas fa-sync-alt mr-1"></i> Reset
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Info Alert -->
                            <div class="alert alert-info alert-has-icon">
                                <div class="alert-icon"><i class="fas fa-info-circle"></i></div>
                                <div class="alert-body">
                                    <div class="alert-title">Informasi</div>
                                    <p class="mb-0">
                                        <strong>Analisis Database:</strong> 
                                        <ul class="mb-0 pl-3">
                                            <li>Tabel <code>t_user</code> memiliki kolom <code>honor_persks</code> untuk menyimpan honor per SKS dosen</li>
                                            <li>Tabel <code>t_transaksi_honor_dosen</code> menyimpan jumlah TM dan SKS yang ditempuh</li>
                                            <li>Total honor = honor_persks × sks_tempuh × jml_tm</li>
                                            <li>Kolom status tidak tersedia di database untuk approval</li>
                                        </ul>
                                    </p>
                                </div>
                            </div>

                            <!-- Summary Cards -->
                            <div class="row mb-4">
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-primary">
                                            <i class="fas fa-database"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Data</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($total_data) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-success">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Dosen Terlibat</h4>
                                            </div>
                                            <div class="card-body">
                                                <?php 
                                                $query_dosen = mysqli_query($koneksi, "
                                                    SELECT COUNT(DISTINCT u.id_user) as total 
                                                    FROM t_transaksi_honor_dosen thd
                                                    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
                                                    JOIN t_user u ON j.id_user = u.id_user
                                                    WHERE $where_summary
                                                ");
                                                $row_dosen = mysqli_fetch_assoc($query_dosen);
                                                echo number_format($row_dosen['total']);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-warning">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Mata Kuliah</h4>
                                            </div>
                                            <div class="card-body">
                                                <?php 
                                                $query_matkul = mysqli_query($koneksi, "
                                                    SELECT COUNT(DISTINCT j.id_jdwl) as total 
                                                    FROM t_transaksi_honor_dosen thd
                                                    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
                                                    WHERE $where_summary
                                                ");
                                                $row_matkul = mysqli_fetch_assoc($query_matkul);
                                                echo number_format($row_matkul['total']);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-info">
                                            <i class="fas fa-calculator"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Honor</h4>
                                            </div>
                                            <div class="card-body">
                                                <?php 
                                                // Hitung total honor dari semua data
                                                $total_honor_all = 0;
                                                if ($total_data > 0) {
                                                    mysqli_data_seek($result, 0);
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $honor_persks = $row['honor_persks'];
                                                        $total_honor = $honor_persks * $row['sks_tempuh'] * $row['jml_tm'];
                                                        $total_honor_all += $total_honor;
                                                    }
                                                    // Reset pointer lagi
                                                    mysqli_data_seek($result, 0);
                                                }
                                                echo 'Rp ' . number_format($total_honor_all, 0, ',', '.');
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Data Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-honor">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-center align-middle" style="width: 50px;">#</th>
                                            <th class="align-middle text-nowrap">Semester</th>
                                            <th class="align-middle text-nowrap">Bulan</th>
                                            <th class="align-middle">Dosen</th>
                                            <th class="align-middle">Mata Kuliah</th>
                                            <th class="text-center align-middle" style="width: 70px;">TM</th>
                                            <th class="text-center align-middle" style="width: 70px;">SKS</th>
                                            <th class="text-center align-middle" style="width: 120px;">Honor/SKS</th>
                                            <th class="text-center align-middle" style="width: 140px;">Total Honor</th>
                                            <th class="text-center align-middle" style="width: 80px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        $total_tm = 0;
                                        $total_sks = 0;
                                        $total_honor = 0;
                                        
                                        if ($total_data > 0):
                                            while ($row = mysqli_fetch_assoc($result)): 
                                                // Tentukan badge warna berdasarkan semester
                                                $semester_color = (strpos($row['semester'], '2') !== false) ? 'primary' : 'success';
                                                
                                                // Hitung honor
                                                $honor_persks = $row['honor_persks'];
                                                $total_honor_row = $honor_persks * $row['sks_tempuh'] * $row['jml_tm'];
                                                
                                                // Akumulasi total
                                                $total_tm += $row['jml_tm'];
                                                $total_sks += $row['sks_tempuh'];
                                                $total_honor += $total_honor_row;
                                        ?>
                                        <tr>
                                            <td class="text-center align-middle">
                                                <span class="text-muted"><?= $no++ ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex flex-column">
                                                    <span class="badge badge-<?= $semester_color ?> badge-pill" style="width: fit-content;">
                                                        <?= htmlspecialchars($row['semester']) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex flex-column">
                                                    <span class="badge badge-light text-dark">
                                                        <?= ucfirst(htmlspecialchars($row['bulan'])) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-icon-wrapper mr-2">
                                                        <div class="avatar-icon bg-primary">
                                                            <span><?= strtoupper(substr($row['nama_user'], 0, 1)) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="font-weight-bold text-primary text-truncate" style="max-width: 150px;"><?= htmlspecialchars($row['nama_user']) ?></span>
                                                        <small class="text-muted"><?= htmlspecialchars($row['npp_user']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex flex-column">
                                                    <span class="font-weight-bold text-dark text-truncate" style="max-width: 200px;"><?= htmlspecialchars($row['nama_matkul']) ?></span>
                                                    <small class="text-muted"><?= htmlspecialchars($row['kode_matkul']) ?></small>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-info badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                    <?= $row['jml_tm'] ?>
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-secondary badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                    <?= $row['sks_tempuh'] ?>
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="text-success font-weight-bold">
                                                    Rp <?= number_format($honor_persks, 0, ',', '.') ?>
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="font-weight-bold text-success">
                                                    Rp <?= number_format($total_honor_row, 0, ',', '.') ?>
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button class="btn btn-sm btn-outline-info btn-detail" 
                                                        data-toggle="tooltip" 
                                                        title="Lihat Detail"
                                                        data-npp="<?= htmlspecialchars($row['npp_user']) ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_user']) ?>"
                                                        data-matkul="<?= htmlspecialchars($row['kode_matkul'] . ' - ' . $row['nama_matkul']) ?>"
                                                        data-semester="<?= htmlspecialchars($row['semester']) ?>"
                                                        data-bulan="<?= ucfirst(htmlspecialchars($row['bulan'])) ?>"
                                                        data-tm="<?= $row['jml_tm'] ?>"
                                                        data-sks="<?= $row['sks_tempuh'] ?>"
                                                        data-honor-persks="<?= $honor_persks ?>"
                                                        data-total-honor="<?= $total_honor_row ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        
                                        <!-- Total Row -->
                                        <tr class="table-active font-weight-bold">
                                            <td colspan="5" class="text-right align-middle">TOTAL:</td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-info p-2"><?= number_format($total_tm) ?></span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-secondary p-2"><?= number_format($total_sks) ?></span>
                                            </td>
                                            <td class="text-center align-middle">-</td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-success p-2">Rp <?= number_format($total_honor, 0, ',', '.') ?></span>
                                            </td>
                                            <td class="text-center align-middle"></td>
                                        </tr>
                                        
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-5">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon">
                                                        <i class="fas fa-search fa-3x text-muted"></i>
                                                    </div>
                                                    <h2 class="mt-3">Data tidak ditemukan</h2>
                                                    <p class="lead">
                                                        Tidak ada data honor dosen yang sesuai dengan filter yang dipilih.
                                                    </p>
                                                    <a href="honor_dosen.php" class="btn btn-primary mt-4">
                                                        <i class="fas fa-redo mr-1"></i> Reset Filter
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="text-muted">
                                    Menampilkan <?= $total_data > 0 ? '1' : '0' ?> - <?= min($no-1, $total_data) ?> dari <?= $total_data ?> data
                                </div>
                                <?php if ($total_data > 0): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination mb-0">
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                        <?php if ($total_data > 10): ?>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <?php endif; ?>
                                        <?php if ($total_data > 20): ?>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="#" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<!-- End Main Content -->

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-info-circle mr-2"></i>Detail Honor Dosen
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">NPP Dosen</label>
                            <p id="detail-npp" class="form-control-plaintext"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Nama Dosen</label>
                            <p id="detail-nama" class="form-control-plaintext"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Mata Kuliah</label>
                            <p id="detail-matkul" class="form-control-plaintext"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Semester</label>
                            <p id="detail-semester" class="form-control-plaintext"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Bulan</label>
                            <p id="detail-bulan" class="form-control-plaintext"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Jumlah TM</label>
                            <p id="detail-tm" class="form-control-plaintext"></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">SKS</label>
                            <p id="detail-sks" class="form-control-plaintext"></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Honor/SKS</label>
                            <p id="detail-honor-persks" class="form-control-plaintext"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Perhitungan Honor</label>
                            <div class="alert alert-light border">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Honor per SKS:</span>
                                    <span id="calc-honor-persks"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>SKS ditempuh:</span>
                                    <span id="calc-sks"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Jumlah TM:</span>
                                    <span id="calc-tm"></span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between font-weight-bold">
                                    <span>Total Honor:</span>
                                    <span id="calc-total-honor" class="text-success"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?php 
include __DIR__ . '/../includes/footer.php';
?>

<!-- Custom CSS untuk halaman ini -->
<style>
/* Custom styles untuk halaman honor dosen */
.avatar-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.avatar-icon.bg-primary {
    background-color: #4361ee;
}

.empty-state {
    padding: 2rem;
    text-align: center;
}

.empty-state-icon {
    margin-bottom: 1.5rem;
}

.empty-state h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.card-statistic-1 .card-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}

.card-statistic-1 .card-body {
    font-size: 1.5rem;
    font-weight: bold;
}

.table-active {
    background-color: rgba(0, 123, 255, 0.1) !important;
}

/* Responsive table - Perbaikan utama */
.table-responsive {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    overflow: hidden;
}

#table-honor {
    margin-bottom: 0;
    min-width: 1000px; /* Minimum width untuk desktop */
}

#table-honor th {
    white-space: nowrap;
    font-size: 0.85rem;
    padding: 12px 8px;
    border-bottom: 2px solid #e3e6f0 !important;
}

#table-honor td {
    padding: 10px 8px;
    vertical-align: middle;
    font-size: 0.9rem;
}

#table-honor .badge-pill {
    font-size: 0.8rem;
    padding: 0.35em 0.65em;
}

/* Mobile Responsiveness */
@media (max-width: 1199.98px) {
    #table-honor {
        min-width: 900px;
    }
}

@media (max-width: 991.98px) {
    .card-header-action .btn {
        margin-bottom: 5px;
    }
    
    .filter-form .btn-group {
        margin-top: 10px;
    }
}

@media (max-width: 768px) {
    /* Hide columns on tablets */
    #table-honor th:nth-child(3), /* Bulan */
    #table-honor td:nth-child(3) {
        display: none;
    }
    
    #table-honor th:nth-child(8), /* Honor/SKS */
    #table-honor td:nth-child(8) {
        display: none;
    }
    
    /* Adjust table width */
    #table-honor {
        min-width: 700px;
    }
    
    /* Summary cards - 2 per row */
    .col-xl-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 576px) {
    /* Hide more columns on mobile */
    #table-honor th:nth-child(2), /* Semester */
    #table-honor td:nth-child(2) {
        display: none;
    }
    
    /* Show important columns only */
    #table-honor {
        min-width: 500px;
    }
    
    /* Adjust avatar size */
    .avatar-icon {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    /* Reduce font sizes */
    #table-honor td {
        font-size: 0.8rem;
        padding: 8px 5px;
    }
    
    #table-honor th {
        font-size: 0.75rem;
        padding: 10px 5px;
    }
    
    /* Button adjustments */
    .btn-detail {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    /* Summary cards - 1 per row */
    .col-xl-3 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 15px;
    }
    
    .card-statistic-1 .card-body {
        font-size: 1.2rem;
    }
    
    /* Adjust filter form */
    .filter-form .btn-group .btn {
        margin-bottom: 5px;
        width: 100%;
    }
    
    .filter-form .btn-group {
        flex-direction: column;
    }
}

/* Extra small devices */
@media (max-width: 375px) {
    #table-honor th:nth-child(7), /* SKS */
    #table-honor td:nth-child(7) {
        display: none;
    }
    
    #table-honor {
        min-width: 400px;
    }
}

/* Tooltip customization */
.tooltip {
    font-size: 0.875rem;
}

/* Modal responsive */
@media (max-width: 576px) {
    .modal-dialog {
        margin: 10px;
    }
}

/* Print optimization */
@media print {
    .navbar, .sidebar, .card-header-action, .btn, .pagination, .modal,
    .filter-form, .alert, .card-statistic-1 {
        display: none !important;
    }
    
    .card {
        border: 0 !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 11px;
        border: 1px solid #000 !important;
    }
    
    .table th {
        background-color: #f8f9fa !important;
        border: 1px solid #000 !important;
    }
    
    .table td {
        border: 1px solid #000 !important;
    }
    
    #table-honor {
        min-width: 100% !important;
    }
    
    /* Show all columns when printing */
    #table-honor th,
    #table-honor td {
        display: table-cell !important;
    }
}

/* Hover effects */
#table-honor tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transition: background-color 0.2s ease;
}

/* Scrollbar styling for table */
.table-responsive::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<!-- Custom JavaScript untuk halaman ini -->
<script>
$(document).ready(function() {
    // Inisialisasi tooltip
    $('[data-toggle="tooltip"]').tooltip({
        trigger: 'hover',
        placement: 'top'
    });
    
    // Inisialisasi select2
    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Pilih...',
            theme: 'bootstrap4'
        });
    }
    
    // Handle tombol detail
    $('.btn-detail').click(function() {
        const npp = $(this).data('npp');
        const nama = $(this).data('nama');
        const matkul = $(this).data('matkul');
        const semester = $(this).data('semester');
        const bulan = $(this).data('bulan');
        const tm = $(this).data('tm');
        const sks = $(this).data('sks');
        const honorPersks = $(this).data('honor-persks');
        const totalHonor = $(this).data('total-honor');
        
        $('#detail-npp').text(npp);
        $('#detail-nama').text(nama);
        $('#detail-matkul').text(matkul);
        $('#detail-semester').text(semester);
        $('#detail-bulan').text(bulan);
        $('#detail-tm').text(tm);
        $('#detail-sks').text(sks);
        $('#detail-honor-persks').text('Rp ' + formatRupiah(honorPersks));
        
        // Update perhitungan
        $('#calc-honor-persks').text('Rp ' + formatRupiah(honorPersks));
        $('#calc-sks').text(sks + ' SKS');
        $('#calc-tm').text(tm + ' TM');
        $('#calc-total-honor').text('Rp ' + formatRupiah(totalHonor));
        
        $('#detailModal').modal('show');
    });
    
    // Responsive table adjustment
    function adjustTableResponsive() {
        const width = $(window).width();
        const tableResponsive = $('.table-responsive');
        
        if (width < 768) {
            tableResponsive.css('max-height', '400px');
            tableResponsive.css('overflow-y', 'auto');
        } else {
            tableResponsive.css('max-height', '');
            tableResponsive.css('overflow-y', '');
        }
    }
    
    // Initial adjustment
    adjustTableResponsive();
    
    // Adjust on resize
    $(window).resize(function() {
        adjustTableResponsive();
    });
});

// Fungsi format rupiah
function formatRupiah(angka) {
    const number = parseFloat(angka);
    return number.toLocaleString('id-ID');
}

// Fungsi export
function exportToPDF() {
    Swal.fire({
        icon: 'info',
        title: 'Ekspor PDF',
        text: 'Fitur ekspor PDF akan segera tersedia.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#4361ee'
    });
}

function exportToExcel() {
    Swal.fire({
        icon: 'info',
        title: 'Ekspor Excel',
        text: 'Fitur ekspor Excel akan segera tersedia.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#4361ee'
    });
}

function exportToPrint() {
    // Clone the table
    const printContent = document.getElementById('table-honor').cloneNode(true);
    
    // Remove action column for printing
    const actionHeader = printContent.querySelector('th:last-child');
    const actionCells = printContent.querySelectorAll('td:last-child');
    
    if (actionHeader) actionHeader.remove();
    actionCells.forEach(cell => cell.remove());
    
    // Open print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Cetak Data Honor Dosen</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 8px; text-align: left; }
                    td { border: 1px solid #dee2e6; padding: 8px; }
                    .text-center { text-align: center; }
                    .text-right { text-align: right; }
                    .font-weight-bold { font-weight: bold; }
                    .badge { padding: 3px 8px; border-radius: 4px; font-size: 12px; }
                    .badge-primary { background-color: #4361ee; color: white; }
                    .badge-success { background-color: #28a745; color: white; }
                    .badge-info { background-color: #17a2b8; color: white; }
                    .badge-secondary { background-color: #6c757d; color: white; }
                    @media print {
                        body { margin: 0; }
                        table { font-size: 11px; }
                    }
                </style>
            </head>
            <body>
                <h2>Data Honor Dosen</h2>
                <p>Tanggal cetak: ${new Date().toLocaleDateString('id-ID')}</p>
                <p>Filter: Semester - ${$('select[name="semester"]').val() || 'Semua'}, 
                       Bulan - ${$('select[name="bulan"]').val() ? $('select[name="bulan"] option:selected').text() : 'Semua'}</p>
                ${printContent.outerHTML}
                <script>
                    window.onload = function() {
                        window.print();
                        window.onafterprint = function() {
                            window.close();
                        };
                    };
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

// Tambahkan event listener untuk tombol print
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        exportToPrint();
    }
});
</script>

<?php
include __DIR__ . '/../includes/footer_scripts.php';

// Tutup koneksi
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}
?>