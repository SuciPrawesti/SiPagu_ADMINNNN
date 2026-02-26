<?php
/**
 * JADWAL UJIAN - SiPagu (KOORDINATOR)
 * Halaman untuk melihat jadwal ujian
 * Lokasi: koordinator/jadwal/jadwal_ujian.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Ambil filter dari GET
$semester = $_GET['semester'] ?? '';
$matkul = $_GET['matkul'] ?? '';

// Query jadwal dengan filter - PERBAIKAN: Gunakan INNER JOIN untuk data yang valid
$where = "1=1";
$where_clauses = [];

if ($semester) {
    $where_clauses[] = "j.semester = '" . mysqli_real_escape_string($koneksi, $semester) . "'";
}

if ($matkul) {
    $search_term = mysqli_real_escape_string($koneksi, $matkul);
    $where_clauses[] = "(j.kode_matkul LIKE '%$search_term%' OR j.nama_matkul LIKE '%$search_term%')";
}

if (!empty($where_clauses)) {
    $where = implode(" AND ", $where_clauses);
}

$query = mysqli_query($koneksi, "
    SELECT 
        j.*, 
        u.nama_user, 
        u.npp_user,
        u.id_user as user_id
    FROM t_jadwal j
    LEFT JOIN t_user u ON j.id_user = u.id_user
    WHERE $where
    ORDER BY j.semester DESC, j.kode_matkul
") or die("Query error: " . mysqli_error($koneksi));

// Query untuk dropdown semester
$query_semester = mysqli_query($koneksi, "
    SELECT DISTINCT semester 
    FROM t_jadwal 
    ORDER BY semester DESC
");

// Reset pointer query utama
$total_jadwal = mysqli_num_rows($query);
mysqli_data_seek($query, 0);

// Hitung totals untuk summary
$total_mhs = 0;
$matkul_unik = [];
$dosen_terlibat = [];

while ($row = mysqli_fetch_assoc($query)) {
    $total_mhs += $row['jml_mhs'] ?? 0;
    if (!empty($row['kode_matkul'])) {
        $matkul_unik[$row['kode_matkul']] = true;
    }
    if (!empty($row['user_id'])) {
        $dosen_terlibat[$row['user_id']] = true;
    }
}
mysqli_data_seek($query, 0);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_koordinator.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Jadwal Ujian</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Jadwal Ujian</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Data Jadwal Mengajar</h4>
                            <div class="card-header-action">
                                <div class="badge badge-info">
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
                            <!-- Filter Card - SAMA PERSIS SEPERTI HONOR DOSEN -->
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
                                                            <?php 
                                                            mysqli_data_seek($query_semester, 0);
                                                            while ($sem = mysqli_fetch_assoc($query_semester)): 
                                                            ?>
                                                            <option value="<?= htmlspecialchars($sem['semester'] ?? '') ?>" 
                                                                <?= ($sem['semester'] ?? '') == $semester ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($sem['semester'] ?? '') ?>
                                                            </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                                <div class="form-group mb-0">
                                                    <label class="form-label">Mata Kuliah</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text">
                                                                <i class="fas fa-book"></i>
                                                            </div>
                                                        </div>
                                                        <input type="text" name="matkul" class="form-control" 
                                                               placeholder="Kode atau nama matkul" 
                                                               value="<?= htmlspecialchars($matkul) ?>">
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
                                                        <a href="jadwal_ujian.php" class="btn btn-outline-secondary btn-lg">
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
                                    <div class="alert-title">Informasi Database</div>
                                    <p class="mb-0">
                                        <strong>Struktur Tabel Jadwal:</strong> 
                                        <ul class="mb-0 pl-3">
                                            <li>Tabel <code>t_jadwal</code> menyimpan data mata kuliah dan dosen pengajar</li>
                                            <li>Setiap jadwal terkait dengan satu dosen melalui <code>id_user</code></li>
                                            <li>Data yang tidak memiliki dosen terkait akan ditampilkan dengan label "Tidak Terdaftar"</li>
                                            <li>Jumlah mahasiswa per jadwal disimpan di kolom <code>jml_mhs</code></li>
                                        </ul>
                                    </p>
                                </div>
                            </div>

                            <!-- Summary Cards -->
                            <div class="row mb-4">
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-primary">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Jadwal</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($total_jadwal) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-success">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Mahasiswa</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($total_mhs) ?>
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
                                                <h4>Mata Kuliah Unik</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format(count($matkul_unik)) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-info">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Dosen Terlibat</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format(count($dosen_terlibat)) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Data Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-jadwal">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-center align-middle" style="width: 50px;">#</th>
                                            <th class="align-middle text-nowrap">Semester</th>
                                            <th class="align-middle text-nowrap">Kode Matkul</th>
                                            <th class="align-middle">Mata Kuliah</th>
                                            <th class="align-middle">Dosen Pengajar</th>
                                            <th class="text-center align-middle" style="width: 100px;">Jumlah Mhs</th>
                                            <th class="text-center align-middle" style="width: 80px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        if ($total_jadwal > 0):
                                            while ($row = mysqli_fetch_assoc($query)): 
                                                $semester_val = $row['semester'] ?? '';
                                                $kode_matkul = $row['kode_matkul'] ?? '';
                                                $nama_matkul = $row['nama_matkul'] ?? '';
                                                $nama_user = $row['nama_user'] ?? '';
                                                $npp_user = $row['npp_user'] ?? '';
                                                $jml_mhs = $row['jml_mhs'] ?? 0;
                                                $id_jdwl = $row['id_jdwl'] ?? 0;
                                                
                                                // Tentukan badge warna berdasarkan semester
                                                $semester_color = (strpos($semester_val, '2') !== false) ? 'primary' : 'success';
                                        ?>
                                        <tr>
                                            <td class="text-center align-middle">
                                                <span class="text-muted"><?= $no++ ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex flex-column">
                                                    <span class="badge badge-<?= $semester_color ?> badge-pill" style="width: fit-content;">
                                                        <?= htmlspecialchars($semester_val) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="align-middle text-nowrap">
                                                <span class="font-weight-bold text-primary">
                                                    <?= htmlspecialchars($kode_matkul) ?>
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex flex-column">
                                                    <span class="font-weight-bold text-dark text-truncate" style="max-width: 200px;">
                                                        <?= htmlspecialchars($nama_matkul) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <?php if (!empty($nama_user)): ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-icon-wrapper mr-2">
                                                        <div class="avatar-icon bg-info">
                                                            <span><?= strtoupper(substr($nama_user, 0, 1)) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="font-weight-bold text-dark text-truncate" style="max-width: 150px;">
                                                            <?= htmlspecialchars($nama_user) ?>
                                                        </span>
                                                        <small class="text-muted"><?= htmlspecialchars($npp_user) ?></small>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-icon-wrapper mr-2">
                                                        <div class="avatar-icon bg-secondary">
                                                            <span>?</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="font-weight-bold text-muted">Dosen Tidak Terdaftar</span>
                                                        <small class="text-muted">ID: <?= $row['id_user'] ?? 'N/A' ?></small>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-info badge-pill p-2" style="min-width: 40px; display: inline-block;">
                                                    <?= number_format($jml_mhs) ?>
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button class="btn btn-sm btn-outline-info btn-detail" 
                                                        data-toggle="tooltip" 
                                                        title="Lihat Detail"
                                                        data-id="<?= $id_jdwl ?>"
                                                        data-semester="<?= htmlspecialchars($semester_val) ?>"
                                                        data-kode="<?= htmlspecialchars($kode_matkul) ?>"
                                                        data-matkul="<?= htmlspecialchars($nama_matkul) ?>"
                                                        data-dosen="<?= htmlspecialchars($nama_user) ?>"
                                                        data-npp="<?= htmlspecialchars($npp_user) ?>"
                                                        data-jml-mhs="<?= $jml_mhs ?>"
                                                        onclick="showJadwalDetail(<?= $id_jdwl ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon">
                                                        <i class="fas fa-search fa-3x text-muted"></i>
                                                    </div>
                                                    <h2 class="mt-3">Data tidak ditemukan</h2>
                                                    <p class="lead">
                                                        Tidak ada data jadwal yang sesuai dengan filter yang dipilih.
                                                    </p>
                                                    <a href="jadwal_ujian.php" class="btn btn-primary mt-4">
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
                                    Menampilkan <?= $total_jadwal > 0 ? '1' : '0' ?> - <?= min($no-1, $total_jadwal) ?> dari <?= $total_jadwal ?> data
                                </div>
                                <?php if ($total_jadwal > 0): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination mb-0">
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                        <?php if ($total_jadwal > 10): ?>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <?php endif; ?>
                                        <?php if ($total_jadwal > 20): ?>
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

                            <!-- Semester Summary -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h4><i class="fas fa-chart-bar mr-2"></i>Rekap per Semester</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="align-middle">Semester</th>
                                                    <th class="text-center align-middle">Jumlah Jadwal</th>
                                                    <th class="text-center align-middle">Jumlah Dosen</th>
                                                    <th class="text-center align-middle">Total Mahasiswa</th>
                                                    <th class="text-center align-middle">Mata Kuliah Unik</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $query_semester_rekap = mysqli_query($koneksi, "
                                                    SELECT 
                                                        semester,
                                                        COUNT(*) as jumlah_jadwal,
                                                        COUNT(DISTINCT id_user) as jumlah_dosen,
                                                        SUM(jml_mhs) as total_mahasiswa,
                                                        COUNT(DISTINCT kode_matkul) as matkul_unik
                                                    FROM t_jadwal
                                                    GROUP BY semester
                                                    ORDER BY semester DESC
                                                ");
                                                
                                                if (mysqli_num_rows($query_semester_rekap) > 0):
                                                    while ($row_rekap = mysqli_fetch_assoc($query_semester_rekap)):
                                                ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <span class="badge badge-primary">
                                                            <?= htmlspecialchars($row_rekap['semester'] ?? '') ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-info">
                                                            <?= number_format($row_rekap['jumlah_jadwal'] ?? 0) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-success">
                                                            <?= number_format($row_rekap['jumlah_dosen'] ?? 0) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="font-weight-bold">
                                                            <?= number_format($row_rekap['total_mahasiswa'] ?? 0) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-warning">
                                                            <?= number_format($row_rekap['matkul_unik'] ?? 0) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endwhile;
                                                else:
                                                ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-3">
                                                        <div class="text-muted">
                                                            <i class="fas fa-database mr-1"></i> Tidak ada data rekap
                                                        </div>
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
            </div>
        </div>
    </section>
</div>
<!-- End Main Content -->

<!-- Modal for Jadwal Detail -->
<div class="modal fade" id="jadwalModal" tabindex="-1" role="dialog" aria-labelledby="jadwalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jadwalModalLabel">
                    <i class="fas fa-info-circle mr-2"></i>Detail Jadwal
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="jadwalDetailContent">
                <!-- Content will be loaded here via AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data jadwal...</p>
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
/* Custom styles untuk halaman jadwal */
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

