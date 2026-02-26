<?php
/**
 * MASTER DATA TRANSAKSI HONOR DOSEN - SiPagu
 * Lokasi: admin/master_data/data_thd.php
 * Fungsi: Read, Update, Delete data transaksi honor dosen
 */

// Include konfigurasi dan autentikasi
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inisialisasi variabel
$message = '';
$message_type = '';
$edit_mode = false;
$edit_data = [];

// ==================== FUNGSI DELETE ====================
if (isset($_GET['delete_id'])) {
    $id_to_delete = (int)$_GET['delete_id'];
    
    $delete_query = mysqli_query($koneksi, 
        "DELETE FROM t_transaksi_honor_dosen WHERE id_thd = $id_to_delete"
    );
    
    if ($delete_query) {
        $_SESSION['message'] = 'Data transaksi honor dosen berhasil dihapus.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Gagal menghapus data transaksi honor dosen: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'danger';
    }
    
    header("Location: data_thd.php");
    exit();
}

// ==================== FUNGSI EDIT ====================
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = mysqli_query($koneksi, 
        "SELECT thd.*, j.kode_matkul, j.nama_matkul, j.jml_mhs, u.nama_user as nama_dosen
         FROM t_transaksi_honor_dosen thd
         LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
         LEFT JOIN t_user u ON j.id_user = u.id_user
         WHERE thd.id_thd = $edit_id"
    );
    
    if (!$edit_result) {
        $_SESSION['message'] = 'Error: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'danger';
    } elseif (mysqli_num_rows($edit_result) > 0) {
        $edit_mode = true;
        $edit_data = mysqli_fetch_assoc($edit_result);
        
        // Debug: Pastikan data tidak kosong
        if (empty($edit_data)) {
            $_SESSION['message'] = 'Data transaksi honor dosen ditemukan tetapi kosong.';
            $_SESSION['message_type'] = 'danger';
            header("Location: data_thd.php");
            exit();
        }
    } else {
        $_SESSION['message'] = 'Data transaksi honor dosen tidak ditemukan.';
        $_SESSION['message_type'] = 'danger';
        header("Location: data_thd.php");
        exit();
    }
}

// ==================== PROSES UPDATE ====================
if (isset($_POST['update_thd'])) {
    $id_thd = (int)$_POST['id_thd'];
    $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $bulan = mysqli_real_escape_string($koneksi, $_POST['bulan']);
    $id_jadwal = (int)$_POST['id_jadwal'];
    $jml_tm = (int)$_POST['jml_tm'];
    $sks_tempuh = (float)$_POST['sks_tempuh'];
    
    $update_query = mysqli_query($koneksi, 
        "UPDATE t_transaksi_honor_dosen SET 
            semester = '$semester',
            bulan = '$bulan',
            id_jadwal = $id_jadwal,
            jml_tm = $jml_tm,
            sks_tempuh = $sks_tempuh
        WHERE id_thd = $id_thd"
    );
    
    if ($update_query) {
        $_SESSION['message'] = 'Data transaksi honor dosen berhasil diperbarui.';
        $_SESSION['message_type'] = 'success';
        header("Location: data_thd.php");
        exit();
    } else {
        $message = 'Gagal memperbarui data transaksi honor dosen: ' . mysqli_error($koneksi);
        $message_type = 'danger';
    }
}

// Ambil pesan dari session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Query data transaksi honor dosen dengan join
$query = mysqli_query($koneksi, 
    "SELECT thd.*, j.kode_matkul, j.nama_matkul, j.jml_mhs, u.nama_user as nama_dosen
     FROM t_transaksi_honor_dosen thd
     LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
     LEFT JOIN t_user u ON j.id_user = u.id_user
     ORDER BY thd.semester DESC, thd.bulan ASC"
);

// Cek jika query gagal
if (!$query) {
    $error_message = 'Error: ' . mysqli_error($koneksi);
    $message = $error_message;
    $message_type = 'danger';
}

