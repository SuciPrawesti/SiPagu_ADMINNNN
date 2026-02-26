<?php
/**
 * MASTER DATA ADMIN - SiPagu
 * Lokasi: admin/master_data/data_admin.php
 * Fungsi: Read, Update, Delete data admin
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
        "SELECT COUNT(*) as count FROM t_transaksi_ujian WHERE id_user = $id_to_delete
         UNION ALL
         SELECT COUNT(*) FROM t_transaksi_pa_ta WHERE id_user = $id_to_delete
         UNION ALL
         SELECT COUNT(*) FROM t_jadwal WHERE id_user = $id_to_delete
         UNION ALL
         SELECT COUNT(*) FROM t_transaksi_honor_dosen WHERE id_user = $id_to_delete"
    );
    
    $has_relations = false;
    while ($row = mysqli_fetch_assoc($check_relations)) {
        if ($row['count'] > 0) {
            $has_relations = true;
            break;
        }
    }
    
    if ($has_relations) {
        $_SESSION['message'] = 'Data admin tidak dapat dihapus karena masih digunakan di tabel lain.';
        $_SESSION['message_type'] = 'danger';
    } else {
        $delete_query = mysqli_query($koneksi, 
            "DELETE FROM t_user WHERE id_user = $id_to_delete AND role_user = 'admin'"
        );
        
        if ($delete_query) {
            $_SESSION['message'] = 'Data admin berhasil dihapus.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal menghapus data admin: ' . mysqli_error($koneksi);
            $_SESSION['message_type'] = 'danger';
        }
    }
    
    header("Location: data_admin.php");
    exit();
}

// ==================== FUNGSI EDIT ====================
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = mysqli_query($koneksi, 
        "SELECT * FROM t_user WHERE id_user = $edit_id AND role_user = 'admin'"
    );
    
    if (!$edit_result) {
        $_SESSION['message'] = 'Error: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'danger';
    } elseif (mysqli_num_rows($edit_result) > 0) {
        $edit_mode = true;
        $edit_data = mysqli_fetch_assoc($edit_result);
    } else {
        $_SESSION['message'] = 'Data admin tidak ditemukan.';
        $_SESSION['message_type'] = 'danger';
        header("Location: data_admin.php");
        exit();
    }
}

// ==================== PROSES UPDATE ====================
if (isset($_POST['update_admin'])) {
    $id_user = (int)$_POST['id_user'];
    $npp_user = mysqli_real_escape_string($koneksi, $_POST['npp_user']);
    $nik_user = mysqli_real_escape_string($koneksi, $_POST['nik_user']);
    $npwp_user = mysqli_real_escape_string($koneksi, $_POST['npwp_user']);
    $norek_user = mysqli_real_escape_string($koneksi, $_POST['norek_user']);
    $nama_user = mysqli_real_escape_string($koneksi, $_POST['nama_user']);
    $nohp_user = mysqli_real_escape_string($koneksi, $_POST['nohp_user']);
    $honor_persks = (int)$_POST['honor_persks'];
    
    $update_query = mysqli_query($koneksi, 
        "UPDATE t_user SET 
            npp_user = '$npp_user',
            nik_user = '$nik_user',
            npwp_user = '$npwp_user',
            norek_user = '$norek_user',
            nama_user = '$nama_user',
            nohp_user = '$nohp_user',
            honor_persks = '$honor_persks'
        WHERE id_user = $id_user AND role_user = 'admin'"
    );
    
    if ($update_query) {
        $_SESSION['message'] = 'Data admin berhasil diperbarui.';
        $_SESSION['message_type'] = 'success';
        header("Location: data_admin.php");
        exit();
    } else {
        $message = 'Gagal memperbarui data admin: ' . mysqli_error($koneksi);
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

// Query data admin
$query = mysqli_query($koneksi, 
    "SELECT * FROM t_user WHERE role_user = 'admin' ORDER BY nama_user ASC"
);

// Cek jika query gagal
if (!$query) {
    $message = 'Error: ' . mysqli_error($koneksi);
    $message_type = 'danger';
}

// Hitung total admin untuk footer
$total_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_user WHERE role_user = 'admin'");
$total_data = mysqli_fetch_assoc($total_query);
$total_admin = $total_data['total'];
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
            <h1>Master Data Admin</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data Admin</div>
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
                    <?= $message ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Daftar Admin</h4>
                            <div class="card-header-action">
                                <a href="?refresh" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Refresh Data
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($edit_mode): ?>
                            <!-- Form Edit Admin -->
                            <div class="edit-form-container">
                                <h5>Edit Data Admin</h5>
                                <form method="POST" action="" class="edit-form">
                                    <input type="hidden" name="id_user" value="<?= $edit_data['id_user'] ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>NPP</label>
                                            <input type="text" name="npp_user" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['npp_user']) ?>" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Nama Lengkap</label>
                                            <input type="text" name="nama_user" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['nama_user']) ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>NIK</label>
                                            <input type="text" name="nik_user" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['nik_user']) ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>NPWP</label>
                                            <input type="text" name="npwp_user" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['npwp_user']) ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>No. Rekening</label>
                                            <input type="text" name="norek_user" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['norek_user']) ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>No. HP</label>
                                            <input type="text" name="nohp_user" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['nohp_user']) ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Honor Per SKS</label>
                                        <input type="number" name="honor_persks" class="form-control" 
                                               value="<?= htmlspecialchars($edit_data['honor_persks']) ?>" step="0.01">
                                    </div>
                                    
                                    <div class="form-group text-right">
                                        <a href="data_admin.php" class="btn btn-secondary">Batal</a>
                                        <button type="submit" name="update_admin" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <hr>
                            <?php endif; ?>

                            <!-- Tabel Data Admin -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-admin">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>NPP</th>
                                            <th>Nama</th>
                                            <th>NIK</th>
                                            <th>NPWP</th>
                                            <th>No. Rekening</th>
                                            <th>No. HP</th>
                                            <th>Honor/SKS</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($query && mysqli_num_rows($query) > 0): ?>
                                        <?php 
                                        // Reset pointer query untuk loop
                                        mysqli_data_seek($query, 0);
                                        $no = 1; 
                                        while ($row = mysqli_fetch_assoc($query)): 
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['npp_user']) ?></td>
                                            <td><?= htmlspecialchars($row['nama_user']) ?></td>
                                            <td><?= htmlspecialchars($row['nik_user']) ?></td>
                                            <td><?= htmlspecialchars($row['npwp_user']) ?></td>
                                            <td><?= htmlspecialchars($row['norek_user']) ?></td>
                                            <td><?= htmlspecialchars($row['nohp_user']) ?></td>
                                            <td>Rp <?= number_format($row['honor_persks'], 0, ',', '.') ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?edit_id=<?= $row['id_user'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete_id=<?= $row['id_user'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Yakin ingin menghapus data admin <?= htmlspecialchars($row['nama_user']) ?>?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">
                                                <?= $message ? $message : 'Tidak ada data admin' ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <small class="text-muted">
                                Total: <?= $total_admin ?> Admin
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

<!-- JavaScript khusus untuk halaman ini -->
<script>
$(document).ready(function() {
    // Konfirmasi sebelum hapus
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            e.preventDefault();
        }
    });
    
    // Initialize datatable jika diperlukan
    if ($.fn.DataTable) {
        $('#table-admin').DataTable({
            "pageLength": 25,
            "order": [[2, 'asc']]
        });
    }
});
</script>