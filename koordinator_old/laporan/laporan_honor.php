<?php
/**
 * LAPORAN HONOR - SiPagu (KOORDINATOR)
 * Halaman untuk melihat laporan honor
 * Lokasi: koordinator/laporan/laporan_honor.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Ambil filter dari GET
$tahun = $_GET['tahun'] ?? date('Y');
$semester = $_GET['semester'] ?? '';
$bulan = $_GET['bulan'] ?? '';

// Konversi bulan angka ke nama
$nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Query laporan honor dosen
$where = "1=1";
if ($tahun) {
    $where .= " AND thd.semester LIKE '$tahun%'";
}
if ($semester) {
    $where .= " AND thd.semester = '$semester'";
}
if ($bulan) {
    $where .= " AND thd.bulan = '" . strtolower($nama_bulan[$bulan]) . "'";
}

$query = mysqli_query($koneksi, "
    SELECT 
        thd.semester,
        thd.bulan,
        u.npp_user,
        u.nama_user,
        j.kode_matkul,
        j.nama_matkul,
        thd.sks_tempuh,
        thd.jml_tm,
        u.honor_persks,
        (thd.jml_tm * thd.sks_tempuh * u.honor_persks) as honor_perhitungan
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    JOIN t_user u ON j.id_user = u.id_user
    WHERE $where
    ORDER BY thd.semester DESC, thd.bulan, u.nama_user
");

// Debug: cek jumlah row
$num_rows = mysqli_num_rows($query);

// Query untuk summary
$query_summary = mysqli_query($koneksi, "
    SELECT 
        COUNT(DISTINCT u.id_user) as jumlah_dosen,
        COUNT(thd.id_thd) as jumlah_transaksi,
        SUM(thd.sks_tempuh) as total_sks,
        SUM(thd.jml_tm) as total_tm,
        SUM(thd.jml_tm * thd.sks_tempuh * u.honor_persks) as total_honor
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    JOIN t_user u ON j.id_user = u.id_user
    WHERE $where
");

$summary = mysqli_fetch_assoc($query_summary);

// Query semester tersedia
$query_semester = mysqli_query($koneksi, "
    SELECT DISTINCT semester 
    FROM t_transaksi_honor_dosen 
    ORDER BY semester DESC
");

// Query tahun tersedia
$query_tahun = mysqli_query($koneksi, "
    SELECT DISTINCT SUBSTRING(semester, 1, 4) as tahun 
    FROM t_transaksi_honor_dosen 
    ORDER BY tahun DESC
");

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

// Reset pointer untuk digunakan kembali
if ($num_rows > 0) {
    mysqli_data_seek($query, 0);
}

// Hitung total data
$total_data = mysqli_num_rows($query);
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Laporan Honor Dosen</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Laporan Honor</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Laporan Honor Mengajar Dosen</h4>
                            <div class="card-header-action">
                                <div class="badge badge-warning">
                                    <i class="fas fa-chart-bar mr-1"></i> Laporan
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
                                    <h4><i class="fas fa-filter mr-2"></i>Filter Laporan</h4>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="filter-form">
                                        <div class="row">
                                            <div class="col-lg-4 col-md-4 mb-3">
                                                <div class="form-group mb-0">
                                                    <label class="form-label">Tahun</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text">
                                                                <i class="fas fa-calendar"></i>
                                                            </div>
                                                        </div>
                                                        <select name="tahun" class="form-control select2">
                                                            <option value="">Semua Tahun</option>
                                                            <?php 
                                                            mysqli_data_seek($query_tahun, 0);
                                                            while ($year = mysqli_fetch_assoc($query_tahun)): 
                                                            ?>
                                                            <option value="<?= htmlspecialchars($year['tahun']) ?>" <?= $year['tahun'] == $tahun ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($year['tahun']) ?>
                                                            </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-4 mb-3">
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
                                                            <option value="<?= htmlspecialchars($sem['semester']) ?>" <?= $sem['semester'] == $semester ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($sem['semester']) ?>
                                                            </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-4 mb-3">
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
                                                            <?php 
                                                            for ($m = 1; $m <= 12; $m++): 
                                                                $selected = ($bulan == $m) ? 'selected' : '';
                                                                $month_value = strtolower($nama_bulan[$m]);
                                                            ?>
                                                            <option value="<?= $m ?>" <?= $selected ?>>
                                                                <?= $nama_bulan[$m] ?>
                                                            </option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12 text-right">
                                                <div class="btn-group" role="group">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-filter mr-1"></i> Terapkan Filter
                                                    </button>
                                                    <a href="laporan_honor.php" class="btn btn-outline-secondary">
                                                        <i class="fas fa-sync-alt mr-1"></i> Reset
                                                    </a>
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
                                    <div class="alert-title">Informasi Laporan</div>
                                    <p class="mb-0">
                                        <strong>Format Laporan:</strong> 
                                        <ul class="mb-0 pl-3">
                                            <li>Laporan ini menampilkan data honor dosen berdasarkan periode yang dipilih</li>
                                            <li>Perhitungan honor: <code>Honor per SKS × SKS Tempuh × Jumlah TM</code></li>
                                            <li>Data dapat diekspor dalam format PDF, Excel, atau dicetak langsung</li>
                                            <li>Laporan ini bersifat read-only dan untuk informasi administratif</li>
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
                                                <?= number_format($summary['jumlah_dosen'] ?? 0) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-warning">
                                            <i class="fas fa-file-invoice"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Transaksi</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($summary['jumlah_transaksi'] ?? 0) ?>
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
                                                <?= 'Rp ' . number_format($summary['total_honor'] ?? 0, 0, ',', '.') ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Data Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-laporan">
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
                                            while ($row = mysqli_fetch_assoc($query)): 
                                                // Tentukan badge warna berdasarkan semester
                                                $semester_color = (strpos($row['semester'], '2') !== false) ? 'primary' : 'success';
                                                
                                                // Hitung honor
                                                $honor_persks = $row['honor_persks'];
                                                $total_honor_row = $row['honor_perhitungan'];
                                                
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
                                                        Tidak ada data laporan honor yang sesuai dengan filter yang dipilih.
                                                    </p>
                                                    <a href="laporan_honor.php" class="btn btn-primary mt-4">
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

                            <!-- Report Summary -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4><i class="fas fa-clipboard-list mr-2"></i>Ringkasan Laporan</h4>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td><strong>Periode Laporan</strong></td>
                                                    <td>:</td>
                                                    <td>
                                                        <?php 
                                                        if ($semester) {
                                                            echo "Semester $semester";
                                                        } elseif ($tahun) {
                                                            echo "Tahun $tahun";
                                                        } else {
                                                            echo "Semua Periode";
                                                        }
                                                        
                                                        if ($bulan) {
                                                            echo ", Bulan " . $nama_bulan[$bulan];
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Tanggal Generate</strong></td>
                                                    <td>:</td>
                                                    <td><?= date('d F Y H:i:s') ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Jumlah Data</strong></td>
                                                    <td>:</td>
                                                    <td><?= max(0, $no - 1) ?> record</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total TM</strong></td>
                                                    <td>:</td>
                                                    <td><?= number_format($total_tm) ?> TM</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total SKS</strong></td>
                                                    <td>:</td>
                                                    <td><?= number_format($total_sks) ?> SKS</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Honor</strong></td>
                                                    <td>:</td>
                                                    <td class="text-success">
                                                        <strong>Rp <?= number_format($total_honor, 0, ',', '.') ?></strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Dibuat Oleh</strong></td>
                                                    <td>:</td>
                                                    <td><?= htmlspecialchars($_SESSION['username'] ?? 'Koordinator') ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4><i class="fas fa-sticky-note mr-2"></i>Catatan Laporan</h4>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                Laporan ini berisi data honor mengajar dosen berdasarkan transaksi yang tercatat dalam sistem.
                                            </p>
                                            <ul class="mb-0 pl-3">
                                                <li>Data dihitung berdasarkan jumlah tatap muka (TM)</li>
                                                <li>Rumus perhitungan: <code>Honor = Honor per SKS × SKS Tempuh × Jumlah TM</code></li>
                                                <li><strong>Catatan Penting:</strong> Pastikan field <code>honor_persks</code> di tabel <code>t_user</code> sudah diisi dengan nilai yang sesuai</li>
                                                <li>Laporan ini bersifat read-only untuk keperluan administrasi</li>
                                                <li>Untuk perubahan data, hubungi administrator sistem</li>
                                            </ul>
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

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-info-circle mr-2"></i>Detail Laporan Honor Dosen
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
/* Custom styles untuk halaman laporan honor */
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