.avatar-icon.bg-info {
    background-color: #17a2b8;
}

.avatar-icon.bg-secondary {
    background-color: #6c757d;
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

/* Responsive table */
.table-responsive {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    overflow: hidden;
}

#table-jadwal {
    margin-bottom: 0;
    min-width: 900px;
}

#table-jadwal th {
    white-space: nowrap;
    font-size: 0.85rem;
    padding: 12px 8px;
    border-bottom: 2px solid #e3e6f0 !important;
}

#table-jadwal td {
    padding: 10px 8px;
    vertical-align: middle;
    font-size: 0.9rem;
}

#table-jadwal .badge-pill {
    font-size: 0.8rem;
    padding: 0.35em 0.65em;
}

/* Mobile Responsiveness */
@media (max-width: 1199.98px) {
    #table-jadwal {
        min-width: 800px;
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
    #table-jadwal th:nth-child(5), /* Dosen Pengajar */
    #table-jadwal td:nth-child(5) {
        display: none;
    }
    
    /* Adjust table width */
    #table-jadwal {
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
    #table-jadwal th:nth-child(3), /* Kode Matkul */
    #table-jadwal td:nth-child(3) {
        display: none;
    }
    
    /* Show important columns only */
    #table-jadwal {
        min-width: 500px;
    }
    
    /* Adjust avatar size */
    .avatar-icon {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    /* Reduce font sizes */
    #table-jadwal td {
        font-size: 0.8rem;
        padding: 8px 5px;
    }
    
    #table-jadwal th {
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
    #table-jadwal th:nth-child(2), /* Semester */
    #table-jadwal td:nth-child(2) {
        display: none;
    }
    
    #table-jadwal {
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
    
    #table-jadwal {
        min-width: 100% !important;
    }
    
    /* Show all columns when printing */
    #table-jadwal th,
    #table-jadwal td {
        display: table-cell !important;
    }
}

