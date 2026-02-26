<?php
/**
 * MASTER DATA PANITIA - SiPagu
 * Lokasi: admin/master_data/data_panitia.php
 * Fungsi: Read, Update, Delete data panitia
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
        "SELECT COUNT(*) as count FROM t_transaksi_ujian WHERE id_panitia = $id_to_delete
         UNION ALL
         SELECT COUNT(*) FROM t_transaksi_pa_ta WHERE id_panitia = $id_to_delete"
    );
    
    $has_relations = false;
    if ($check_relations) {
        while ($row = mysqli_fetch_assoc($check_relations)) {
            if (isset($row['count']) && $row['count'] > 0) {
                $has_relations = true;
                break;
            }
        }
    }
    
    if ($has_relations) {
        $_SESSION['message'] = 'Data panitia tidak dapat dihapus karena masih digunakan di tabel transaksi.';
        $_SESSION['message_type'] = 'danger';
    } else {
        $delete_query = mysqli_query($koneksi, 
            "DELETE FROM t_panitia WHERE id_pnt = $id_to_delete"
        );
        
        if ($delete_query) {
            $_SESSION['message'] = 'Data panitia berhasil dihapus.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal menghapus data panitia: ' . mysqli_error($koneksi);
            $_SESSION['message_type'] = 'danger';
        }
    }
    
    header("Location: data_panitia.php");
    exit();
}

// ==================== FUNGSI EDIT ====================
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = mysqli_query($koneksi, 
        "SELECT * FROM t_panitia WHERE id_pnt = $edit_id"
    );
    
    if (!$edit_result) {
        $_SESSION['message'] = 'Error: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'danger';
    } elseif (mysqli_num_rows($edit_result) > 0) {
        $edit_mode = true;
        $edit_data = mysqli_fetch_assoc($edit_result);
        
        // Debug: Tampilkan data edit
        if (empty($edit_data)) {
            $_SESSION['message'] = 'Data panitia ditemukan tetapi kosong.';
            $_SESSION['message_type'] = 'danger';
            header("Location: data_panitia.php");
            exit();
        }
    } else {
        $_SESSION['message'] = 'Data panitia tidak ditemukan.';
        $_SESSION['message_type'] = 'danger';
        header("Location: data_panitia.php");
        exit();
    }
}

// ==================== PROSES UPDATE ====================
if (isset($_POST['update_panitia'])) {
    $id_pnt = (int)$_POST['id_pnt'];
    $jbtn_pnt = mysqli_real_escape_string($koneksi, $_POST['jbtn_pnt']);
    $honor_std = (int)$_POST['honor_std'];
    $honor_p1 = isset($_POST['honor_p1']) ? (int)$_POST['honor_p1'] : 0;
    $honor_p2 = isset($_POST['honor_p2']) ? (int)$_POST['honor_p2'] : 0;
    
    $update_query = mysqli_query($koneksi, 
        "UPDATE t_panitia SET 
            jbtn_pnt = '$jbtn_pnt',
            honor_std = $honor_std,
            honor_p1 = $honor_p1,
            honor_p2 = $honor_p2
        WHERE id_pnt = $id_pnt"
    );
    
    if ($update_query) {
        $_SESSION['message'] = 'Data panitia berhasil diperbarui.';
        $_SESSION['message_type'] = 'success';
        header("Location: data_panitia.php");
        exit();
    } else {
        $message = 'Gagal memperbarui data panitia: ' . mysqli_error($koneksi);
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

// Query data panitia
$query = mysqli_query($koneksi, 
    "SELECT * FROM t_panitia ORDER BY jbtn_pnt ASC"
);

// Cek jika query gagal
if (!$query) {
    $message = 'Error: ' . mysqli_error($koneksi);
    $message_type = 'danger';
} else {
    // Simpan data ke array untuk digunakan nanti
    $panitia_data = [];
    if (mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_assoc($query)) {
            $panitia_data[] = $row;
        }
    }
}

// Hitung total panitia
$total_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_panitia");
$total_data = $total_query ? mysqli_fetch_assoc($total_query) : ['total' => 0];
$total_panitia = isset($total_data['total']) ? $total_data['total'] : 0;
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
            <h1>Master Data Panitia</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data Panitia</div>
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
                            <h4>Daftar Panitia</h4>
                            <div class="card-header-action">
                                <a href="?refresh" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Refresh Data
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($edit_mode && !empty($edit_data)): ?>
                            <!-- Form Edit Panitia -->
                            <div class="edit-form-container">
                                <h5>Edit Data Panitia</h5>
                                <form method="POST" action="" class="edit-form">
                                    <input type="hidden" name="id_pnt" value="<?= htmlspecialchars($edit_data['id_pnt'] ?? '') ?>">
                                    
                                    <div class="form-group">
                                        <label>Jabatan Panitia</label>
                                        <input type="text" name="jbtn_pnt" class="form-control" 
                                               value="<?= htmlspecialchars($edit_data['jbtn_pnt'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label>Honor Standar</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="number" name="honor_std" class="form-control" 
                                                       value="<?= isset($edit_data['honor_std']) ? (int)$edit_data['honor_std'] : 0 ?>" min="0" required>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Honor P1</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="number" name="honor_p1" class="form-control" 
                                                       value="<?= isset($edit_data['honor_p1']) ? (int)$edit_data['honor_p1'] : 0 ?>" min="0">
                                            </div>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Honor P2</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="number" name="honor_p2" class="form-control" 
                                                       value="<?= isset($edit_data['honor_p2']) ? (int)$edit_data['honor_p2'] : 0 ?>" min="0">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group text-right">
                                        <a href="data_panitia.php" class="btn btn-secondary">Batal</a>
                                        <button type="submit" name="update_panitia" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <hr>
                            <?php endif; ?>

                            <!-- Tabel Data Panitia -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-panitia">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Jabatan Panitia</th>
                                            <th>Honor Standar</th>
                                            <th>Honor P1</th>
                                            <th>Honor P2</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($panitia_data) && count($panitia_data) > 0): ?>
                                        <?php $no = 1; foreach ($panitia_data as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= isset($row['jbtn_pnt']) ? htmlspecialchars($row['jbtn_pnt']) : '-' ?></td>
                                            <td><?= isset($row['honor_std']) ? number_format((int)$row['honor_std'], 0, ',', '.') : '0' ?></td>
                                            <td><?= isset($row['honor_p1']) ? number_format((int)$row['honor_p1'], 0, ',', '.') : '0' ?></td>
                                            <td><?= isset($row['honor_p2']) ? number_format((int)$row['honor_p2'], 0, ',', '.') : '0' ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if (isset($row['id_pnt'])): ?>
                                                    <a href="?edit_id=<?= $row['id_pnt'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete_id=<?= $row['id_pnt'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Yakin ingin menghapus data panitia <?= isset($row['jbtn_pnt']) ? htmlspecialchars($row['jbtn_pnt']) : 'ini' ?>?')">
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
                                            <td colspan="6" class="text-center">
                                                Tidak ada data panitia
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-danger">
                                                Terjadi kesalahan saat mengambil data
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <small class="text-muted">
                                Total: <?= $total_panitia ?> Panitia
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
    // Initialize datatable
    if ($.fn.DataTable) {
        $('#table-panitia').DataTable({
            "pageLength": 25,
            "order": [[1, 'asc']],
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
    
    // Format input number dengan pemisah ribuan
    $('input[type="number"]').on('input', function() {
        var value = $(this).val().replace(/\./g, '');
        if (!isNaN(value)) {
            $(this).val(value);
        }
    });
});
</script>