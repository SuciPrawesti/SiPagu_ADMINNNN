<?php
/**
 * HONOR PANITIA - SiPagu (KOORDINATOR)
 * Halaman untuk melihat data honor panitia (READ ONLY)
 * Lokasi: koordinator/honor/honor_panitia.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Query data honor panitia dengan JOIN yang benar
$query = mysqli_query($koneksi, "
    SELECT 
        tu.*, 
        p.jbtn_pnt, 
        p.honor_std, 
        p.honor_p1, 
        p.honor_p2,
        u.nama_user, 
        u.npp_user
    FROM t_transaksi_ujian tu
    JOIN t_panitia p ON tu.id_panitia = p.id_pnt
    JOIN t_user u ON tu.id_user = u.id_user
    ORDER BY tu.id_tu DESC
");

// Hitung total data
$total_data = mysqli_num_rows($query);
mysqli_data_seek($query, 0); // Reset pointer

// Query untuk summary
$query_summary = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) as total_data,
        COUNT(DISTINCT p.jbtn_pnt) as total_jabatan,
        SUM(tu.jml_mhs) as total_mahasiswa,
        SUM(tu.jml_matkul) as total_matkul,
        SUM(p.honor_std) as total_honor_std
    FROM t_transaksi_ujian tu
    JOIN t_panitia p ON tu.id_panitia = p.id_pnt
");

$summary = mysqli_fetch_assoc($query_summary);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_koordinator.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Data Honor Panitia</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Honor Panitia</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Data Honor Panitia Ujian</h4>
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
                            <!-- Info Box -->
                            <div class="alert alert-info alert-has-icon">
                                <div class="alert-icon"><i class="fas fa-info-circle"></i></div>
                                <div class="alert-body">
                                    <div class="alert-title">Informasi</div>
                                    <p class="mb-0">
                                        <strong>Struktur Database:</strong> 
                                        <ul class="mb-0 pl-3">
                                            <li>Tabel <code>t_transaksi_ujian</code> menyimpan data jumlah mahasiswa, mata kuliah, dll</li>
                                            <li>Tabel <code>t_panitia</code> menyimpan <code>honor_std</code> berdasarkan jabatan</li>
                                            <li>Total honor = honor_std dari tabel panitia</li>
                                            <li>Database tidak memiliki kolom jumlah koreksi untuk honor tambahan</li>
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
                                                <?= number_format($summary['total_data'] ?? 0) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-success">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Jabatan Unik</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($summary['total_jabatan'] ?? 0) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-warning">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Mahasiswa</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($summary['total_mahasiswa'] ?? 0) ?>
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
                                                <?= 'Rp ' . number_format($summary['total_honor_std'] ?? 0, 0, ',', '.') ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Data Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-honor-panitia">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-center align-middle" style="width: 50px;">#</th>
                                            <th class="align-middle text-nowrap">Semester</th>
                                            <th class="align-middle">Panitia</th>
                                            <th class="align-middle">Jabatan</th>
                                            <th class="text-center align-middle" style="width: 80px;">Jml. Mhs</th>
                                            <th class="text-center align-middle" style="width: 80px;">Jml. Matkul</th>
                                            <th class="text-center align-middle" style="width: 120px;">Honor Standar</th>
                                            <th class="text-center align-middle" style="width: 140px;">Total Honor</th>
                                            <th class="text-center align-middle" style="width: 80px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        $total_mhs = 0;
                                        $total_matkul = 0;
                                        $total_honor = 0;
                                        
                                        if ($total_data > 0):
                                            while ($row = mysqli_fetch_assoc($query)): 
                                                // Ambil data dari query yang sudah difix
                                                $honor_std = $row['honor_std'] ?? 0;
                                                $total_honor_row = $honor_std;
                                                
                                                // Akumulasi total
                                                $total_mhs += $row['jml_mhs'] ?? 0;
                                                $total_matkul += $row['jml_matkul'] ?? 0;
                                                $total_honor += $total_honor_row;
                                                
                                                // Tentukan warna badge berdasarkan jabatan
                                                $jabatan = $row['jbtn_pnt'] ?? '';
                                                $jabatan_lower = strtolower($jabatan);
                                                $badge_color = 'primary';
                                                if (strpos($jabatan_lower, 'ketua') !== false) $badge_color = 'danger';
                                                elseif (strpos($jabatan_lower, 'sekretaris') !== false) $badge_color = 'success';
                                                elseif (strpos($jabatan_lower, 'bendahara') !== false) $badge_color = 'warning';
                                                elseif (strpos($jabatan_lower, 'anggota') !== false) $badge_color = 'info';
                                        ?>
                                        <tr>
                                            <td class="text-center align-middle">
                                                <span class="text-muted"><?= $no++ ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex flex-column">
                                                    <span class="badge badge-primary badge-pill" style="width: fit-content;">
                                                        <?= htmlspecialchars($row['semester'] ?? '') ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-icon-wrapper mr-2">
                                                        <div class="avatar-icon bg-success">
                                                            <span><?= strtoupper(substr($row['nama_user'] ?? '', 0, 1)) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="font-weight-bold text-dark text-truncate" style="max-width: 150px;">
                                                            <?= htmlspecialchars($row['nama_user'] ?? '') ?>
                                                        </span>
                                                        <small class="text-muted"><?= htmlspecialchars($row['npp_user'] ?? '') ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge badge-<?= $badge_color ?> badge-pill">
                                                    <?= htmlspecialchars($jabatan) ?>
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-info badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                    <?= number_format($row['jml_mhs'] ?? 0) ?>
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-secondary badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                    <?= number_format($row['jml_matkul'] ?? 0) ?>
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="text-success font-weight-bold">
                                                    Rp <?= number_format($honor_std, 0, ',', '.') ?>
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
                                                        data-npp="<?= htmlspecialchars($row['npp_user'] ?? '') ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_user'] ?? '') ?>"
                                                        data-jabatan="<?= htmlspecialchars($jabatan) ?>"
                                                        data-semester="<?= htmlspecialchars($row['semester'] ?? '') ?>"
                                                        data-jml-mhs="<?= $row['jml_mhs'] ?? 0 ?>"
                                                        data-jml-matkul="<?= $row['jml_matkul'] ?? 0 ?>"
                                                        data-jml-pgws-pagi="<?= $row['jml_pgws_pagi'] ?? 0 ?>"
                                                        data-jml-pgws-sore="<?= $row['jml_pgws_sore'] ?? 0 ?>"
                                                        data-jml-koor-pagi="<?= $row['jml_koor_pagi'] ?? 0 ?>"
                                                        data-jml-koor-sore="<?= $row['jml_koor_sore'] ?? 0 ?>"
                                                        data-honor-std="<?= $honor_std ?>"
                                                        data-honor-p1="<?= $row['honor_p1'] ?? 0 ?>"
                                                        data-honor-p2="<?= $row['honor_p2'] ?? 0 ?>"
                                                        data-total-honor="<?= $total_honor_row ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        
                                        <!-- Total Row -->
                                        <tr class="table-active font-weight-bold">
                                            <td colspan="4" class="text-right align-middle">TOTAL:</td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-info p-2"><?= number_format($total_mhs) ?></span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-secondary p-2"><?= number_format($total_matkul) ?></span>
                                            </td>
                                            <td class="text-center align-middle">-</td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-success p-2">Rp <?= number_format($total_honor, 0, ',', '.') ?></span>
                                            </td>
                                            <td class="text-center align-middle"></td>
                                        </tr>
                                        
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-5">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon">
                                                        <i class="fas fa-users fa-3x text-muted"></i>
                                                    </div>
                                                    <h2 class="mt-3">Data tidak ditemukan</h2>
                                                    <p class="lead">
                                                        Belum ada data honor panitia ujian.
                                                    </p>
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
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-info-circle mr-2"></i>Detail Honor Panitia Ujian
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">NPP Panitia</label>
                            <p id="detail-npp" class="form-control-plaintext"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Nama Panitia</label>
                            <p id="detail-nama" class="form-control-plaintext"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Jabatan</label>
                            <p id="detail-jabatan" class="form-control-plaintext"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Semester</label>
                            <p id="detail-semester" class="form-control-plaintext"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Jumlah Mahasiswa</label>
                            <p id="detail-jml-mhs" class="form-control-plaintext"></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Jumlah Mata Kuliah</label>
                            <p id="detail-jml-matkul" class="form-control-plaintext"></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Honor Standar</label>
                            <p id="detail-honor-std" class="form-control-plaintext font-weight-bold text-success"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Tambahan informasi dari database -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i>Detail Tambahan</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="font-weight-bold text-muted">PGWS Pagi</label>
                                            <p id="detail-jml-pgws-pagi" class="form-control-plaintext"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="font-weight-bold text-muted">PGWS Sore</label>
                                            <p id="detail-jml-pgws-sore" class="form-control-plaintext"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="font-weight-bold text-muted">Koordinator Pagi</label>
                                            <p id="detail-jml-koor-pagi" class="form-control-plaintext"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="font-weight-bold text-muted">Koordinator Sore</label>
                                            <p id="detail-jml-koor-sore" class="form-control-plaintext"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold text-muted">Perhitungan Honor</label>
                            <div class="alert alert-light border">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Honor standar jabatan:</span>
                                    <span id="calc-honor-std" class="font-weight-bold"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-muted">
                                    <span>Honor P1 (jika ada):</span>
                                    <span id="calc-honor-p1"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-muted">
                                    <span>Honor P2 (jika ada):</span>
                                    <span id="calc-honor-p2"></span>
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
/* Custom styles untuk halaman honor panitia */
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

