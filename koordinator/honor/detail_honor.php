<?php
/**
 * DETAIL HONOR - SiPagu (KOORDINATOR)
 * Halaman untuk melihat detail data honor
 * Lokasi: koordinator/honor/detail_honor.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Ambil ID dari GET parameter
$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? ''; // 'dosen', 'panitia', 'pa_ta'

if (!$id || !$type) {
    header("Location: honor_dosen.php");
    exit;
}

// Query data berdasarkan type
$data = [];
$title = '';
$table = '';

switch ($type) {
    case 'dosen':
        $query = mysqli_query($koneksi, "
            SELECT thd.*, j.*, u.*
            FROM t_transaksi_honor_dosen thd
            JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
            JOIN t_user u ON j.id_user = u.id_user
            WHERE thd.id_thd = '$id'
        ");
        $title = "Detail Honor Dosen";
        $table = "t_transaksi_honor_dosen";
        break;
        
    case 'panitia':
        $query = mysqli_query($koneksi, "
            SELECT tu.*, p.*, u.*
            FROM t_transaksi_ujian tu
            JOIN t_panitia p ON tu.id_panitia = p.id_pnt
            JOIN t_user u ON tu.id_user = u.id_user
            WHERE tu.id_tu = '$id'
        ");
        $title = "Detail Honor Panitia";
        $table = "t_transaksi_ujian";
        break;
        
    case 'pa_ta':
        $query = mysqli_query($koneksi, "
            SELECT tpt.*, p.*, u.*
            FROM t_transaksi_pa_ta tpt
            JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
            JOIN t_user u ON tpt.id_user = u.id_user
            WHERE tpt.id_tpt = '$id'
        ");
        $title = "Detail Honor PA/TA";
        $table = "t_transaksi_pa_ta";
        break;
        
    default:
        header("Location: honor_dosen.php");
        exit;
}

if (mysqli_num_rows($query) == 0) {
    header("Location: honor_dosen.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_koordinator.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1><?= $title ?></h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
                <div class="breadcrumb-item"><a href="<?= BASE_URL ?>koordinator/honor/honor_dosen.php">Honor</a></div>
                <div class="breadcrumb-item"><?= $title ?></div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Informasi Detail</h4>
                            <div class="card-header-action">
                                <a href="javascript:history.back()" class="btn btn-primary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Info Box -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Informasi:</strong> Halaman ini hanya untuk melihat detail data honor.
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <!-- Data Display -->
                                    <div class="detail-card">
                                        <?php if ($type == 'dosen'): ?>
                                        <h5>Data Honor Mengajar</h5>
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="30%">ID Transaksi</th>
                                                <td><?= $data['id_thd'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Semester</th>
                                                <td><span class="badge badge-primary"><?= $data['semester'] ?></span></td>
                                            </tr>
                                            <tr>
                                                <th>Bulan</th>
                                                <td><?= ucfirst($data['bulan']) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Dosen</th>
                                                <td>
                                                    <strong><?= htmlspecialchars($data['npp_user']) ?></strong> - 
                                                    <?= htmlspecialchars($data['nama_user']) ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Mata Kuliah</th>
                                                <td>
                                                    <strong><?= htmlspecialchars($data['kode_matkul']) ?></strong> - 
                                                    <?= htmlspecialchars($data['nama_matkul']) ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Jumlah Tatap Muka</th>
                                                <td><?= $data['jml_tm'] ?> kali</td>
                                            </tr>
                                            <tr>
                                                <th>SKS Ditempuh</th>
                                                <td><?= $data['sks_tempuh'] ?> SKS</td>
                                            </tr>
                                            <tr>
                                                <th>Jumlah Mahasiswa</th>
                                                <td><?= $data['jml_mhs'] ?> orang</td>
                                            </tr>
                                        </table>
                                        
                                        <?php elseif ($type == 'panitia'): ?>
                                        <h5>Data Honor Panitia Ujian</h5>
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="30%">ID Transaksi</th>
                                                <td><?= $data['id_tu'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Semester</th>
                                                <td><span class="badge badge-primary"><?= $data['semester'] ?></span></td>
                                            </tr>
                                            <tr>
                                                <th>Panitia</th>
                                                <td>
                                                    <strong><?= htmlspecialchars($data['npp_user']) ?></strong> - 
                                                    <?= htmlspecialchars($data['nama_user']) ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Jabatan</th>
                                                <td><?= htmlspecialchars($data['jbtn_pnt']) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Honor Standar</th>
                                                <td>Rp <?= number_format($data['honor_std'], 0, ',', '.') ?></td>
                                            </tr>
                                            <tr>
                                                <th>Jumlah Mahasiswa</th>
                                                <td><?= $data['jml_mhs'] ?> orang</td>
                                            </tr>
                                            <tr>
                                                <th>Jumlah Mata Kuliah</th>
                                                <td><?= $data['jml_matkul'] ?> matkul</td>
                                            </tr>
                                            <tr>
                                                <th>Jumlah Koreksi</th>
                                                <td><?= $data['jml_koreksi'] ?> berkas</td>
                                            </tr>
                                            <tr>
                                                <th>Jumlah Mhs Prodi</th>
                                                <td><?= $data['jml_mhs_prodi'] ?> orang</td>
                                            </tr>
                                        </table>
                                        
                                        <?php elseif ($type == 'pa_ta'): ?>
                                        <h5>Data Honor Pembimbing PA/TA</h5>
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="30%">ID Transaksi</th>
                                                <td><?= $data['id_tpt'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Semester</th>
                                                <td><span class="badge badge-primary"><?= $data['semester'] ?></span></td>
                                            </tr>
                                            <tr>
                                                <th>Periode Wisuda</th>
                                                <td><?= ucfirst($data['periode_wisuda']) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Dosen Pembimbing</th>
                                                <td>
                                                    <strong><?= htmlspecialchars($data['npp_user']) ?></strong> - 
                                                    <?= htmlspecialchars($data['nama_user']) ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Program Studi</th>
                                                <td><?= $data['prodi'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Jabatan</th>
                                                <td><?= htmlspecialchars($data['jbtn_pnt']) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Honor Standar</th>
                                                <td>Rp <?= number_format($data['honor_std'], 0, ',', '.') ?></td>
                                            </tr>
                                            <tr>
                                                <th>Jumlah Mhs Prodi</th>
                                                <td><?= $data['jml_mhs_prodi'] ?> orang</td>
                                            </tr>
                                            <tr>
                                                <th>Jumlah Mhs Bimbingan</th>
                                                <td><?= $data['jml_mhs_bimbingan'] ?> orang</td>
                                            </tr>
                                            <tr>
                                                <th>Jumlah PGI 1</th>
                                                <td><?= $data['jml_pgji_1'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Jumlah PGI 2</th>
                                                <td><?= $data['jml_pgji_2'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Ketua PGI</th>
                                                <td><?= htmlspecialchars($data['ketua_pgji']) ?></td>
                                            </tr>
                                        </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <!-- Action Panel -->
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Informasi</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="text-center mb-4">
                                                <i class="fas fa-file-invoice-dollar fa-3x text-primary mb-3"></i>
                                                <h5>Status Data</h5>
                                                
                                                <!-- CATATAN: Database tidak memiliki kolom status untuk approval -->
                                                <!-- Jika ada kolom status, bisa ditampilkan di sini -->
                                                <div class="mt-3">
                                                    <span class="badge badge-success p-2">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        Data Valid
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="list-group">
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Tanggal Input</span>
                                                        <span class="font-weight-bold">
                                                            <?= date('d M Y', strtotime($data['created_at'] ?? date('Y-m-d'))) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <?php if (isset($data['updated_at'])): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Terakhir Diupdate</span>
                                                        <span class="font-weight-bold">
                                                            <?= date('d M Y', strtotime($data['updated_at'])) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mt-4">
                                                <p class="text-muted small">
                                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                                    Data ini telah diverifikasi oleh sistem.
                                                    Untuk perubahan data, hubungi administrator.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- History (Jika ada) -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h4>Catatan</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">
                                        <!-- CATATAN: Database tidak memiliki kolom catatan untuk honor -->
                                        Tidak ada catatan tambahan. Data honor ini dihitung berdasarkan aturan standar yang berlaku.
                                    </p>
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
.detail-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.detail-card h5 {
    color: #2d3748;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #667eea;
}

.table-bordered th {
    background-color: #f8fafc;
    font-weight: 500;
}

.table-bordered td {
    background-color: #fff;
}

.list-group-item {
    border: none;
    border-bottom: 1px solid #eef2f7;
    padding: 12px 0;
}

.list-group-item:last-child {
    border-bottom: none;
}
</style>

<?php 
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>