<?php
/**
 * REKAP BULANAN - SiPagu (KOORDINATOR)
 * Halaman untuk melihat rekap honor bulanan
 * Lokasi: koordinator/rekap/rekap_bulanan.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Ambil filter dari GET
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('n');

// Konversi bulan angka ke nama
$nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Query rekap honor dosen bulanan
$query_dosen = mysqli_query($koneksi, "
    SELECT 
        thd.bulan,
        COUNT(*) as jumlah_transaksi,
        COUNT(DISTINCT j.id_user) as jumlah_dosen,
        SUM(thd.sks_tempuh) as total_sks,
        SUM(thd.jml_tm) as total_tm
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    WHERE YEAR(STR_TO_DATE(CONCAT('01-', thd.bulan, '-', thd.semester), '%d-%M-%Y')) = '$tahun'
        AND MONTH(STR_TO_DATE(CONCAT('01-', thd.bulan, '-', thd.semester), '%d-%M-%Y')) = '$bulan'
    GROUP BY thd.bulan
    ORDER BY FIELD(thd.bulan, 
        'januari', 'februari', 'maret', 'april', 'mei', 'juni',
        'juli', 'agustus', 'september', 'oktober', 'november', 'desember')
");

// Query rekap per dosen
$query_per_dosen = mysqli_query($koneksi, "
    SELECT 
        u.npp_user,
        u.nama_user,
        COUNT(thd.id_thd) as jumlah_matkul,
        SUM(thd.sks_tempuh) as total_sks,
        SUM(thd.jml_tm) as total_tm
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    JOIN t_user u ON j.id_user = u.id_user
    WHERE YEAR(STR_TO_DATE(CONCAT('01-', thd.bulan, '-', thd.semester), '%d-%M-%Y')) = '$tahun'
        AND MONTH(STR_TO_DATE(CONCAT('01-', thd.bulan, '-', thd.semester), '%d-%M-%Y')) = '$bulan'
    GROUP BY u.id_user
    ORDER BY u.nama_user
");

// Query rekap per mata kuliah
$query_per_matkul = mysqli_query($koneksi, "
    SELECT 
        j.kode_matkul,
        j.nama_matkul,
        COUNT(DISTINCT j.id_user) as jumlah_dosen,
        COUNT(thd.id_thd) as jumlah_transaksi,
        SUM(thd.sks_tempuh) as total_sks
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    WHERE YEAR(STR_TO_DATE(CONCAT('01-', thd.bulan, '-', thd.semester), '%d-%M-%Y')) = '$tahun'
        AND MONTH(STR_TO_DATE(CONCAT('01-', thd.bulan, '-', thd.semester), '%d-%M-%Y')) = '$bulan'
    GROUP BY j.kode_matkul, j.nama_matkul
    ORDER BY j.kode_matkul
");

// Hitung total
$total_dosen = 0;
$total_sks = 0;
$total_tm = 0;
$total_transaksi = 0;

while ($row = mysqli_fetch_assoc($query_dosen)) {
    $total_dosen = $row['jumlah_dosen'];
    $total_sks = $row['total_sks'];
    $total_tm = $row['total_tm'];
    $total_transaksi = $row['jumlah_transaksi'];
}

// Reset pointer
mysqli_data_seek($query_dosen, 0);

// Query tahun tersedia
$query_tahun = mysqli_query($koneksi, "
    SELECT DISTINCT YEAR(STR_TO_DATE(CONCAT('01-', bulan, '-', semester), '%d-%M-%Y')) as tahun 
    FROM t_transaksi_honor_dosen 
    ORDER BY tahun DESC
    LIMIT 5
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_koordinator.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Rekap Honor Bulanan</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Rekap Bulanan</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Rekapitulasi Data Honor <?= $nama_bulan[$bulan] ?> <?= $tahun ?></h4>
                            <div class="card-header-action">
                                <div class="badge badge-info">
                                    <i class="fas fa-calendar-alt mr-1"></i> Bulanan
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
                                    <h4><i class="fas fa-filter mr-2"></i>Filter Rekap Bulanan</h4>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="filter-form">
                                        <div class="row align-items-end">
                                            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                                <div class="form-group mb-0">
                                                    <label class="form-label">Tahun</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text">
                                                                <i class="fas fa-calendar"></i>
                                                            </div>
                                                        </div>
                                                        <select name="tahun" class="form-control select2">
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
                                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                            <option value="<?= $m ?>" <?= $m == $bulan ? 'selected' : '' ?>>
                                                                <?= $nama_bulan[$m] ?>
                                                            </option>
                                                            <?php endfor; ?>
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
                                                        <a href="rekap_bulanan.php" class="btn btn-outline-secondary btn-lg">
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
                                    <div class="alert-title">Informasi Rekap Bulanan</div>
                                    <p class="mb-0">
                                        <strong>Fitur Rekap Bulanan:</strong> 
                                        <ul class="mb-0 pl-3">
                                            <li>Menampilkan data rekap honor dosen untuk periode bulan tertentu</li>
                                            <li>Statistik mencakup jumlah dosen, SKS, dan TM per bulan</li>
                                            <li>Rincian per dosen dan per mata kuliah tersedia di tabel terpisah</li>
                                            <li>Data dapat diekspor dalam format PDF, Excel, atau dicetak langsung</li>
                                        </ul>
                                    </p>
                                </div>
                            </div>

                            <!-- Summary Cards -->
                            <div class="row mb-4">
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-primary">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Periode</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= $nama_bulan[$bulan] ?> <?= $tahun ?>
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
                                                <h4>Total Dosen</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($total_dosen) ?>
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
                                                <h4>Total SKS</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($total_sks) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-info">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total TM</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($total_tm) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rekap per Dosen -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4><i class="fas fa-user-tie mr-2"></i>Rekap per Dosen</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th class="text-center align-middle" style="width: 50px;">#</th>
                                                    <th class="align-middle">NPP</th>
                                                    <th class="align-middle">Nama Dosen</th>
                                                    <th class="text-center align-middle">Jumlah Matkul</th>
                                                    <th class="text-center align-middle">Total SKS</th>
                                                    <th class="text-center align-middle">Total TM</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                mysqli_data_seek($query_per_dosen, 0);
                                                $no = 1;
                                                $total_matkul_dosen = 0;
                                                $total_sks_dosen = 0;
                                                $total_tm_dosen = 0;
                                                
                                                if (mysqli_num_rows($query_per_dosen) > 0):
                                                    while ($row = mysqli_fetch_assoc($query_per_dosen)): 
                                                        $total_matkul_dosen += $row['jumlah_matkul'];
                                                        $total_sks_dosen += $row['total_sks'];
                                                        $total_tm_dosen += $row['total_tm'];
                                                ?>
                                                <tr>
                                                    <td class="text-center align-middle">
                                                        <span class="text-muted"><?= $no++ ?></span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="font-weight-bold text-primary">
                                                            <?= htmlspecialchars($row['npp_user']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-icon-wrapper mr-2">
                                                                <div class="avatar-icon bg-success">
                                                                    <span><?= strtoupper(substr($row['nama_user'], 0, 1)) ?></span>
                                                                </div>
                                                            </div>
                                                            <span class="font-weight-bold text-dark">
                                                                <?= htmlspecialchars($row['nama_user']) ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-primary badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                            <?= $row['jumlah_matkul'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-warning badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                            <?= $row['total_sks'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-info badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                            <?= $row['total_tm'] ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                                
                                                <!-- Total Row -->
                                                <tr class="table-active font-weight-bold">
                                                    <td colspan="3" class="text-right align-middle">TOTAL:</td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-primary p-2"><?= number_format($total_matkul_dosen) ?></span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-warning p-2"><?= number_format($total_sks_dosen) ?></span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-info p-2"><?= number_format($total_tm_dosen) ?></span>
                                                    </td>
                                                </tr>
                                                
                                                <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-5">
                                                        <div class="empty-state">
                                                            <div class="empty-state-icon">
                                                                <i class="fas fa-user-slash fa-3x text-muted"></i>
                                                            </div>
                                                            <h2 class="mt-3">Data tidak ditemukan</h2>
                                                            <p class="lead">
                                                                Tidak ada data dosen untuk periode ini.
                                                            </p>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Rekap per Mata Kuliah -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4><i class="fas fa-book mr-2"></i>Rekap per Mata Kuliah</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th class="text-center align-middle" style="width: 50px;">#</th>
                                                    <th class="align-middle">Kode</th>
                                                    <th class="align-middle">Mata Kuliah</th>
                                                    <th class="text-center align-middle">Jumlah Dosen</th>
                                                    <th class="text-center align-middle">Jumlah Transaksi</th>
                                                    <th class="text-center align-middle">Total SKS</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                mysqli_data_seek($query_per_matkul, 0);
                                                $no = 1;
                                                $total_dosen_matkul = 0;
                                                $total_transaksi_matkul = 0;
                                                $total_sks_matkul = 0;
                                                
                                                if (mysqli_num_rows($query_per_matkul) > 0):
                                                    while ($row = mysqli_fetch_assoc($query_per_matkul)): 
                                                        $total_dosen_matkul += $row['jumlah_dosen'];
                                                        $total_transaksi_matkul += $row['jumlah_transaksi'];
                                                        $total_sks_matkul += $row['total_sks'];
                                                ?>
                                                <tr>
                                                    <td class="text-center align-middle">
                                                        <span class="text-muted"><?= $no++ ?></span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="badge badge-secondary">
                                                            <?= htmlspecialchars($row['kode_matkul']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="font-weight-bold text-dark">
                                                            <?= htmlspecialchars($row['nama_matkul']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-success badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                            <?= $row['jumlah_dosen'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-primary badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                            <?= $row['jumlah_transaksi'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-warning badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                            <?= $row['total_sks'] ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                                
                                                <!-- Total Row -->
                                                <tr class="table-active font-weight-bold">
                                                    <td colspan="3" class="text-right align-middle">TOTAL:</td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-success p-2"><?= number_format($total_dosen_matkul) ?></span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-primary p-2"><?= number_format($total_transaksi_matkul) ?></span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-warning p-2"><?= number_format($total_sks_matkul) ?></span>
                                                    </td>
                                                </tr>
                                                
                                                <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-5">
                                                        <div class="empty-state">
                                                            <div class="empty-state-icon">
                                                                <i class="fas fa-book fa-3x text-muted"></i>
                                                            </div>
                                                            <h2 class="mt-3">Data tidak ditemukan</h2>
                                                            <p class="lead">
                                                                Tidak ada data mata kuliah untuk periode ini.
                                                            </p>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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
                                                        <?= $nama_bulan[$bulan] ?> <?= $tahun ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Tanggal Generate</strong></td>
                                                    <td>:</td>
                                                    <td><?= date('d F Y H:i:s') ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Dosen</strong></td>
                                                    <td>:</td>
                                                    <td><?= number_format($total_dosen) ?> dosen</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Transaksi</strong></td>
                                                    <td>:</td>
                                                    <td><?= number_format($total_transaksi) ?> transaksi</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total SKS</strong></td>
                                                    <td>:</td>
                                                    <td><?= number_format($total_sks) ?> SKS</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total TM</strong></td>
                                                    <td>:</td>
                                                    <td><?= number_format($total_tm) ?> TM</td>
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
                                                Laporan rekap bulanan ini menyajikan data honor mengajar dosen untuk periode bulan tertentu.
                                            </p>
                                            <ul class="mb-0 pl-3">
                                                <li>Data diambil dari tabel transaksi honor dosen</li>
                                                <li>Jumlah SKS dan TM dihitung berdasarkan data aktual</li>
                                                <li>Rekap per dosen menunjukkan distribusi beban mengajar</li>
                                                <li>Rekap per mata kuliah menunjukkan popularitas mata kuliah</li>
                                                <li>Laporan ini untuk keperluan administratif dan monitoring</li>
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

<style>
/* Custom styles untuk halaman rekap bulanan */
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

