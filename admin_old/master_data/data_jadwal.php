<?php
/**
 * MASTER DATA JADWAL - SiPagu
 * Lokasi: admin/master_data/data_jadwal.php
 * Fungsi: Read, Update, Delete data jadwal
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
    
    // Cek apakah ada relasi ke tabel lain sebelum hapus
    $check_relations = mysqli_query($koneksi, 
        "SELECT COUNT(*) as count FROM t_transaksi_honor_dosen WHERE id_jadwal = $id_to_delete"
    );
    
    if ($check_relations) {
        $row = mysqli_fetch_assoc($check_relations);
        
        if ($row['count'] > 0) {
            $_SESSION['message'] = 'Data jadwal tidak dapat dihapus karena masih digunakan di tabel Transaksi Honor Dosen.';
            $_SESSION['message_type'] = 'danger';
        } else {
            $delete_query = mysqli_query($koneksi, 
                "DELETE FROM t_jadwal WHERE id_jdwl = $id_to_delete"
            );
            
            if ($delete_query) {
                $_SESSION['message'] = 'Data jadwal berhasil dihapus.';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Gagal menghapus data jadwal: ' . mysqli_error($koneksi);
                $_SESSION['message_type'] = 'danger';
            }
        }
    } else {
        $_SESSION['message'] = 'Error checking relations: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'danger';
    }
    
    header("Location: data_jadwal.php");
    exit();
}

// ==================== FUNGSI EDIT ====================
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = mysqli_query($koneksi, 
        "SELECT j.*, u.nama_user 
         FROM t_jadwal j 
         LEFT JOIN t_user u ON j.id_user = u.id_user 
         WHERE j.id_jdwl = $edit_id"
    );
    
    if (!$edit_result) {
        $_SESSION['message'] = 'Error: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'danger';
    } elseif (mysqli_num_rows($edit_result) > 0) {
        $edit_mode = true;
        $edit_data = mysqli_fetch_assoc($edit_result);
    } else {
        $_SESSION['message'] = 'Data jadwal tidak ditemukan.';
        $_SESSION['message_type'] = 'danger';
        header("Location: data_jadwal.php");
        exit();
    }
}

// ==================== PROSES UPDATE ====================
if (isset($_POST['update_jadwal'])) {
    $id_jdwl = (int)$_POST['id_jdwl'];
    $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $kode_matkul = mysqli_real_escape_string($koneksi, $_POST['kode_matkul']);
    $nama_matkul = mysqli_real_escape_string($koneksi, $_POST['nama_matkul']);
    $id_user = (int)$_POST['id_user'];
    $jml_mhs = (int)$_POST['jml_mhs'];
    
    $update_query = mysqli_query($koneksi, 
        "UPDATE t_jadwal SET 
            semester = '$semester',
            kode_matkul = '$kode_matkul',
            nama_matkul = '$nama_matkul',
            id_user = $id_user,
            jml_mhs = $jml_mhs
        WHERE id_jdwl = $id_jdwl"
    );
    
    if ($update_query) {
        $_SESSION['message'] = 'Data jadwal berhasil diperbarui.';
        $_SESSION['message_type'] = 'success';
        header("Location: data_jadwal.php");
        exit();
    } else {
        $message = 'Gagal memperbarui data jadwal: ' . mysqli_error($koneksi);
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

// Query data jadwal dengan join ke user
$query = mysqli_query($koneksi, 
    "SELECT j.*, u.nama_user 
     FROM t_jadwal j 
     LEFT JOIN t_user u ON j.id_user = u.id_user 
     ORDER BY j.semester DESC, j.nama_matkul ASC"
);

// Cek jika query gagal
if (!$query) {
    $error_message = 'Error: ' . mysqli_error($koneksi);
    $message = $error_message;
    $message_type = 'danger';
}

// Query untuk dropdown dosen - reset pointer
$dosen_query_result = mysqli_query($koneksi, 
    "SELECT id_user, nama_user FROM t_user WHERE role_user IN ('dosen', 'staff', 'admin', 'koordinator') ORDER BY nama_user ASC"
);

// Hitung total untuk footer
$total_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_jadwal");
$total_data = mysqli_fetch_assoc($total_query);
$total_jadwal = $total_data ? $total_data['total'] : 0;

// Inisialisasi array untuk menyimpan data
$jadwal_data = [];
$row_count = 0;

if ($query && mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $jadwal_data[] = $row;
        $row_count++;
    }
}

// Reset pointer untuk loop nanti
if ($dosen_query_result) {
    mysqli_data_seek($dosen_query_result, 0);
}
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
            <h1>Master Data Jadwal</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data Jadwal</div>
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
                            <h4>Daftar Jadwal</h4>
                            <div class="card-header-action">
                                <a href="?refresh" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Refresh Data
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($edit_mode && !empty($edit_data)): ?>
                            <!-- Form Edit Jadwal -->
                            <div class="edit-form-container">
                                <h5>Edit Data Jadwal</h5>
                                <form method="POST" action="" class="edit-form">
                                    <input type="hidden" name="id_jdwl" value="<?= htmlspecialchars($edit_data['id_jdwl'] ?? '') ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Semester</label>
                                            <input type="text" name="semester" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['semester'] ?? '') ?>" 
                                                   placeholder="Contoh: 20231 (2023 Ganjil)" required>
                                            <small class="form-text text-muted">Format: Tahun + Semester (1=Ganjil, 2=Genap)</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Kode Mata Kuliah</label>
                                            <input type="text" name="kode_matkul" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['kode_matkul'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Nama Mata Kuliah</label>
                                            <input type="text" name="nama_matkul" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['nama_matkul'] ?? '') ?>" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Dosen Pengampu</label>
                                            <select name="id_user" class="form-control select2" required>
                                                <option value="">Pilih Dosen</option>
                                                <?php 
                                                if ($dosen_query_result && mysqli_num_rows($dosen_query_result) > 0) {
                                                    mysqli_data_seek($dosen_query_result, 0);
                                                    while ($dosen = mysqli_fetch_assoc($dosen_query_result)): 
                                                ?>
                                                <option value="<?= htmlspecialchars($dosen['id_user'] ?? '') ?>" 
                                                    <?= (isset($edit_data['id_user']) && $edit_data['id_user'] == $dosen['id_user']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($dosen['nama_user'] ?? '') ?>
                                                </option>
                                                <?php endwhile; } ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Jumlah Mahasiswa</label>
                                        <input type="number" name="jml_mhs" class="form-control" 
                                               value="<?= htmlspecialchars($edit_data['jml_mhs'] ?? 0) ?>" min="0" required>
                                    </div>
                                    
                                    <div class="form-group text-right">
                                        <a href="data_jadwal.php" class="btn btn-secondary">Batal</a>
                                        <button type="submit" name="update_jadwal" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <hr>
                            <?php endif; ?>

                            <!-- Tabel Data Jadwal -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-jadwal">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Semester</th>
                                            <th>Kode MK</th>
                                            <th>Mata Kuliah</th>
                                            <th>Dosen</th>
                                            <th>Jumlah Mhs</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($jadwal_data) && $row_count > 0): ?>
                                        <?php $no = 1; foreach ($jadwal_data as $row): ?>
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
                                            <td><?= isset($row['kode_matkul']) ? htmlspecialchars($row['kode_matkul']) : '-' ?></td>
                                            <td><?= isset($row['nama_matkul']) ? htmlspecialchars($row['nama_matkul']) : '-' ?></td>
                                            <td><?= isset($row['nama_user']) ? htmlspecialchars($row['nama_user']) : '-' ?></td>
                                            <td><?= isset($row['jml_mhs']) ? number_format($row['jml_mhs'], 0, ',', '.') : '0' ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if (isset($row['id_jdwl'])): ?>
                                                    <a href="?edit_id=<?= $row['id_jdwl'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete_id=<?= $row['id_jdwl'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Yakin ingin menghapus jadwal <?= isset($row['nama_matkul']) ? htmlspecialchars($row['nama_matkul']) : 'ini' ?>?')">
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
                                            <td colspan="7" class="text-center">
                                                Tidak ada data jadwal
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-danger">
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
                                Total: <?= $total_jadwal ?> Jadwal
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
        placeholder: 'Pilih Dosen',
        allowClear: true
    });
    
    // Initialize datatable
    if ($.fn.DataTable) {
        $('#table-jadwal').DataTable({
            "pageLength": 25,
            "order": [[1, 'desc']],
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