#table-laporan {
    margin-bottom: 0;
    min-width: 1000px; /* Minimum width untuk desktop */
}

#table-laporan th {
    white-space: nowrap;
    font-size: 0.85rem;
    padding: 12px 8px;
    border-bottom: 2px solid #e3e6f0 !important;
}

#table-laporan td {
    padding: 10px 8px;
    vertical-align: middle;
    font-size: 0.9rem;
}

#table-laporan .badge-pill {
    font-size: 0.8rem;
    padding: 0.35em 0.65em;
}

/* Filter form buttons */
.filter-form .btn-group {
    margin-top: 10px;
}

.filter-form .btn {
    padding: 8px 20px;
    font-size: 14px;
}

/* Mobile Responsiveness */
@media (max-width: 1199.98px) {
    #table-laporan {
        min-width: 900px;
    }
}

@media (max-width: 991.98px) {
    .card-header-action .btn {
        margin-bottom: 5px;
    }
    
    .filter-form .btn-group {
        margin-top: 15px;
    }
}

@media (max-width: 768px) {
    /* Hide columns on tablets */
    #table-laporan th:nth-child(3), /* Bulan */
    #table-laporan td:nth-child(3) {
        display: none;
    }
    
    #table-laporan th:nth-child(8), /* Honor/SKS */
    #table-laporan td:nth-child(8) {
        display: none;
    }
    
    /* Adjust table width */
    #table-laporan {
        min-width: 700px;
    }
    
    /* Summary cards - 2 per row */
    .col-xl-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    
    /* Filter form adjustments */
    .filter-form .btn-group {
        width: 100%;
    }
    
    .filter-form .btn {
        width: 50%;
    }
}

