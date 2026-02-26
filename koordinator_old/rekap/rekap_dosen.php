<?php
/**
 * REKAP PER DOSEN - SiPagu (KOORDINATOR)
 * Halaman untuk melihat rekap honor per dosen
 * Lokasi: koordinator/rekap/rekap_dosen.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Ambil filter dari GET
$dosen_id = $_GET['dosen'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');
$semester = $_GET['semester'] ?? '';

// Query daftar dosen
$query_dosen = mysqli_query($koneksi, "
    SELECT id_user, npp_user, nama_user 
    FROM t_user 
    WHERE role_user IN ('dosen', 'koordinator', 'staff')
    ORDER BY nama_user
");

// Query rekap per dosen
$where = "1=1";
if ($dosen_id) {
    $where .= " AND j.id_user = '$dosen_id'";
}
if ($tahun) {
    $where .= " AND thd.semester LIKE '$tahun%'";
}
if ($semester) {
    $where .= " AND thd.semester = '$semester'";
}

$query_rekap = mysqli_query($koneksi, "
    SELECT 
        u.id_user,
        u.npp_user,
        u.nama_user,
        COUNT(DISTINCT thd.id_thd) as jumlah_honor,
        COUNT(DISTINCT j.kode_matkul) as jumlah_matkul,
        SUM(thd.sks_tempuh) as total_sks,
        SUM(thd.jml_tm) as total_tm,
        GROUP_CONCAT(DISTINCT thd.bulan ORDER BY 
            FIELD(thd.bulan, 
                'januari', 'februari', 'maret', 'april', 'mei', 'juni',
                'juli', 'agustus', 'september', 'oktober', 'november', 'desember')
            SEPARATOR ', ') as bulan_aktif
    FROM t_user u
    LEFT JOIN t_jadwal j ON u.id_user = j.id_user
    LEFT JOIN t_transaksi_honor_dosen thd ON j.id_jdwl = thd.id_jadwal
    WHERE u.role_user IN ('dosen', 'koordinator', 'staff')
        AND $where
    GROUP BY u.id_user, u.npp_user, u.nama_user
    ORDER BY u.nama_user
");

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

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_koordinator.php';

// Hitung totals
$total_dosen = mysqli_num_rows($query_rekap);
mysqli_data_seek($query_rekap, 0);

$total_honor = 0;
$total_sks = 0;
$total_tm = 0;
$total_matkul = 0;

while ($row = mysqli_fetch_assoc($query_rekap)) {
    $total_honor += $row['jumlah_honor'];
    $total_sks += $row['total_sks'];
    $total_tm += $row['total_tm'];
    $total_matkul += $row['jumlah_matkul'];
}
mysqli_data_seek($query_rekap, 0);
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Rekap Honor per Dosen</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Rekap per Dosen</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Rekapitulasi Honor Berdasarkan Dosen</h4>
                            <div class="card-header-action">
                                <div class="badge badge-info">
                                    <i class="fas fa-user-tie mr-1"></i> Per Dosen
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
                                    <h4><i class="fas fa-filter mr-2"></i>Filter Rekap Dosen</h4>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="filter-form">
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 mb-3">
                                                <div class="form-group mb-0">
                                                    <label class="form-label">Dosen</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                        </div>
                                                        <select name="dosen" class="form-control select2">
                                                            <option value="">Semua Dosen</option>
                                                            <?php 
                                                            mysqli_data_seek($query_dosen, 0);
                                                            while ($dosen = mysqli_fetch_assoc($query_dosen)): 
                                                            ?>
                                                            <option value="<?= $dosen['id_user'] ?>" <?= $dosen['id_user'] == $dosen_id ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($dosen['nama_user']) ?> (<?= htmlspecialchars($dosen['npp_user']) ?>)
                                                            </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 mb-3">
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
                                            <div class="col-lg-4 col-md-6 mb-3">
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
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12 text-right">
                                                <div class="btn-group" role="group">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-filter mr-1"></i> Terapkan Filter
                                                    </button>
                                                    <a href="rekap_dosen.php" class="btn btn-outline-secondary">
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
                                    <div class="alert-title">Informasi Rekap Dosen</div>
                                    <p class="mb-0">
                                        <strong>Fitur Rekap per Dosen:</strong> 
                                        <ul class="mb-0 pl-3">
                                            <li>Menampilkan data rekap honor per dosen dengan berbagai filter</li>
                                            <li>Statistik mencakup jumlah honor, mata kuliah, SKS, dan TM</li>
                                            <li>Kolom "Bulan Aktif" menunjukkan bulan-bulan dosen menerima honor</li>
                                            <li>Klik tombol mata untuk melihat detail honor per dosen</li>
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
                                            <i class="fas fa-users"></i>
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
                                        <div class="card-icon bg-success">
                                            <i class="fas fa-file-invoice"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Honor</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($total_honor) ?>
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

                            <!-- Data Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-rekap-dosen">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-center align-middle" style="width: 50px;">#</th>
                                            <th class="align-middle">NPP</th>
                                            <th class="align-middle">Nama Dosen</th>
                                            <th class="text-center align-middle">Jumlah Honor</th>
                                            <th class="text-center align-middle">Jumlah Matkul</th>
                                            <th class="text-center align-middle">Total SKS</th>
                                            <th class="text-center align-middle">Total TM</th>
                                            <th class="align-middle">Bulan Aktif</th>
                                            <th class="text-center align-middle" style="width: 80px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        if ($total_dosen > 0):
                                            while ($row = mysqli_fetch_assoc($query_rekap)): 
                                                // Format bulan aktif
                                                $bulan_aktif = $row['bulan_aktif'];
                                                if ($bulan_aktif) {
                                                    $bulan_array = explode(', ', $bulan_aktif);
                                                    $bulan_aktif = implode(', ', array_map('ucfirst', $bulan_array));
                                                } else {
                                                    $bulan_aktif = '<span class="text-muted">-</span>';
                                                }
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
                                                        <div class="avatar-icon bg-primary">
                                                            <span><?= strtoupper(substr($row['nama_user'], 0, 1)) ?></span>
                                                        </div>
                                                    </div>
                                                    <span class="font-weight-bold text-dark">
                                                        <?= htmlspecialchars($row['nama_user']) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-success badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                    <?= $row['jumlah_honor'] ?>
                                                </span>
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
                                            <td class="align-middle">
                                                <small class="text-muted"><?= $bulan_aktif ?></small>
                                            </td>
                                            <td class="text-center align-middle">
                                                <a href="<?= BASE_URL ?>koordinator/honor/detail_honor.php?type=dosen&id=<?= $row['id_user'] ?>" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="Detail Honor"
                                                   data-toggle="tooltip">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        
                                        <!-- Total Row -->
                                        <tr class="table-active font-weight-bold">
                                            <td colspan="3" class="text-right align-middle">TOTAL:</td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-success p-2"><?= number_format($total_honor) ?></span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-primary p-2"><?= number_format($total_matkul) ?></span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-warning p-2"><?= number_format($total_sks) ?></span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-info p-2"><?= number_format($total_tm) ?></span>
                                            </td>
                                            <td class="text-center align-middle">-</td>
                                            <td class="text-center align-middle"></td>
                                        </tr>
                                        
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-5">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon">
                                                        <i class="fas fa-search fa-3x text-muted"></i>
                                                    </div>
                                                    <h2 class="mt-3">Data tidak ditemukan</h2>
                                                    <p class="lead">
                                                        Tidak ada data rekap dosen yang sesuai dengan filter yang dipilih.
                                                    </p>
                                                    <a href="rekap_dosen.php" class="btn btn-primary mt-4">
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
                                    Menampilkan <?= $total_dosen > 0 ? '1' : '0' ?> - <?= min($no-1, $total_dosen) ?> dari <?= $total_dosen ?> data
                                </div>
                                <?php if ($total_dosen > 0): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination mb-0">
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                        <?php if ($total_dosen > 10): ?>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <?php endif; ?>
                                        <?php if ($total_dosen > 20): ?>
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
                                                        ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Dosen</strong></td>
                                                    <td>:</td>
                                                    <td>
                                                        <?php 
                                                        if ($dosen_id) {
                                                            mysqli_data_seek($query_dosen, 0);
                                                            while ($dosen = mysqli_fetch_assoc($query_dosen)) {
                                                                if ($dosen['id_user'] == $dosen_id) {
                                                                    echo htmlspecialchars($dosen['nama_user']) . ' (' . htmlspecialchars($dosen['npp_user']) . ')';
                                                                    break;
                                                                }
                                                            }
                                                        } else {
                                                            echo "Semua Dosen";
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
                                                    <td><?= number_format($total_dosen) ?> dosen</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Honor</strong></td>
                                                    <td>:</td>
                                                    <td><?= number_format($total_honor) ?> transaksi</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total SKS</strong></td>
                                                    <td>:</td>
                                                    <td><?= number_format($total_sks) ?> SKS</td>
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
                                                Laporan rekap per dosen ini memberikan gambaran beban mengajar dan honor yang diterima dosen.
                                            </p>
                                            <ul class="mb-0 pl-3">
                                                <li>Data diambil berdasarkan transaksi honor dosen</li>
                                                <li>Jumlah honor menunjukkan frekuensi penerimaan honor</li>
                                                <li>Total SKS dan TM menunjukkan beban mengajar kumulatif</li>
                                                <li>Bulan aktif menunjukkan periode dosen menerima honor</li>
                                                <li>Gunakan tombol mata untuk melihat detail honor per dosen</li>
                                                <li>Laporan ini untuk monitoring distribusi beban mengajar</li>
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
/* Custom styles untuk halaman rekap dosen */
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

