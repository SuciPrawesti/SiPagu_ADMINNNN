<?php
/**
 * APPROVE HONOR - SiPagu (KOORDINATOR)
 * Halaman untuk menyetujui/menolak data honor (HANYA JIKA ADA KOLOM STATUS)
 * CATATAN: Database tidak memiliki kolom status untuk approval
 * Lokasi: koordinator/honor/approve_honor.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Check if database has status column for approval
// Based on database structure, there's no status column for approval
// This page is created as placeholder for future implementation

$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? '';

if (!$id || !$type) {
    header("Location: honor_dosen.php");
    exit;
}

// Process form submission (if database had status column)
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // CATATAN: Database tidak memiliki kolom status untuk approval
    // Kode berikut adalah contoh jika ada kolom status di masa depan
    /*
    if ($action == 'approve' || $action == 'reject') {
        $status = $action == 'approve' ? 'approved' : 'rejected';
        $approved_by = $_SESSION['id_user'];
        $approved_at = date('Y-m-d H:i:s');
        
        // Determine table based on type
        $table = '';
        $id_field = '';
        
        switch ($type) {
            case 'dosen':
                $table = 't_transaksi_honor_dosen';
                $id_field = 'id_thd';
                break;
            case 'panitia':
                $table = 't_transaksi_ujian';
                $id_field = 'id_tu';
                break;
            case 'pa_ta':
                $table = 't_transaksi_pa_ta';
                $id_field = 'id_tpt';
                break;
        }
        
        if ($table && $id_field) {
            // Check if table has status column
            $check_column = mysqli_query($koneksi, 
                "SHOW COLUMNS FROM $table LIKE 'status'"
            );
            
            if (mysqli_num_rows($check_column) > 0) {
                // Update status
                $update = mysqli_query($koneksi, "
                    UPDATE $table 
                    SET status = '$status',
                        approval_notes = '$notes',
                        approved_by = '$approved_by',
                        approved_at = '$approved_at'
                    WHERE $id_field = '$id'
                ");
                
                if ($update) {
                    $message = "Data honor berhasil di-$action.";
                    header("Location: detail_honor.php?id=$id&type=$type");
                    exit;
                } else {
                    $error = "Gagal mengupdate status: " . mysqli_error($koneksi);
                }
            } else {
                $error = "Tabel tidak memiliki kolom status untuk approval.";
            }
        }
    }
    */
}

// Get data based on type
$data = [];
$title = '';

