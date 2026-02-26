<?php
/**
 * INPUT HONOR - SiPagu (STAFF)
 * Halaman untuk input transaksi honor mengajar
 * Lokasi: staff/honor/input_honor.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

$id_user = $_SESSION['id_user'];
$success = '';
$error   = '';

// Proses simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_honor'])) {
    $semester   = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $bulan      = mysqli_real_escape_string($koneksi, $_POST['bulan']);
    $id_jadwal  = (int) $_POST['id_jadwal'];
    $jml_tm     = (int) $_POST['jml_tm'];
    $sks_tempuh = (int) $_POST['sks_tempuh'];

    // Validasi jadwal milik user ini
    $cek = mysqli_query($koneksi, 
        "SELECT id_jdwl FROM t_jadwal WHERE id_jdwl='$id_jadwal' AND id_user='$id_user'"
    );

    if (mysqli_num_rows($cek) == 0) {
        $error = "Jadwal tidak valid atau bukan milik Anda.";
    } elseif ($jml_tm <= 0 || $sks_tempuh <= 0) {
        $error = "Jumlah TM dan SKS harus lebih dari 0.";
    } else {
        // Cek duplikasi semester+bulan+jadwal
        $cek_dup = mysqli_query($koneksi, 
            "SELECT id_thd FROM t_transaksi_honor_dosen 
             WHERE semester='$semester' AND bulan='$bulan' AND id_jadwal='$id_jadwal'"
        );
        if (mysqli_num_rows($cek_dup) > 0) {
            $error = "Data honor untuk semester <strong>$semester</strong>, bulan <strong>" . ucfirst($bulan) . "</strong> pada mata kuliah ini sudah ada.";
        } else {
            $insert = mysqli_query($koneksi, "
                INSERT INTO t_transaksi_honor_dosen (semester, bulan, id_jadwal, jml_tm, sks_tempuh)
                VALUES ('$semester', '$bulan', '$id_jadwal', '$jml_tm', '$sks_tempuh')
            ");
            if ($insert) {
                $success = "Data honor berhasil disimpan!";
            } else {
                $error = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
        }
    }
}

// Ambil daftar jadwal milik staff ini
$query_jadwal = mysqli_query($koneksi, 
    "SELECT id_jdwl, kode_matkul, nama_matkul, semester
     FROM t_jadwal
     WHERE id_user = '$id_user'
     ORDER BY semester DESC, kode_matkul ASC"
);

$months = [
    'januari' => 'Januari', 'februari' => 'Februari', 'maret' => 'Maret',
    'april' => 'April', 'mei' => 'Mei', 'juni' => 'Juni',
    'juli' => 'Juli', 'agustus' => 'Agustus', 'september' => 'September',
    'oktober' => 'Oktober', 'november' => 'November', 'desember' => 'Desember'
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_staff.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Input Honor Mengajar</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>staff/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Honor Mengajar</div>
                <div class="breadcrumb-item">Input</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Form Input Honor Mengajar</h4>
                            <div class="card-header-action">
                                <a href="<?= BASE_URL ?>staff/honor/honor_saya.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-list mr-1"></i> Riwayat Honor
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show">
                                    <i class="fas fa-check-circle mr-2"></i> <?= $success ?>
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                </div>
                            <?php endif; ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> <?= $error ?>
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <!-- Pilih Mata Kuliah -->
                                <div class="form-group">
                                    <label>Mata Kuliah <span class="text-danger">*</span></label>
                                    <select name="id_jadwal" class="form-control" required id="selectJadwal">
                                        <option value="">-- Pilih Mata Kuliah --</option>
                                        <?php while ($j = mysqli_fetch_assoc($query_jadwal)): ?>
                                            <option value="<?= $j['id_jdwl'] ?>"
                                                data-semester="<?= $j['semester'] ?>"
                                                <?= (isset($_POST['id_jadwal']) && $_POST['id_jadwal'] == $j['id_jdwl']) ? 'selected' : '' ?>>
                                                [<?= htmlspecialchars($j['semester']) ?>] <?= htmlspecialchars($j['kode_matkul']) ?> - <?= htmlspecialchars($j['nama_matkul']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="form-text text-muted">Hanya menampilkan mata kuliah yang Anda ampu</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Semester <span class="text-danger">*</span></label>
                                            <input type="text" name="semester" class="form-control" id="inputSemester"
                                                   placeholder="Contoh: 2025A"
                                                   value="<?= isset($_POST['semester']) ? htmlspecialchars($_POST['semester']) : '' ?>"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Bulan <span class="text-danger">*</span></label>
                                            <select name="bulan" class="form-control" required>
                                                <option value="">-- Pilih Bulan --</option>
                                                <?php foreach ($months as $key => $label): ?>
                                                    <option value="<?= $key ?>" <?= (isset($_POST['bulan']) && $_POST['bulan'] === $key) ? 'selected' : '' ?>>
                                                        <?= $label ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Jumlah Tatap Muka (TM) <span class="text-danger">*</span></label>
                                            <input type="number" name="jml_tm" class="form-control"
                                                   min="1" max="50"
                                                   placeholder="Contoh: 14"
                                                   value="<?= isset($_POST['jml_tm']) ? (int)$_POST['jml_tm'] : '' ?>"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>SKS Ditempuh <span class="text-danger">*</span></label>
                                            <input type="number" name="sks_tempuh" class="form-control"
                                                   min="1" max="6"
                                                   placeholder="Contoh: 3"
                                                   value="<?= isset($_POST['sks_tempuh']) ? (int)$_POST['sks_tempuh'] : '' ?>"
                                                   required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group text-right">
                                    <a href="honor_saya.php" class="btn btn-secondary mr-2">
                                        <i class="fas fa-times mr-1"></i> Batal
                                    </a>
                                    <button type="submit" name="simpan_honor" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i> Simpan Honor
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Info Panel -->
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h4><i class="fas fa-info-circle text-info mr-2"></i> Petunjuk</h4>
                        </div>
                        <div class="card-body">
                            <ul class="pl-3">
                                <li class="mb-2">Pilih mata kuliah yang Anda ampu pada semester tersebut</li>
                                <li class="mb-2">Isi semester sesuai format: <strong>2025A</strong> atau <strong>2025B</strong></li>
                                <li class="mb-2">Jumlah TM adalah total pertemuan dalam satu bulan</li>
                                <li class="mb-2">SKS ditempuh sesuai bobot mata kuliah</li>
                                <li class="mb-2">Satu mata kuliah hanya dapat diinput sekali per bulan per semester</li>
                            </ul>
                            <hr>
                            <p class="text-muted small mb-0">
                                <i class="fas fa-calculator mr-1"></i>
                                Total honor dihitung otomatis: <br>
                                <strong>TM × SKS × Honor/SKS</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/footer_scripts.php'; ?>

<script>
$(document).ready(function() {
    // Auto-fill semester dari jadwal yang dipilih
    $('#selectJadwal').on('change', function() {
        var semester = $(this).find(':selected').data('semester');
        if (semester) {
            $('#inputSemester').val(semester);
        }
    });
});
</script>