.avatar-icon.bg-success {
    background-color: #28a745;
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

/* Responsive table */
.table-responsive {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    overflow: hidden;
}

#table-honor-panitia {
    margin-bottom: 0;
    min-width: 1000px;
}

#table-honor-panitia th {
    white-space: nowrap;
    font-size: 0.85rem;
    padding: 12px 8px;
    border-bottom: 2px solid #e3e6f0 !important;
}

#table-honor-panitia td {
    padding: 10px 8px;
    vertical-align: middle;
    font-size: 0.9rem;
}

#table-honor-panitia .badge-pill {
    font-size: 0.8rem;
    padding: 0.35em 0.65em;
}

/* Mobile Responsiveness */
@media (max-width: 1199.98px) {
    #table-honor-panitia {
        min-width: 900px;
    }
}

@media (max-width: 991.98px) {
    .card-header-action .btn {
        margin-bottom: 5px;
    }
}

@media (max-width: 768px) {
    /* Hide columns on tablets */
    #table-honor-panitia th:nth-child(6), /* Jml. Matkul */
    #table-honor-panitia td:nth-child(6) {
        display: none;
    }
    
    /* Adjust table width */
    #table-honor-panitia {
        min-width: 800px;
    }
    
    /* Summary cards - 2 per row */
    .col-xl-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 576px) {
    /* Hide more columns on mobile */
    #table-honor-panitia th:nth-child(5), /* Jml. Mhs */
    #table-honor-panitia td:nth-child(5) {
        display: none;
    }
    
    /* Show important columns only */
    #table-honor-panitia {
        min-width: 700px;
    }
    
    /* Adjust avatar size */
    .avatar-icon {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    /* Reduce font sizes */
    #table-honor-panitia td {
        font-size: 0.8rem;
        padding: 8px 5px;
    }
    
    #table-honor-panitia th {
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
    
    /* Modal adjustments */
    .modal-lg {
        margin: 10px;
    }
    
    .modal-content {
        padding: 10px;
    }
}