/* Responsive table */
.table-responsive {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    overflow: hidden;
}

.table-responsive table {
    margin-bottom: 0;
    min-width: 800px;
}

.table-responsive th {
    white-space: nowrap;
    font-size: 0.85rem;
    padding: 12px 8px;
    border-bottom: 2px solid #e3e6f0 !important;
}

.table-responsive td {
    padding: 10px 8px;
    vertical-align: middle;
    font-size: 0.9rem;
}

.table-responsive .badge-pill {
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
@media (max-width: 991.98px) {
    .card-header-action .btn {
        margin-bottom: 5px;
    }
    
    .filter-form .btn-group {
        margin-top: 10px;
    }
}

@media (max-width: 768px) {
    /* Summary cards - 2 per row */
    .col-xl-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    
    /* Filter form adjustments */
    .filter-form .btn-group {
        margin-top: 15px;
    }
    
    .filter-form .btn-group .btn {
        width: 100%;
        margin-bottom: 5px;
    }
}

@media (max-width: 576px) {
    /* Adjust avatar size */
    .avatar-icon {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    /* Reduce font sizes */
    .table-responsive td {
        font-size: 0.8rem;
        padding: 8px 5px;
    }
    
    .table-responsive th {
        font-size: 0.75rem;
        padding: 10px 5px;
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
    
    /* Filter form buttons adjustments for mobile */
    .filter-form .col-lg-6 {
        margin-top: 15px;
    }
    
    .filter-form .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .filter-form .btn-group .btn {
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

/* Print optimization */
@media print {
    .navbar, .sidebar, .card-header-action, .btn, .pagination, .modal,
    .filter-form, .alert, .card-statistic-1, .card-header, .breadcrumb,
    .section-header {
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
    
    /* Show report title */
    .main-content h1 {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
    }
}

/* Hover effects */
.table-responsive tbody tr:hover {
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
    // Inisialisasi select2
    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Pilih...',
            theme: 'bootstrap4'
        });
    }
    
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
    window.print();
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
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>