@media (max-width: 576px) {
    /* Hide more columns on mobile */
    #table-laporan th:nth-child(2), /* Semester */
    #table-laporan td:nth-child(2) {
        display: none;
    }
    
    /* Show important columns only */
    #table-laporan {
        min-width: 500px;
    }
    
    /* Adjust avatar size */
    .avatar-icon {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    /* Reduce font sizes */
    #table-laporan td {
        font-size: 0.8rem;
        padding: 8px 5px;
    }
    
    #table-laporan th {
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
    
    /* Filter form buttons full width on mobile */
    .filter-form .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .filter-form .btn {
        width: 100%;
        margin-bottom: 5px;
    }
    
    /* Adjust summary cards layout */
    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 15px;
    }
}

/* Extra small devices */
@media (max-width: 375px) {
    #table-laporan th:nth-child(7), /* SKS */
    #table-laporan td:nth-child(7) {
        display: none;
    }
    
    #table-laporan {
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
    .filter-form, .alert, .card-statistic-1, .card-header, .breadcrumb,
    .section-header, .report-summary-card, .note-card {
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
    
    #table-laporan {
        min-width: 100% !important;
    }
    
    /* Show all columns when printing */
    #table-laporan th,
    #table-laporan td {
        display: table-cell !important;
    }
    
    /* Show report title */
    .main-content h1 {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
    }
}

/* Hover effects */
#table-laporan tbody tr:hover {
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
    const printContent = document.getElementById('table-laporan').cloneNode(true);
    
    // Remove action column for printing
    const actionHeader = printContent.querySelector('th:last-child');
    const actionCells = printContent.querySelectorAll('td:last-child');
    
    if (actionHeader) actionHeader.remove();
    actionCells.forEach(cell => cell.remove());
    
    // Get filter information
    const semesterFilter = $('select[name="semester"]').val();
    const bulanFilter = $('select[name="bulan"]').val();
    const tahunFilter = $('select[name="tahun"]').val();
    
    let filterInfo = 'Semua Periode';
    if (semesterFilter) {
        filterInfo = 'Semester: ' + semesterFilter;
    } else if (tahunFilter) {
        filterInfo = 'Tahun: ' + tahunFilter;
    }
    
    if (bulanFilter) {
        const bulanNama = $('select[name="bulan"] option:selected').text();
        filterInfo += ', Bulan: ' + bulanNama;
    }
    
    // Open print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Cetak Laporan Honor Dosen</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                    .header h1 { margin: 0; color: #333; }
                    .header h2 { margin: 5px 0 0 0; font-size: 16px; color: #666; }
                    .info { margin-bottom: 20px; padding: 10px; background: #f5f5f5; border-radius: 5px; }
                    .info table { width: 100%; }
                    .info td { padding: 3px 0; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
                    th { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: bold; }
                    td { border: 1px solid #dee2e6; padding: 8px; }
                    .text-center { text-align: center; }
                    .text-right { text-align: right; }
                    .font-weight-bold { font-weight: bold; }
                    .badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; }
                    .badge-primary { background-color: #4361ee; color: white; }
                    .badge-success { background-color: #28a745; color: white; }
                    .badge-info { background-color: #17a2b8; color: white; }
                    .badge-secondary { background-color: #6c757d; color: white; }
                    .total-row { background-color: #e9ecef !important; font-weight: bold; }
                    @media print {
                        body { margin: 0; }
                        table { font-size: 10px; }
                        .header { page-break-after: avoid; }
                        table { page-break-inside: avoid; }
                    }
                    @page {
                        size: A4 landscape;
                        margin: 10mm;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>LAPORAN HONOR DOSEN</h1>
                    <h2>Sistem Pembayaran Honor Mengajar</h2>
                </div>
                
                <div class="info">
                    <table>
                        <tr>
                            <td style="width: 30%"><strong>Periode Laporan:</strong></td>
                            <td>${filterInfo}</td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Cetak:</strong></td>
                            <td>${new Date().toLocaleDateString('id-ID', { 
                                weekday: 'long', 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}</td>
                        </tr>
                        <tr>
                            <td><strong>Dicetak Oleh:</strong></td>
                            <td><?= htmlspecialchars($_SESSION['username'] ?? 'Koordinator') ?></td>
                        </tr>
                    </table>
                </div>
                
                ${printContent.outerHTML}
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #333;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%; text-align: center; padding-top: 40px;">
                                <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px;">
                                    Koordinator
                                </div>
                            </td>
                            <td style="width: 50%; text-align: center; padding-top: 40px;">
                                <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px;">
                                    Administrator
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                
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