<?php
/**
 * MASTER DATA TRANSAKSI PA/TA - SiPagu
 * Lokasi: admin/master_data/data_tpata.php
 * Fungsi: Read, Update, Delete data transaksi PA/TA
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
        "DELETE FROM t_transaksi_pa_ta WHERE id_tpt = $id_to_delete"
    );
    
    if ($delete_query) {
        $_SESSION['message'] = 'Data transaksi PA/TA berhasil dihapus.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Gagal menghapus data transaksi PA/TA: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'danger';
    }
    
    header("Location: data_tpata.php");
    exit();
}

// ==================== FUNGSI EDIT ====================
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = mysqli_query($koneksi, 
        "SELECT tpt.*, u.nama_user as nama_dosen, p.jbtn_pnt
         FROM t_transaksi_pa_ta tpt
         LEFT JOIN t_user u ON tpt.id_user = u.id_user
         LEFT JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
         WHERE tpt.id_tpt = $edit_id"
    );
    
    if (!$edit_result) {
        $_SESSION['message'] = 'Error: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'danger';
    } elseif (mysqli_num_rows($edit_result) > 0) {
        $edit_mode = true;
        $edit_data = mysqli_fetch_assoc($edit_result);
        
        // Debug: Pastikan data tidak kosong
        if (empty($edit_data)) {
            $_SESSION['message'] = 'Data transaksi PA/TA ditemukan tetapi kosong.';
            $_SESSION['message_type'] = 'danger';
            header("Location: data_tpata.php");
            exit();
        }
    } else {
        $_SESSION['message'] = 'Data transaksi PA/TA tidak ditemukan.';
        $_SESSION['message_type'] = 'danger';
        header("Location: data_tpata.php");
        exit();
    }
}

// ==================== PROSES UPDATE ====================
if (isset($_POST['update_tpata'])) {
    $id_tpt = (int)$_POST['id_tpt'];
    $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $periode_wisuda = mysqli_real_escape_string($koneksi, $_POST['periode_wisuda']);
    $id_user = (int)$_POST['id_user'];
    $id_panitia = (int)$_POST['id_panitia'];
    $jml_mhs_prodi = (int)$_POST['jml_mhs_prodi'];
    $jml_mhs_bimbingan = (int)$_POST['jml_mhs_bimbingan'];
    $prodi = mysqli_real_escape_string($koneksi, $_POST['prodi']);
    $jml_pgji_1 = isset($_POST['jml_pgji_1']) ? (int)$_POST['jml_pgji_1'] : 0;
    $jml_pgji_2 = isset($_POST['jml_pgji_2']) ? (int)$_POST['jml_pgji_2'] : 0;
    $ketua_pgji = mysqli_real_escape_string($koneksi, $_POST['ketua_pgji']);
    
    $update_query = mysqli_query($koneksi, 
        "UPDATE t_transaksi_pa_ta SET 
            semester = '$semester',
            periode_wisuda = '$periode_wisuda',
            id_user = $id_user,
            id_panitia = $id_panitia,
            jml_mhs_prodi = $jml_mhs_prodi,
            jml_mhs_bimbingan = $jml_mhs_bimbingan,
            prodi = '$prodi',
            jml_pgji_1 = $jml_pgji_1,
            jml_pgji_2 = $jml_pgji_2,
            ketua_pgji = '$ketua_pgji'
        WHERE id_tpt = $id_tpt"
    );
    
    if ($update_query) {
        $_SESSION['message'] = 'Data transaksi PA/TA berhasil diperbarui.';
        $_SESSION['message_type'] = 'success';
        header("Location: data_tpata.php");
        exit();
    } else {
        $message = 'Gagal memperbarui data transaksi PA/TA: ' . mysqli_error($koneksi);
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

// Query data transaksi PA/TA dengan join
$query = mysqli_query($koneksi, 
    "SELECT tpt.*, u.nama_user as nama_dosen, p.jbtn_pnt
     FROM t_transaksi_pa_ta tpt
     LEFT JOIN t_user u ON tpt.id_user = u.id_user
     LEFT JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
     ORDER BY tpt.semester DESC, tpt.periode_wisuda ASC"
);

// Cek jika query gagal
if (!$query) {
    $error_message = 'Error: ' . mysqli_error($koneksi);
    $message = $error_message;
    $message_type = 'danger';
}

// Query untuk dropdown dosen
$dosen_query = mysqli_query($koneksi, 
    "SELECT id_user, nama_user FROM t_user WHERE role_user IN ('dosen', 'staff', 'admin', 'koordinator') ORDER BY nama_user ASC"
);

// Query untuk dropdown panitia
$panitia_query = mysqli_query($koneksi, 
    "SELECT id_pnt, jbtn_pnt FROM t_panitia ORDER BY jbtn_pnt ASC"
);

// Data enum periode wisuda
$periode_wisuda_enum = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 
                       'juli', 'agustus', 'september', 'oktober', 'november', 'desember'];

// Data prodi
$prodi_list = ['Teknik Informatika', 'Sistem Informasi', 'Manajemen Informatika', 
               'Teknik Komputer', 'Ilmu Komputer'];

// Simpan data ke array untuk digunakan nanti
$tpata_data = [];
$row_count = 0;

if ($query && mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $tpata_data[] = $row;
        $row_count++;
    }
}

// Hitung total untuk stats card
$total_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_pa_ta");
$total_data = $total_query ? mysqli_fetch_assoc($total_query) : ['total' => 0];
$total_tpata = isset($total_data['total']) ? $total_data['total'] : 0;

$dosen_count = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_user) as total FROM t_transaksi_pa_ta");
$dosen_data = $dosen_count ? mysqli_fetch_assoc($dosen_count) : ['total' => 0];
$total_dosen = isset($dosen_data['total']) ? $dosen_data['total'] : 0;

$mhs_query = mysqli_query($koneksi, "SELECT COALESCE(SUM(jml_mhs_prodi), 0) as total FROM t_transaksi_pa_ta");
$mhs_data = $mhs_query ? mysqli_fetch_assoc($mhs_query) : ['total' => 0];
$total_mhs = isset($mhs_data['total']) ? $mhs_data['total'] : 0;

$bimbingan_query = mysqli_query($koneksi, "SELECT COALESCE(SUM(jml_mhs_bimbingan), 0) as total FROM t_transaksi_pa_ta");
$bimbingan_data = $bimbingan_query ? mysqli_fetch_assoc($bimbingan_query) : ['total' => 0];
$total_bimbingan = isset($bimbingan_data['total']) ? $bimbingan_data['total'] : 0;
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
            <h1>Master Data Transaksi PA/TA</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data PA/TA</div>
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
                    <!-- Stats Card -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="stats-card">
                                <div class="stats-title">Total Transaksi</div>
                                <div class="stats-value">
                                    <?= number_format($total_tpata) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="stats-card">
                                <div class="stats-title">Total Dosen</div>
                                <div class="stats-value">
                                    <?= number_format($total_dosen) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="stats-card">
                                <div class="stats-title">Total Mahasiswa</div>
                                <div class="stats-value">
                                    <?= number_format($total_mhs) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="stats-card">
                                <div class="stats-title">Total Bimbingan</div>
                                <div class="stats-value">
                                    <?= number_format($total_bimbingan) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4>Daftar Transaksi PA/TA</h4>
                            <div class="card-header-action">
                                <a href="?refresh" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Refresh Data
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($edit_mode && !empty($edit_data)): ?>
                            <!-- Form Edit TPTA -->
                            <div class="edit-form-container">
                                <h5>Edit Data Transaksi PA/TA</h5>
                                <form method="POST" action="" class="edit-form">
                                    <input type="hidden" name="id_tpt" value="<?= htmlspecialchars($edit_data['id_tpt'] ?? '') ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Semester</label>
                                            <input type="text" name="semester" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['semester'] ?? '') ?>" 
                                                   placeholder="Contoh: 20231 (2023 Ganjil)" required>
                                            <small class="form-text text-muted">Format: Tahun + Semester (1=Ganjil, 2=Genap)</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Periode Wisuda</label>
                                            <select name="periode_wisuda" class="form-control" required>
                                                <option value="">Pilih Periode Wisuda</option>
                                                <?php foreach ($periode_wisuda_enum as $periode): ?>
                                                <option value="<?= $periode ?>" 
                                                    <?= (isset($edit_data['periode_wisuda']) && $edit_data['periode_wisuda'] == $periode) ? 'selected' : '' ?>>
                                                    <?= ucfirst($periode) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Dosen</label>
                                            <select name="id_user" class="form-control select2" required>
                                                <option value="">Pilih Dosen</option>
                                                <?php 
                                                if ($dosen_query && mysqli_num_rows($dosen_query) > 0) {
                                                    mysqli_data_seek($dosen_query, 0);
                                                    while ($dosen = mysqli_fetch_assoc($dosen_query)): 
                                                ?>
                                                <option value="<?= htmlspecialchars($dosen['id_user'] ?? '') ?>" 
                                                    <?= (isset($edit_data['id_user']) && $edit_data['id_user'] == $dosen['id_user']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($dosen['nama_user'] ?? '') ?>
                                                </option>
                                                <?php endwhile; } ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Jabatan Panitia</label>
                                            <select name="id_panitia" class="form-control select2" required>
                                                <option value="">Pilih Jabatan Panitia</option>
                                                <?php 
                                                if ($panitia_query && mysqli_num_rows($panitia_query) > 0) {
                                                    mysqli_data_seek($panitia_query, 0);
                                                    while ($panitia = mysqli_fetch_assoc($panitia_query)): 
                                                ?>
                                                <option value="<?= htmlspecialchars($panitia['id_pnt'] ?? '') ?>" 
                                                    <?= (isset($edit_data['id_panitia']) && $edit_data['id_panitia'] == $panitia['id_pnt']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($panitia['jbtn_pnt'] ?? '') ?>
                                                </option>
                                                <?php endwhile; } ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Mahasiswa Prodi</label>
                                            <input type="number" name="jml_mhs_prodi" class="form-control" 
                                                   value="<?= isset($edit_data['jml_mhs_prodi']) ? htmlspecialchars($edit_data['jml_mhs_prodi']) : 0 ?>" min="0" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Mahasiswa Bimbingan</label>
                                            <input type="number" name="jml_mhs_bimbingan" class="form-control" 
                                                   value="<?= isset($edit_data['jml_mhs_bimbingan']) ? htmlspecialchars($edit_data['jml_mhs_bimbingan']) : 0 ?>" min="0" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Program Studi</label>
                                            <select name="prodi" class="form-control" required>
                                                <option value="">Pilih Program Studi</option>
                                                <?php foreach ($prodi_list as $prodi): ?>
                                                <option value="<?= $prodi ?>" 
                                                    <?= (isset($edit_data['prodi']) && $edit_data['prodi'] == $prodi) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($prodi) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Ketua Penguji</label>
                                            <input type="text" name="ketua_pgji" class="form-control" 
                                                   value="<?= isset($edit_data['ketua_pgji']) ? htmlspecialchars($edit_data['ketua_pgji']) : '' ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Penguji 1</label>
                                            <input type="number" name="jml_pgji_1" class="form-control" 
                                                   value="<?= isset($edit_data['jml_pgji_1']) ? htmlspecialchars($edit_data['jml_pgji_1']) : 0 ?>" min="0">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Penguji 2</label>
                                            <input type="number" name="jml_pgji_2" class="form-control" 
                                                   value="<?= isset($edit_data['jml_pgji_2']) ? htmlspecialchars($edit_data['jml_pgji_2']) : 0 ?>" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group text-right">
                                        <a href="data_tpata.php" class="btn btn-secondary">Batal</a>
                                        <button type="submit" name="update_tpata" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <hr>
                            <?php endif; ?>

                            <!-- Tabel Data TPTA -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-tpata">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Semester</th>
                                            <th>Periode</th>
                                            <th>Dosen</th>
                                            <th>Jabatan</th>
                                            <th>Mahasiswa</th>
                                            <th>Bimbingan</th>
                                            <th>Prodi</th>
                                            <th>Penguji</th>
                                            <th>Ketua</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($tpata_data) && $row_count > 0): ?>
                                        <?php $no = 1; foreach ($tpata_data as $row): ?>
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
                                            <td>
                                                <?php if (isset($row['periode_wisuda'])): ?>
                                                <span class="badge badge-info">
                                                    <?= ucfirst($row['periode_wisuda']) ?>
                                                </span>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= isset($row['nama_dosen']) ? htmlspecialchars($row['nama_dosen']) : '-' ?></td>
                                            <td>
                                                <?php if (isset($row['jbtn_pnt'])): ?>
                                                <span class="badge badge-warning">
                                                    <?= htmlspecialchars($row['jbtn_pnt']) ?>
                                                </span>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <strong><?= isset($row['jml_mhs_prodi']) ? number_format((int)$row['jml_mhs_prodi'], 0, ',', '.') : '0' ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-success">
                                                    <?= isset($row['jml_mhs_bimbingan']) ? number_format((int)$row['jml_mhs_bimbingan'], 0, ',', '.') : '0' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if (isset($row['prodi']) && !empty($row['prodi'])) {
                                                    $prodi_class = '';
                                                    switch($row['prodi']) {
                                                        case 'Teknik Informatika': $prodi_class = 'prodi-si'; break;
                                                        case 'Sistem Informasi': $prodi_class = 'prodi-ti'; break;
                                                        case 'Manajemen Informatika': $prodi_class = 'prodi-mi'; break;
                                                        default: $prodi_class = 'badge-secondary';
                                                    }
                                                ?>
                                                <span class="prodi-badge <?= $prodi_class ?>">
                                                    <?= substr($row['prodi'], 0, 2) ?>
                                                </span>
                                                <?php } else { ?>
                                                <span class="prodi-badge badge-secondary">-</span>
                                                <?php } ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <span class="badge badge-primary" title="Penguji 1">
                                                        P1: <?= isset($row['jml_pgji_1']) ? (int)$row['jml_pgji_1'] : '0' ?>
                                                    </span>
                                                    <span class="badge badge-secondary" title="Penguji 2">
                                                        P2: <?= isset($row['jml_pgji_2']) ? (int)$row['jml_pgji_2'] : '0' ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (isset($row['ketua_pgji']) && !empty($row['ketua_pgji'])): ?>
                                                <span class="badge badge-danger">
                                                    <?= htmlspecialchars($row['ketua_pgji']) ?>
                                                </span>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if (isset($row['id_tpt'])): ?>
                                                    <a href="?edit_id=<?= $row['id_tpt'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete_id=<?= $row['id_tpt'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Yakin ingin menghapus data transaksi PA/TA ini?')">
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
                                            <td colspan="11" class="text-center">
                                                Tidak ada data transaksi PA/TA
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center text-danger">
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
                                Total: <?= $total_tpata ?> Transaksi
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
        placeholder: function() {
            return $(this).data('placeholder') || 'Pilih opsi';
        },
        allowClear: true
    });
    
    // Initialize datatable
    if ($.fn.DataTable) {
        $('#table-tpata').DataTable({
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
    
    // Format input number
    $('input[type="number"]').on('input', function() {
        var value = $(this).val().replace(/\./g, '');
        if (!isNaN(value) && value !== '') {
            $(this).val(parseInt(value));
        }
    });
});
</script>