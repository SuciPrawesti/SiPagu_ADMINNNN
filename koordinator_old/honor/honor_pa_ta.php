<?php
/**
 * HONOR PA/TA - SiPagu (KOORDINATOR)
 * Halaman untuk melihat data honor pembimbing PA/TA (READ ONLY)
 * Lokasi: koordinator/honor/honor_pa_ta.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Query data honor PA/TA
$query = mysqli_query($koneksi, "
    SELECT tpt.*, p.jbtn_pnt, p.honor_std, u.nama_user, u.npp_user
    FROM t_transaksi_pa_ta tpt
    JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
    JOIN t_user u ON tpt.id_user = u.id_user
    ORDER BY tpt.id_tpt DESC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_koordinator.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Data Honor Pembimbing PA/TA</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Honor PA/TA</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Data Honor Pembimbing Penulisan Akhir/Tugas Akhir</h4>
                        </div>
                        
                        <div class="card-body">
                            <!-- Info Box -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Informasi:</strong> Halaman ini hanya untuk melihat data honor pembimbing PA/TA.
                            </div>

                            <!-- Data Table -->
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Semester</th>
                                            <th>Periode Wisuda</th>
                                            <th>Dosen</th>
                                            <th>Prodi</th>
                                            <th>Mhs Prodi</th>
                                            <th>Mhs Bimbingan</th>
                                            <th>PGI 1</th>
                                            <th>PGI 2</th>
                                            <th>Jabatan</th>
                                            <th>Honor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($query)): 
                                            // Hitung total honor (contoh perhitungan sederhana)
                                            $total_honor = $row['honor_std'];
                                            // Tambahan untuk pembimbing berdasarkan jumlah mahasiswa
                                            if ($row['jml_mhs_bimbingan'] > 0) {
                                                $total_honor += ($row['jml_mhs_bimbingan'] * 50000); // contoh: 50k per mahasiswa
                                            }
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><span class="badge badge-primary"><?= $row['semester'] ?></span></td>
                                            <td><?= ucfirst($row['periode_wisuda']) ?></td>
                                            <td>
                                                <div><strong><?= htmlspecialchars($row['npp_user']) ?></strong></div>
                                                <small><?= htmlspecialchars($row['nama_user']) ?></small>
                                            </td>
                                            <td><?= $row['prodi'] ?></td>
                                            <td><?= $row['jml_mhs_prodi'] ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?= $row['jml_mhs_bimbingan'] ?>
                                                </span>
                                            </td>
                                            <td><?= $row['jml_pgji_1'] ?></td>
                                            <td><?= $row['jml_pgji_2'] ?></td>
                                            <td><?= htmlspecialchars($row['jbtn_pnt']) ?></td>
                                            <td>
                                                <strong class="text-success">
                                                    <?= number_format($total_honor, 0, ',', '.') ?>
                                                </strong>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        
                                        <?php if (mysqli_num_rows($query) == 0): ?>
                                        <tr>
                                            <td colspan="11" class="text-center">
                                                <div class="empty-state">
                                                    <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                                                    <h5>Data PA/TA tidak ditemukan</h5>
                                                    <p class="text-muted">Belum ada data honor pembimbing PA/TA</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary Cards -->
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-primary">
                                            <i class="fas fa-file-invoice"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Data</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= mysqli_num_rows($query) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-success">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Bimbingan</h4>
                                            </div>
                                            <div class="card-body">
                                                <?php 
                                                $query_bimbingan = mysqli_query($koneksi, "
                                                    SELECT SUM(jml_mhs_bimbingan) as total 
                                                    FROM t_transaksi_pa_ta
                                                ");
                                                $row_bimbingan = mysqli_fetch_assoc($query_bimbingan);
                                                echo number_format($row_bimbingan['total'] ?? 0);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-warning">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Dosen Pembimbing</h4>
                                            </div>
                                            <div class="card-body">
                                                <?php 
                                                $query_dosen = mysqli_query($koneksi, "
                                                    SELECT COUNT(DISTINCT id_user) as total 
                                                    FROM t_transaksi_pa_ta
                                                ");
                                                $row_dosen = mysqli_fetch_assoc($query_dosen);
                                                echo $row_dosen['total'];
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Per Program Studi -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h4>Rekap per Program Studi</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Program Studi</th>
                                                    <th>Jumlah Dosen</th>
                                                    <th>Jumlah Mahasiswa</th>
                                                    <th>Total Bimbingan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $query_prodi = mysqli_query($koneksi, "
                                                    SELECT 
                                                        prodi,
                                                        COUNT(DISTINCT id_user) as jumlah_dosen,
                                                        SUM(jml_mhs_prodi) as total_mhs,
                                                        SUM(jml_mhs_bimbingan) as total_bimbingan
                                                    FROM t_transaksi_pa_ta
                                                    GROUP BY prodi
                                                    ORDER BY prodi
                                                ");
                                                
                                                while ($row_prodi = mysqli_fetch_assoc($query_prodi)):
                                                ?>
                                                <tr>
                                                    <td><strong><?= $row_prodi['prodi'] ?></strong></td>
                                                    <td><?= $row_prodi['jumlah_dosen'] ?></td>
                                                    <td><?= number_format($row_prodi['total_mhs'] ?? 0) ?></td>
                                                    <td><?= number_format($row_prodi['total_bimbingan'] ?? 0) ?></td>
                                                </tr>
                                                <?php endwhile; ?>
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

<?php 
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>