switch ($type) {
    case 'dosen':
        $query = mysqli_query($koneksi, "
            SELECT thd.*, j.*, u.*
            FROM t_transaksi_honor_dosen thd
            JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
            JOIN t_user u ON j.id_user = u.id_user
            WHERE thd.id_thd = '$id'
        ");
        $title = "Persetujuan Honor Dosen";
        break;
        
    case 'panitia':
        $query = mysqli_query($koneksi, "
            SELECT tu.*, p.*, u.*
            FROM t_transaksi_ujian tu
            JOIN t_panitia p ON tu.id_panitia = p.id_pnt
            JOIN t_user u ON tu.id_user = u.id_user
            WHERE tu.id_tu = '$id'
        ");
        $title = "Persetujuan Honor Panitia";
        break;
        
    case 'pa_ta':
        $query = mysqli_query($koneksi, "
            SELECT tpt.*, p.*, u.*
            FROM t_transaksi_pa_ta tpt
            JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
            JOIN t_user u ON tpt.id_user = u.id_user
            WHERE tpt.id_tpt = '$id'
        ");
        $title = "Persetujuan Honor PA/TA";
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
                            <h4>Persetujuan Data Honor</h4>
                        </div>
                        
                        <div class="card-body">
                            <!-- Warning Alert -->
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Perhatian:</strong> Fitur approval honor saat ini tidak tersedia karena 
                                struktur database tidak mendukung. Database tidak memiliki kolom status untuk 
                                approval. Kontak administrator untuk informasi lebih lanjut.
                            </div>
                            
                            <!-- Data Summary -->
                            <div class="summary-card mb-4">
                                <h5>Ringkasan Data</h5>
                                <div class="row">
                                    <?php if ($type == 'dosen'): ?>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-primary"><i class="fas fa-user"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Dosen</span>
                                                <span class="info-box-number">
                                                    <?= htmlspecialchars($data['nama_user']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i class="fas fa-book"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Mata Kuliah</span>
                                                <span class="info-box-number">
                                                    <?= htmlspecialchars($data['nama_matkul']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning"><i class="fas fa-calendar"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Semester</span>
                                                <span class="info-box-number">
                                                    <?= $data['semester'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php elseif ($type == 'panitia'): ?>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-primary"><i class="fas fa-user-tie"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Panitia</span>
                                                <span class="info-box-number">
                                                    <?= htmlspecialchars($data['nama_user']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i class="fas fa-id-badge"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Jabatan</span>
                                                <span class="info-box-number">
                                                    <?= htmlspecialchars($data['jbtn_pnt']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning"><i class="fas fa-graduation-cap"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Jumlah Mhs</span>
                                                <span class="info-box-number">
                                                    <?= $data['jml_mhs'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php elseif ($type == 'pa_ta'): ?>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-primary"><i class="fas fa-user-graduate"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Dosen Pembimbing</span>
                                                <span class="info-box-number">
                                                    <?= htmlspecialchars($data['nama_user']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i class="fas fa-school"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Program Studi</span>
                                                <span class="info-box-number">
                                                    <?= $data['prodi'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning"><i class="fas fa-users"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Mhs Bimbingan</span>
                                                <span class="info-box-number">
                                                    <?= $data['jml_mhs_bimbingan'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Current Status (Placeholder) -->
                            <div class="status-card mb-4">
                                <h5>Status Saat Ini</h5>
                                <div class="alert alert-secondary">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle fa-2x mr-3"></i>
                                        <div>
                                            <h6 class="mb-1">Database Tidak Mendukung</h6>
                                            <p class="mb-0">
                                                Struktur database saat ini tidak memiliki kolom untuk mencatat 
                                                status approval. Fitur ini akan tersedia setelah update database.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Placeholder Form -->
                            <div class="placeholder-form">
                                <h5>Form Persetujuan (Tidak Aktif)</h5>
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label>Aksi</label>
                                        <div class="selectgroup selectgroup-pills">
                                            <label class="selectgroup-item">
                                                <input type="radio" name="action" value="approve" 
                                                       class="selectgroup-input" disabled>
                                                <span class="selectgroup-button selectgroup-button-icon">
                                                    <i class="fas fa-check-circle mr-1"></i> Setujui
                                                </span>
                                            </label>
                                            <label class="selectgroup-item">
                                                <input type="radio" name="action" value="reject" 
                                                       class="selectgroup-input" disabled>
                                                <span class="selectgroup-button selectgroup-button-icon">
                                                    <i class="fas fa-times-circle mr-1"></i> Tolak
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Catatan (Opsional)</label>
                                        <textarea class="form-control" name="notes" 
                                                  placeholder="Masukkan catatan persetujuan/penolakan..." 
                                                  rows="3" disabled></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success" disabled>
                                            <i class="fas fa-check mr-1"></i> Simpan Persetujuan
                                        </button>
                                        <a href="detail_honor.php?id=<?= $id ?>&type=<?= $type ?>" 
                                           class="btn btn-secondary">
                                            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Detail
                                        </a>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-lock mr-2"></i>
                                        Form ini terkunci karena struktur database tidak mendukung fitur approval.
                                    </div>
                                </form>
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
.summary-card {
    background: #f8fafc;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #e2e8f0;
}

.info-box {
    background: white;
    border-radius: 6px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.info-box-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 1.5rem;
}

.info-box-content {
    margin-left: 70px;
}

.info-box-text {
    font-size: 0.875rem;
    color: #718096;
    text-transform: uppercase;
    font-weight: 500;
}

.info-box-number {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2d3748;
}

.status-card .alert {
    border-left: 4px solid #667eea;
}

.placeholder-form {
    background: #f8fafc;
    border-radius: 8px;
    padding: 25px;
    border: 1px dashed #cbd5e0;
}

.placeholder-form h5 {
    color: #4a5568;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

.selectgroup.selectgroup-pills {
    flex-wrap: wrap;
}

.selectgroup-item {
    margin-bottom: 5px;
}
</style>

<?php 
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>