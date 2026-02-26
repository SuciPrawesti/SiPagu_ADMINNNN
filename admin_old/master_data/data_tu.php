<?php
/**
 * MASTER DATA TRANSAKSI UJIAN - SiPagu
 * Lokasi: admin/master_data/data_tu.php
 * Fungsi: Read, Update, Delete data transaksi ujian
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
        "DELETE FROM t_transaksi_ujian WHERE id_tu = $id_to_delete"
    );
    
    if ($delete_query) {
        $_SESSION['message'] = 'Data transaksi ujian berhasil dihapus.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Gagal menghapus data transaksi ujian: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'danger';
    }
    
    header("Location: data_tu.php");
    exit();
}

// ==================== FUNGSI EDIT ====================
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = mysqli_query($koneksi, 
        "SELECT tu.*, u.nama_user as nama_dosen, p.jbtn_pnt
         FROM t_transaksi_ujian tu
         LEFT JOIN t_user u ON tu.id_user = u.id_user
         LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
         WHERE tu.id_tu = $edit_id"
    );
    
    if (!$edit_result) {
        $_SESSION['message'] = 'Error: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'danger';
    } elseif (mysqli_num_rows($edit_result) > 0) {
        $edit_mode = true;
        $edit_data = mysqli_fetch_assoc($edit_result);
        
        // Debug: Pastikan data tidak kosong
        if (empty($edit_data)) {
            $_SESSION['message'] = 'Data transaksi ujian ditemukan tetapi kosong.';
            $_SESSION['message_type'] = 'danger';
            header("Location: data_tu.php");
            exit();
        }
    } else {
        $_SESSION['message'] = 'Data transaksi ujian tidak ditemukan.';
        $_SESSION['message_type'] = 'danger';
        header("Location: data_tu.php");
        exit();
    }
}

// ==================== PROSES UPDATE ====================
if (isset($_POST['update_tu'])) {
    $id_tu = (int)$_POST['id_tu'];
    $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $id_user = (int)$_POST['id_user'];
    $id_panitia = (int)$_POST['id_panitia'];
    $jml_mhs_prodi = (int)$_POST['jml_mhs_prodi'];
    $jml_mhs = (int)$_POST['jml_mhs'];
    $jml_koreksi = isset($_POST['jml_koreksi']) ? (int)$_POST['jml_koreksi'] : 0;
    $jml_matkul = isset($_POST['jml_matkul']) ? (int)$_POST['jml_matkul'] : 0;
    $jml_pgws_pagi = isset($_POST['jml_pgws_pagi']) ? (int)$_POST['jml_pgws_pagi'] : 0;
    $jml_pgws_sore = isset($_POST['jml_pgws_sore']) ? (int)$_POST['jml_pgws_sore'] : 0;
    $jml_koor_pagi = isset($_POST['jml_koor_pagi']) ? (int)$_POST['jml_koor_pagi'] : 0;
    $jml_koor_sore = isset($_POST['jml_koor_sore']) ? (int)$_POST['jml_koor_sore'] : 0;
    
    $update_query = mysqli_query($koneksi, 
        "UPDATE t_transaksi_ujian SET 
            semester = '$semester',
            id_user = $id_user,
            id_panitia = $id_panitia,
            jml_mhs_prodi = $jml_mhs_prodi,
            jml_mhs = $jml_mhs,
            jml_koreksi = $jml_koreksi,
            jml_matkul = $jml_matkul,
            jml_pgws_pagi = $jml_pgws_pagi,
            jml_pgws_sore = $jml_pgws_sore,
            jml_koor_pagi = $jml_koor_pagi,
            jml_koor_sore = $jml_koor_sore
        WHERE id_tu = $id_tu"
    );
    
    if ($update_query) {
        $_SESSION['message'] = 'Data transaksi ujian berhasil diperbarui.';
        $_SESSION['message_type'] = 'success';
        header("Location: data_tu.php");
        exit();
    } else {
        $message = 'Gagal memperbarui data transaksi ujian: ' . mysqli_error($koneksi);
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

// Query data transaksi ujian dengan join
$query = mysqli_query($koneksi, 
    "SELECT tu.*, u.nama_user as nama_dosen, p.jbtn_pnt
     FROM t_transaksi_ujian tu
     LEFT JOIN t_user u ON tu.id_user = u.id_user
     LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
     ORDER BY tu.semester DESC"
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

// Simpan data ke array untuk digunakan nanti
$tu_data = [];
$row_count = 0;

if ($query && mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $tu_data[] = $row;
        $row_count++;
    }
}

// Hitung total summary
$summary_query = mysqli_query($koneksi, 
    "SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(SUM(jml_mhs), 0) as total_mahasiswa,
        COALESCE(SUM(jml_koreksi), 0) as total_koreksi,
        COALESCE(SUM(jml_matkul), 0) as total_matkul,
        COALESCE(SUM(jml_pgws_pagi + jml_pgws_sore), 0) as total_pengawas,
        COALESCE(SUM(jml_koor_pagi + jml_koor_sore), 0) as total_koordinator
     FROM t_transaksi_ujian"
);

if ($summary_query) {
    $summary = mysqli_fetch_assoc($summary_query);
} else {
    $summary = [
        'total_transaksi' => 0,
        'total_mahasiswa' => 0,
        'total_koreksi' => 0,
        'total_matkul' => 0,
        'total_pengawas' => 0,
        'total_koordinator' => 0
    ];
}

// Hitung total untuk footer
$total_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_transaksi_ujian");
$total_data = $total_query ? mysqli_fetch_assoc($total_query) : ['total' => 0];
$total_tu = isset($total_data['total']) ? $total_data['total'] : 0;
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
            <h1>Master Data Transaksi Ujian</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data Transaksi Ujian</div>
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
                    <!-- Summary Card -->
                    <div class="summary-card">
                        <div class="summary-title">
                            <i class="fas fa-chart-bar"></i> Ringkasan Statistik
                        </div>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <div class="summary-label">Total Transaksi</div>
                                <div class="summary-value"><?= number_format($summary['total_transaksi'] ?? 0) ?></div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-label">Total Mahasiswa</div>
                                <div class="summary-value"><?= number_format($summary['total_mahasiswa'] ?? 0) ?></div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-label">Total Koreksi</div>
                                <div class="summary-value"><?= number_format($summary['total_koreksi'] ?? 0) ?></div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-label">Total Mata Kuliah</div>
                                <div class="summary-value"><?= number_format($summary['total_matkul'] ?? 0) ?></div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-label">Total Pengawas</div>
                                <div class="summary-value"><?= number_format($summary['total_pengawas'] ?? 0) ?></div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-label">Total Koordinator</div>
                                <div class="summary-value"><?= number_format($summary['total_koordinator'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4>Daftar Transaksi Ujian</h4>
                            <div class="card-header-action">
                                <a href="?refresh" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Refresh Data
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($edit_mode && !empty($edit_data)): ?>
                            <!-- Form Edit TU -->
                            <div class="edit-form-container">
                                <h5>Edit Data Transaksi Ujian</h5>
                                <form method="POST" action="" class="edit-form">
                                    <input type="hidden" name="id_tu" value="<?= htmlspecialchars($edit_data['id_tu'] ?? '') ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Semester</label>
                                            <input type="text" name="semester" class="form-control" 
                                                   value="<?= htmlspecialchars($edit_data['semester'] ?? '') ?>" 
                                                   placeholder="Contoh: 20231 (2023 Ganjil)" required>
                                            <small class="form-text text-muted">Format: Tahun + Semester (1=Ganjil, 2=Genap)</small>
                                        </div>
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
                                    </div>
                                    
                                    <div class="form-row">
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
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Mahasiswa Prodi</label>
                                            <input type="number" name="jml_mhs_prodi" class="form-control" 
                                                   value="<?= isset($edit_data['jml_mhs_prodi']) ? htmlspecialchars($edit_data['jml_mhs_prodi']) : 0 ?>" min="0" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Mahasiswa</label>
                                            <input type="number" name="jml_mhs" class="form-control" 
                                                   value="<?= isset($edit_data['jml_mhs']) ? htmlspecialchars($edit_data['jml_mhs']) : 0 ?>" min="0" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Koreksi</label>
                                            <input type="number" name="jml_koreksi" class="form-control" 
                                                   value="<?= isset($edit_data['jml_koreksi']) ? htmlspecialchars($edit_data['jml_koreksi']) : 0 ?>" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Mata Kuliah</label>
                                            <input type="number" name="jml_matkul" class="form-control" 
                                                   value="<?= isset($edit_data['jml_matkul']) ? htmlspecialchars($edit_data['jml_matkul']) : 0 ?>" min="0">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Pengawas Pagi</label>
                                            <input type="number" name="jml_pgws_pagi" class="form-control" 
                                                   value="<?= isset($edit_data['jml_pgws_pagi']) ? htmlspecialchars($edit_data['jml_pgws_pagi']) : 0 ?>" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Pengawas Sore</label>
                                            <input type="number" name="jml_pgws_sore" class="form-control" 
                                                   value="<?= isset($edit_data['jml_pgws_sore']) ? htmlspecialchars($edit_data['jml_pgws_sore']) : 0 ?>" min="0">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Koordinator Pagi</label>
                                            <input type="number" name="jml_koor_pagi" class="form-control" 
                                                   value="<?= isset($edit_data['jml_koor_pagi']) ? htmlspecialchars($edit_data['jml_koor_pagi']) : 0 ?>" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Jumlah Koordinator Sore</label>
                                        <input type="number" name="jml_koor_sore" class="form-control" 
                                               value="<?= isset($edit_data['jml_koor_sore']) ? htmlspecialchars($edit_data['jml_koor_sore']) : 0 ?>" min="0">
                                    </div>
                                    
                                    <div class="form-group text-right">
                                        <a href="data_tu.php" class="btn btn-secondary">Batal</a>
                                        <button type="submit" name="update_tu" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <hr>
                            <?php endif; ?>

                            <!-- Tabel Data TU -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table-tu">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Semester</th>
                                            <th>Dosen</th>
                                            <th>Jabatan</th>
                                            <th>Mahasiswa</th>
                                            <th>Koreksi</th>
                                            <th>MK</th>
                                            <th>Pengawas</th>
                                            <th>Koordinator</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($tu_data) && $row_count > 0): ?>
                                        <?php $no = 1; foreach ($tu_data as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <strong>
                                                    <?php 
                                                    if (isset($row['semester']) && !empty($row['semester'])) {
                                                        $tahun = substr($row['semester'], 0, 4);
                                                        $semester_type = (substr($row['semester'], -1) == '1') ? 'Ganjil' : 'Genap';
                                                        echo htmlspecialchars($tahun . ' ' . $semester_type);
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </strong>
                                            </td>
                                            <td><?= isset($row['nama_dosen']) ? htmlspecialchars($row['nama_dosen']) : '-' ?></td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    <?= isset($row['jbtn_pnt']) ? htmlspecialchars($row['jbtn_pnt']) : '-' ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex flex-column">
                                                    <small class="text-muted">Prodi: <?= isset($row['jml_mhs_prodi']) ? number_format((int)$row['jml_mhs_prodi'], 0, ',', '.') : '0' ?></small>
                                                    <strong><?= isset($row['jml_mhs']) ? number_format((int)$row['jml_mhs'], 0, ',', '.') : '0' ?></strong>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-info">
                                                    <?= isset($row['jml_koreksi']) ? number_format((int)$row['jml_koreksi'], 0, ',', '.') : '0' ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-success">
                                                    <?= isset($row['jml_matkul']) ? number_format((int)$row['jml_matkul'], 0, ',', '.') : '0' ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <span class="session-badge session-pagi" title="Pengawas Pagi">
                                                        <?= isset($row['jml_pgws_pagi']) ? (int)$row['jml_pgws_pagi'] : '0' ?>
                                                    </span>
                                                    <span class="session-badge session-sore" title="Pengawas Sore">
                                                        <?= isset($row['jml_pgws_sore']) ? (int)$row['jml_pgws_sore'] : '0' ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <span class="session-badge session-pagi" title="Koordinator Pagi">
                                                        <?= isset($row['jml_koor_pagi']) ? (int)$row['jml_koor_pagi'] : '0' ?>
                                                    </span>
                                                    <span class="session-badge session-sore" title="Koordinator Sore">
                                                        <?= isset($row['jml_koor_sore']) ? (int)$row['jml_koor_sore'] : '0' ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if (isset($row['id_tu'])): ?>
                                                    <a href="?edit_id=<?= $row['id_tu'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete_id=<?= $row['id_tu'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Yakin ingin menghapus data transaksi ujian ini?')">
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
                                            <td colspan="10" class="text-center">
                                                Tidak ada data transaksi ujian
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-danger">
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
                                Total: <?= $total_tu ?> Transaksi
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
        $('#table-tu').DataTable({
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
    
    // Format input number
    $('input[type="number"]').on('input', function() {
        var value = $(this).val().replace(/\./g, '');
        if (!isNaN(value) && value !== '') {
            $(this).val(parseInt(value));
        }
    });
    
    // Calculate totals
    function calculateTotals() {
        let totalMahasiswa = 0;
        let totalKoreksi = 0;
        let totalMatkul = 0;
        let totalPengawasPagi = 0;
        let totalPengawasSore = 0;
        let totalKoorPagi = 0;
        let totalKoorSore = 0;
        
        $('#table-tu tbody tr').each(function() {
            const $row = $(this);
            
            // Mahasiswa
            const mhsText = $row.find('td:nth-child(5) strong').text();
            const mhsValue = parseInt(mhsText.replace(/\./g, '')) || 0;
            totalMahasiswa += mhsValue;
            
            // Koreksi
            const koreksiText = $row.find('td:nth-child(6) .badge').text();
            const koreksiValue = parseInt(koreksiText.replace(/\./g, '')) || 0;
            totalKoreksi += koreksiValue;
            
            // Mata Kuliah
            const matkulText = $row.find('td:nth-child(7) .badge').text();
            const matkulValue = parseInt(matkulText.replace(/\./g, '')) || 0;
            totalMatkul += matkulValue;
            
            // Pengawas
            const pengawasPagiText = $row.find('td:nth-child(8) .session-pagi').text();
            const pengawasSoreText = $row.find('td:nth-child(8) .session-sore').text();
            totalPengawasPagi += parseInt(pengawasPagiText) || 0;
            totalPengawasSore += parseInt(pengawasSoreText) || 0;
            
            // Koordinator
            const koorPagiText = $row.find('td:nth-child(9) .session-pagi').text();
            const koorSoreText = $row.find('td:nth-child(9) .session-sore').text();
            totalKoorPagi += parseInt(koorPagiText) || 0;
            totalKoorSore += parseInt(koorSoreText) || 0;
        });
        
        // Update summary card
        $('.summary-item:nth-child(2) .summary-value').text(totalMahasiswa.toLocaleString());
        $('.summary-item:nth-child(3) .summary-value').text(totalKoreksi.toLocaleString());
        $('.summary-item:nth-child(4) .summary-value').text(totalMatkul.toLocaleString());
        $('.summary-item:nth-child(5) .summary-value').text((totalPengawasPagi + totalPengawasSore).toLocaleString());
        $('.summary-item:nth-child(6) .summary-value').text((totalKoorPagi + totalKoorSore).toLocaleString());
    }
    
    // Calculate totals on page load
    setTimeout(calculateTotals, 100);
    
    // Recalculate when table is sorted/filtered
    if ($.fn.DataTable) {
        $('#table-tu').on('draw.dt', function() {
            setTimeout(calculateTotals, 100);
        });
    }
});
</script>