/* Responsive table */
.table-responsive {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    overflow: hidden;
}

#table-rekap-dosen {
    margin-bottom: 0;
    min-width: 1000px;
}

#table-rekap-dosen th {
    white-space: nowrap;
    font-size: 0.85rem;
    padding: 12px 8px;
    border-bottom: 2px solid #e3e6f0 !important;
}

#table-rekap-dosen td {
    padding: 10px 8px;
    vertical-align: middle;
    font-size: 0.9rem;
}

#table-rekap-dosen .badge-pill {
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
    #table-rekap-dosen {
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
    #table-rekap-dosen th:nth-child(7), /* Total TM */
    #table-rekap-dosen td:nth-child(7) {
        display: none;
    }
    
    #table-rekap-dosen th:nth-child(8), /* Bulan Aktif */
    #table-rekap-dosen td:nth-child(8) {
        display: none;
    }
    
    /* Adjust table width */
    #table-rekap-dosen {
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
    #table-rekap-dosen th:nth-child(6), /* Total SKS */
    #table-rekap-dosen td:nth-child(6) {
        display: none;
    }
    
    /* Show important columns only */
    #table-rekap-dosen {
        min-width: 500px;
    }
    
    /* Adjust avatar size */
    .avatar-icon {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    /* Reduce font sizes */
    #table-rekap-dosen td {
        font-size: 0.8rem;
        padding: 8px 5px;
    }
    
    #table-rekap-dosen th {
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
    #table-rekap-dosen th:nth-child(5), /* Jumlah Matkul */
    #table-rekap-dosen td:nth-child(5) {
        display: none;
    }
    
    #table-rekap-dosen {
        min-width: 400px;
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
    
    #table-rekap-dosen {
        min-width: 100% !important;
    }
    
    /* Show all columns when printing */
    #table-rekap-dosen th,
    #table-rekap-dosen td {
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
#table-rekap-dosen tbody tr:hover {
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