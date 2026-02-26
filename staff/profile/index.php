<?php
/**
 * PROFILE STAFF - SiPagu
 * Halaman untuk mengelola profile staff
 * Lokasi: staff/profile/index.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

$user_id = $_SESSION['id_user'];
$query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$user_id'");
$user = mysqli_fetch_assoc($query);

$success = '';
$error   = '';

// Proses update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama_user  = mysqli_real_escape_string($koneksi, $_POST['nama_user']);
    $nohp_user  = mysqli_real_escape_string($koneksi, $_POST['nohp_user']);
    $norek_user = mysqli_real_escape_string($koneksi, $_POST['norek_user']);
    $npwp_user  = mysqli_real_escape_string($koneksi, $_POST['npwp_user']);

    $update_query = mysqli_query($koneksi,
        "UPDATE t_user SET 
            nama_user  = '$nama_user',
            nohp_user  = '$nohp_user',
            norek_user = '$norek_user',
            npwp_user  = '$npwp_user'
        WHERE id_user = '$user_id'"
    );

    if ($update_query) {
        $success = "Profile berhasil diupdate!";
        $query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$user_id'");
        $user  = mysqli_fetch_assoc($query);
        $_SESSION['nama_user'] = $user['nama_user'];
    } else {
        $error = "Gagal mengupdate profile: " . mysqli_error($koneksi);
    }
}

// Proses ganti password
$pw_success = '';
$pw_error   = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ganti_password'])) {
    $pw_lama = md5($_POST['pw_lama']);
    $pw_baru = $_POST['pw_baru'];
    $pw_conf = $_POST['pw_konfirmasi'];

    if ($pw_lama !== $user['pw_user']) {
        $pw_error = "Password lama tidak sesuai.";
    } elseif (strlen($pw_baru) < 6) {
        $pw_error = "Password baru minimal 6 karakter.";
    } elseif ($pw_baru !== $pw_conf) {
        $pw_error = "Konfirmasi password tidak cocok.";
    } else {
        $pw_hash = md5($pw_baru);
        $upd = mysqli_query($koneksi, 
            "UPDATE t_user SET pw_user='$pw_hash' WHERE id_user='$user_id'"
        );
        if ($upd) {
            $pw_success = "Password berhasil diubah!";
        } else {
            $pw_error = "Gagal mengubah password.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_staff.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header pt-4 pb-0">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <h1 class="h3 font-weight-normal text-dark mb-1">Profile Saya</h1>
                    <p class="text-muted mb-0">Kelola informasi akun Anda</p>
                </div>
                <div class="text-muted">
                    <?php echo date('l, d F Y'); ?>
                </div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <!-- Profile Edit -->
                <div class="col-12">
                    <div class="card card-simple">
                        <div class="card-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show">
                                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                </div>
                            <?php endif; ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <i class="fas fa-exclamation-triangle mr-2"></i><?= $error ?>
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                </div>
                            <?php endif; ?>

                            <div class="row">
                                <!-- Info Singkat -->
                                <div class="col-md-4">
                                    <div class="text-center mb-4">
                                        <div class="avatar-placeholder mx-auto mb-3" style="
                                            width: 80px; height: 80px; border-radius: 50%;
                                            background: linear-gradient(135deg, #667eea, #764ba2);
                                            display: flex; align-items: center; justify-content: center;
                                            font-size: 2rem; color: white; font-weight: 600;">
                                            <?= strtoupper(substr($user['nama_user'], 0, 1)) ?>
                                        </div>
                                        <h4 class="mb-1"><?= htmlspecialchars($user['nama_user']) ?></h4>
                                        <div class="mb-3">
                                            <span class="badge badge-info">
                                                <?= strtoupper($user['role_user']) ?>
                                            </span>
                                        </div>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-id-badge mr-1"></i>
                                            <?= htmlspecialchars($user['npp_user']) ?>
                                        </p>
                                    </div>

                                    <div class="info-card mb-4">
                                        <h6 class="info-title">Informasi Akun</h6>
                                        <div class="info-item">
                                            <i class="fas fa-shield-alt text-muted mr-2"></i>
                                            <span>Status:</span>
                                            <span class="float-right text-success">Aktif</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-money-check text-muted mr-2"></i>
                                            <span>Honor/SKS:</span>
                                            <span class="float-right font-weight-bold">
                                                Rp <?= number_format($user['honor_persks'], 0, ',', '.') ?>
                                            </span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt text-muted mr-2"></i>
                                            <span>Hari ini:</span>
                                            <span class="float-right"><?= date('d M Y') ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Edit -->
                                <div class="col-md-8">
                                    <h5 class="mb-4">Edit Profile</h5>
                                    <form method="POST" action="">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>NPP</label>
                                                    <input type="text" class="form-control"
                                                           value="<?= htmlspecialchars($user['npp_user']) ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>NIK</label>
                                                    <input type="text" class="form-control"
                                                           value="<?= htmlspecialchars($user['nik_user']) ?>" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" name="nama_user" class="form-control"
                                                   value="<?= htmlspecialchars($user['nama_user']) ?>" required>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nomor HP <span class="text-danger">*</span></label>
                                                    <input type="text" name="nohp_user" class="form-control"
                                                           value="<?= htmlspecialchars($user['nohp_user']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>NPWP <span class="text-danger">*</span></label>
                                                    <input type="text" name="npwp_user" class="form-control"
                                                           value="<?= htmlspecialchars($user['npwp_user']) ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Nomor Rekening <span class="text-danger">*</span></label>
                                            <input type="text" name="norek_user" class="form-control"
                                                   value="<?= htmlspecialchars($user['norek_user']) ?>" required>
                                            <small class="form-text text-muted">
                                                Pastikan nomor rekening valid untuk pembayaran honor
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label>Honor per SKS</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="text" class="form-control"
                                                       value="<?= number_format($user['honor_persks'], 0, ',', '.') ?>" readonly>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">/ SKS</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">Nilai ini diatur oleh administrator</small>
                                        </div>

                                        <div class="form-group text-right">
                                            <button type="submit" name="update_profile" class="btn btn-primary">
                                                <i class="fas fa-save mr-1"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>

                                    <hr>

                                    <!-- Ganti Password -->
                                    <h5 class="mb-3">Ganti Password</h5>
                                    <?php if ($pw_success): ?>
                                        <div class="alert alert-success alert-dismissible fade show">
                                            <i class="fas fa-check-circle mr-2"></i><?= $pw_success ?>
                                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($pw_error): ?>
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <i class="fas fa-exclamation-triangle mr-2"></i><?= $pw_error ?>
                                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                        </div>
                                    <?php endif; ?>
                                    <form method="POST" action="">
                                        <div class="form-group">
                                            <label>Password Lama <span class="text-danger">*</span></label>
                                            <input type="password" name="pw_lama" class="form-control" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Password Baru <span class="text-danger">*</span></label>
                                                    <input type="password" name="pw_baru" class="form-control" 
                                                           minlength="6" required>
                                                    <small class="form-text text-muted">Minimal 6 karakter</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                                    <input type="password" name="pw_konfirmasi" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group text-right">
                                            <button type="submit" name="ganti_password" class="btn btn-warning">
                                                <i class="fas fa-key mr-1"></i> Ganti Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/footer_scripts.php'; ?>

<style>
:root {
    --card-bg: #ffffff;
    --border-color: #eef2f7;
    --text-primary: #2d3748;
    --text-secondary: #718096;
}

.card-simple {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.info-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
}

.info-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-color);
}

.info-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
    font-size: 0.875rem;
    color: var(--text-primary);
    border-bottom: 1px solid var(--border-color);
}

.info-item:last-child { border-bottom: none; }
.info-item i { width: 20px; }

.form-group label { font-weight: 500; color: var(--text-primary); margin-bottom: 6px; }
.form-control { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 15px; transition: all 0.2s ease; }
.form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.form-control:read-only { background-color: #f8fafc; color: #718096; }
.input-group-text { background-color: #f8fafc; border: 1px solid #e2e8f0; color: var(--text-secondary); font-weight: 500; }

.h3 { font-size: 1.75rem; font-weight: 400; }
.font-weight-normal { font-weight: 400 !important; }
.section-header { border-bottom: 1px solid var(--border-color); }
</style>