/* Hover effects */
#table-jadwal tbody tr:hover {
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
    
    // Handle tombol detail (alternatif jika AJAX gagal)
    $('.btn-detail').click(function() {
        const id = $(this).data('id');
        const semester = $(this).data('semester');
        const kode = $(this).data('kode');
        const matkul = $(this).data('matkul');
        const dosen = $(this).data('dosen');
        const npp = $(this).data('npp');
        const jmlMhs = $(this).data('jml-mhs');
        
        // Tampilkan modal dengan data statis jika AJAX gagal
        $('#jadwalDetailContent').html(`
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Semester</label>
                        <p class="form-control-plaintext">${semester}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Kode Mata Kuliah</label>
                        <p class="form-control-plaintext">${kode}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Nama Mata Kuliah</label>
                        <p class="form-control-plaintext font-weight-bold">${matkul}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Dosen Pengajar</label>
                        <p class="form-control-plaintext">${dosen || 'Tidak Terdaftar'}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">NPP Dosen</label>
                        <p class="form-control-plaintext">${npp || '-'}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Jumlah Mahasiswa</label>
                        <div class="alert alert-light border">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Total Mahasiswa:</span>
                                <span class="badge badge-info p-2">${jmlMhs} Mahasiswa</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Catatan:</strong> Data detail lengkap dapat dilihat di halaman admin.
            </div>
        `);
        
        $('#jadwalModal').modal('show');
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

function showJadwalDetail(id) {
    $('#jadwalModal').modal('show');
    
    // Coba load data via AJAX
    $.ajax({
        url: '<?= BASE_URL ?>koordinator/jadwal/get_jadwal_detail.php',
        method: 'GET',
        data: { id: id },
        timeout: 5000, // 5 detik timeout
        success: function(response) {
            $('#jadwalDetailContent').html(response);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            
            // Tampilkan pesan error
            $('#jadwalDetailContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Gagal memuat data detail. Silakan coba lagi.
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync mr-1"></i> Refresh Halaman
                    </button>
                </div>
            `);
        }
    });
}

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
    const printContent = document.getElementById('table-jadwal').cloneNode(true);
    
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
                <title>Cetak Data Jadwal Ujian</title>
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
                <h2>Data Jadwal Ujian</h2>
                <p>Tanggal cetak: ${new Date().toLocaleDateString('id-ID')}</p>
                <p>Filter: Semester - ${$('select[name="semester"]').val() || 'Semua'}, 
                       Mata Kuliah - ${$('input[name="matkul"]').val() || 'Semua'}</p>
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
?>