/* Extra small devices */
@media (max-width: 375px) {
    #table-honor-panitia th:nth-child(3), /* Panitia */
    #table-honor-panitia td:nth-child(3) {
        display: none;
    }
    
    #table-honor-panitia {
        min-width: 600px;
    }
}

/* Tooltip customization */
.tooltip {
    font-size: 0.875rem;
}

/* Print optimization */
@media print {
    .navbar, .sidebar, .card-header-action, .btn, .pagination, .modal,
    .alert, .card-statistic-1, .btn-detail {
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
    
    #table-honor-panitia {
        min-width: 100% !important;
    }
    
    /* Show all columns when printing */
    #table-honor-panitia th,
    #table-honor-panitia td {
        display: table-cell !important;
    }
}

/* Hover effects */
#table-honor-panitia tbody tr:hover {
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

/* Badge color variations */
.badge-danger { background-color: #dc3545 !important; }
.badge-success { background-color: #28a745 !important; }
.badge-warning { background-color: #ffc107 !important; color: #212529 !important; }
.badge-info { background-color: #17a2b8 !important; }
.badge-primary { background-color: #4361ee !important; }
.badge-secondary { background-color: #6c757d !important; }
</style>

<!-- Custom JavaScript untuk halaman ini -->
<script>
$(document).ready(function() {
    // Inisialisasi tooltip
    $('[data-toggle="tooltip"]').tooltip({
        trigger: 'hover',
        placement: 'top'
    });
    
    // Handle tombol detail
    $('.btn-detail').click(function() {
        const npp = $(this).data('npp');
        const nama = $(this).data('nama');
        const jabatan = $(this).data('jabatan');
        const semester = $(this).data('semester');
        const jmlMhs = $(this).data('jml-mhs');
        const jmlMatkul = $(this).data('jml-matkul');
        const jmlPgwsPagi = $(this).data('jml-pgws-pagi');
        const jmlPgwsSore = $(this).data('jml-pgws-sore');
        const jmlKoorPagi = $(this).data('jml-koor-pagi');
        const jmlKoorSore = $(this).data('jml-koor-sore');
        const honorStd = $(this).data('honor-std');
        const honorP1 = $(this).data('honor-p1');
        const honorP2 = $(this).data('honor-p2');
        const totalHonor = $(this).data('total-honor');
        
        // Update informasi dasar
        $('#detail-npp').text(npp);
        $('#detail-nama').text(nama);
        $('#detail-jabatan').text(jabatan);
        $('#detail-semester').text(semester);
        $('#detail-jml-mhs').text(formatNumber(jmlMhs) + ' Mahasiswa');
        $('#detail-jml-matkul').text(formatNumber(jmlMatkul) + ' Mata Kuliah');
        $('#detail-honor-std').text('Rp ' + formatRupiah(honorStd));
        
        // Update informasi tambahan
        $('#detail-jml-pgws-pagi').text(formatNumber(jmlPgwsPagi));
        $('#detail-jml-pgws-sore').text(formatNumber(jmlPgwsSore));
        $('#detail-jml-koor-pagi').text(formatNumber(jmlKoorPagi));
        $('#detail-jml-koor-sore').text(formatNumber(jmlKoorSore));
        
        // Update perhitungan
        $('#calc-honor-std').text('Rp ' + formatRupiah(honorStd));
        
        // Tampilkan honor P1 dan P2 jika ada
        if (honorP1 > 0) {
            $('#calc-honor-p1').text('Rp ' + formatRupiah(honorP1)).parent().removeClass('text-muted');
        } else {
            $('#calc-honor-p1').text('-').parent().addClass('text-muted');
        }
        
        if (honorP2 > 0) {
            $('#calc-honor-p2').text('Rp ' + formatRupiah(honorP2)).parent().removeClass('text-muted');
        } else {
            $('#calc-honor-p2').text('-').parent().addClass('text-muted');
        }
        
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

// Fungsi format angka biasa
function formatNumber(angka) {
    return parseFloat(angka).toLocaleString('id-ID');
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
    const printContent = document.getElementById('table-honor-panitia').cloneNode(true);
    
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
                <title>Cetak Data Honor Panitia</title>
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
                <h2>Data Honor Panitia Ujian</h2>
                <p>Tanggal cetak: ${new Date().toLocaleDateString('id-ID')}</p>
                <p>Total data: <?= number_format($summary['total_data'] ?? 0) ?></p>
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