// Query untuk dropdown jadwal
$jadwal_query = mysqli_query($koneksi, 
    "SELECT j.*, u.nama_user 
     FROM t_jadwal j 
     LEFT JOIN t_user u ON j.id_user = u.id_user 
     ORDER BY j.semester DESC, j.nama_matkul ASC"
);

if (!$jadwal_query) {
    $jadwal_error = 'Error loading jadwal data: ' . mysqli_error($koneksi);
    if (empty($message)) {
        $message = $jadwal_error;
        $message_type = 'danger';
    }
}

// Query untuk enum bulan
$bulan_enum = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 
               'juli', 'agustus', 'september', 'oktober', 'november', 'desember'];

// Simpan data ke array untuk digunakan nanti
$thd_data = [];
$row_count = 0;

if ($query && mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $thd_data[] = $row;
        $row_count++;
    }
}

// Hitung total THD
$total_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_honor_dosen");
$total_data = $total_query ? mysqli_fetch_assoc($total_query) : ['total' => 0];
$total_thd = isset($total_data['total']) ? $total_data['total'] : 0;
?>

<!-- ==================== HEADER (Dari file include) ==================== -->
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- ==================== NAVBAR (Dari file include) ==================== -->
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<!-- ==================== SIDEBAR (Dari file include) ==================== -->
<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Master Data Transaksi Honor Dosen</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data Honor Dosen</div>
            </div>
        </div>

        <div class="section-body">
            <!-- Alert Message -->
            <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible show fade">
                <div class="alert-body">
                    <button class="close" data-dismiss="alert">
                        <span>×</span>
                    </button>
                    <?= htmlspecialchars($message) ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Daftar Transaksi Honor Dosen</h4>
                            <div class="card-header-action">
                                <a href="?refresh" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Refresh Data
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($edit_mode && !empty($edit_data)): ?>
                            <!-- Form Edit THD -->
                            <div class="edit-form-container">
                                <h5>Edit Data Transaksi Honor Dosen</h5>
                                <form method="POST" action="" class="edit-form">
                                    <input type="hidden" name="id_thd" value="<?= htmlspecialchars($edit_data['id_thd'] ?? '') ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Semester</label>
                                            <input type="text" name="semester" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['semester'] ?? '') ?>" 
                                                   placeholder="Contoh: 20231 (2023 Ganjil)" required>
                                            <small class="form-text text-muted">Format: Tahun + Semester (1=Ganjil, 2=Genap)</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Bulan</label>
                                            <select name="bulan" class="form-control" required>
                                                <option value="">Pilih Bulan</option>
                                                <?php foreach ($bulan_enum as $bulan): ?>
                                                <option value="<?= $bulan ?>" 
                                                    <?= (isset($edit_data['bulan']) && $edit_data['bulan'] == $bulan) ? 'selected' : '' ?>>
                                                    <?= ucfirst($bulan) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Jadwal</label>
                                        <select name="id_jadwal" class="form-control select2" required>
                                            <option value="">Pilih Jadwal</option>
                                            <?php 
                                            // Reset pointer jadwal query
                                            if ($jadwal_query && mysqli_num_rows($jadwal_query) > 0) {
                                                mysqli_data_seek($jadwal_query, 0);
                                                while ($jadwal = mysqli_fetch_assoc($jadwal_query)): 
                                            ?>
                                            <option value="<?= htmlspecialchars($jadwal['id_jdwl'] ?? '') ?>" 
                                                <?= (isset($edit_data['id_jadwal']) && $edit_data['id_jadwal'] == $jadwal['id_jdwl']) ? 'selected' : '' ?>>
                                                [<?= isset($jadwal['semester']) ? substr($jadwal['semester'], 0, 4) : '' ?> 
                                                <?= (isset($jadwal['semester']) && substr($jadwal['semester'], -1) == '1') ? 'Ganjil' : 'Genap' ?>] 
                                                <?= isset($jadwal['kode_matkul']) ? htmlspecialchars($jadwal['kode_matkul']) : '' ?> - 
                                                <?= isset($jadwal['nama_matkul']) ? htmlspecialchars($jadwal['nama_matkul']) : '' ?> 
                                                (<?= isset($jadwal['nama_user']) ? htmlspecialchars($jadwal['nama_user']) : '' ?>)
                                            </option>
                                            <?php endwhile; } ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Tatap Muka</label>
                                            <input type="number" name="jml_tm" class="form-control" 
                                                   value="<?= isset($edit_data['jml_tm']) ? htmlspecialchars($edit_data['jml_tm']) : 0 ?>" min="0" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>SKS Tempuh</label>
                                            <input type="number" name="sks_tempuh" class="form-control" 
                                                   value="<?= isset($edit_data['sks_tempuh']) ? htmlspecialchars($edit_data['sks_tempuh']) : 0 ?>" min="0" step="0.5" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group text-right">
                                        <a href="data_thd.php" class="btn btn-secondary">Batal</a>
                                        <button type="submit" name="update_thd" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <hr>
                            <?php endif; ?>

                            <!-- Tabel Data THD -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-thd">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Semester</th>
                                            <th>Bulan</th>
                                            <th>Dosen</th>
                                            <th>Mata Kuliah</th>
                                            <th>Tatap Muka</th>
                                            <th>SKS</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($thd_data) && $row_count > 0): ?>
                                        <?php $no = 1; foreach ($thd_data as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <?php 
                                                if (isset($row['semester']) && !empty($row['semester'])) {
                                                    $tahun = substr($row['semester'], 0, 4);
                                                    $semester_type = (substr($row['semester'], -1) == '1') ? 'Ganjil' : 'Genap';
                                                    echo htmlspecialchars($tahun . ' ' . $semester_type);
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td><?= isset($row['bulan']) ? ucfirst($row['bulan']) : '-' ?></td>
                                            <td><?= isset($row['nama_dosen']) ? htmlspecialchars($row['nama_dosen']) : '-' ?></td>
                                            <td>
                                                <?php if (isset($row['kode_matkul']) || isset($row['nama_matkul'])): ?>
                                                <strong><?= isset($row['kode_matkul']) ? htmlspecialchars($row['kode_matkul']) : '' ?></strong><br>
                                                <small><?= isset($row['nama_matkul']) ? htmlspecialchars($row['nama_matkul']) : '' ?></small>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </td>
                                            <td><?= isset($row['jml_tm']) ? htmlspecialchars($row['jml_tm']) . 'x' : '0x' ?></td>
                                            <td><?= isset($row['sks_tempuh']) ? htmlspecialchars($row['sks_tempuh']) . ' SKS' : '0 SKS' ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if (isset($row['id_thd'])): ?>
                                                    <a href="?edit_id=<?= $row['id_thd'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete_id=<?= $row['id_thd'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Yakin ingin menghapus data transaksi honor dosen ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php elseif ($query && mysqli_num_rows($query) === 0): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">
                                                Tidak ada data transaksi honor dosen
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-danger">
                                                <?= isset($error_message) ? htmlspecialchars($error_message) : 'Terjadi kesalahan saat mengambil data' ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <small class="text-muted">
                                Total: <?= $total_thd ?> Transaksi
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- ==================== FOOTER (Dari file include) ==================== -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- ==================== FOOTER SCRIPTS (Dari file include) ==================== -->
<?php include __DIR__ . '/../includes/footer_scripts.php'; ?>
<!-- Select2 JS -->
<script src="<?= ASSETS_URL ?>/modules/select2/dist/js/select2.full.min.js"></script>

<!-- JavaScript khusus untuk halaman ini -->
<script>
$(document).ready(function() {
    // Initialize select2
    $('.select2').select2({
        placeholder: 'Pilih Jadwal',
        allowClear: true
    });
    
    // Initialize datatable
    if ($.fn.DataTable) {
        $('#table-thd').DataTable({
            "pageLength": 25,
            "order": [[1, 'desc'], [2, 'asc']],
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Tidak ada data yang ditemukan",
                "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                "infoEmpty": "Tidak ada data",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
    }
});
</script>