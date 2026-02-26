<?php
/**
 * UPLOAD DATA HONOR DOSEN - SiPagu
 * Halaman upload data honor dosen dari Excel
 * Lokasi: admin/upload_honor_dosen.php
 */

// ======================
// INCLUDE & KONFIGURASI
// ======================
require_once __DIR__ . '../../auth.php';
require_once __DIR__ . '../../../config.php';

$page_title = "Upload Honor Dosen";

$error_message   = '';
$success_message = '';

// ======================
// PROSES UPLOAD EXCEL
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    if (empty($_FILES['filexls']['name'])) {
        $error_message = 'Silakan pilih file Excel.';
    } else {

        $file_name = $_FILES['filexls']['name'];
        $file_tmp  = $_FILES['filexls']['tmp_name'];
        $file_size = $_FILES['filexls']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validasi ekstensi
        if (!in_array($file_ext, ['xls', 'xlsx'])) {
            $error_message = 'File harus bertipe XLS atau XLSX.';
        }
        // Validasi ukuran (10MB)
        elseif ($file_size > 10 * 1024 * 1024) {
            $error_message = 'Ukuran file maksimal 10MB.';
        }
        else {

            require_once __DIR__ . '/../vendor/autoload.php';

            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_tmp);
                $spreadsheet = $reader->load($file_tmp);
                $sheetData = $spreadsheet->getActiveSheet()->toArray();

                $jumlahData  = 0;
                $jumlahGagal = 0;
                $errors      = [];

                // Mulai dari baris ke-1 (0 = header)
                for ($i = 1; $i < count($sheetData); $i++) {

                    $semester   = trim($sheetData[$i][0] ?? '');
                    $bulan      = strtolower(trim($sheetData[$i][1] ?? ''));
                    $id_jadwal  = trim($sheetData[$i][2] ?? '');
                    $jml_tm     = trim($sheetData[$i][3] ?? '');
                    $sks_tempuh = trim($sheetData[$i][4] ?? '');

                    // Skip baris kosong
                    if (empty($semester) && empty($bulan) && empty($id_jadwal)) {
                        continue;
                    }

                    // Validasi field wajib
                    if (empty($semester) || empty($bulan) || empty($id_jadwal)) {
                        $errors[] = "Baris $i: Semester, bulan, dan id_jadwal wajib diisi";
                        $jumlahGagal++;
                        continue;
                    }

                    // Validasi bulan
                    $bulan_valid = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 
                                    'juli', 'agustus', 'september', 'oktober', 'november', 'desember'];
                    if (!in_array($bulan, $bulan_valid)) {
                        $errors[] = "Baris $i: Bulan '$bulan' tidak valid";
                        $jumlahGagal++;
                        continue;
                    }

                    // Validasi numerik
                    if (!is_numeric($id_jadwal) || !is_numeric($jml_tm) || !is_numeric($sks_tempuh)) {
                        $errors[] = "Baris $i: id_jadwal, jumlah TM, dan SKS harus angka";
                        $jumlahGagal++;
                        continue;
                    }

                    // Validasi semester format
                    if (!preg_match('/^\d{4}[12]$/', $semester)) {
                        $errors[] = "Baris $i: Format semester '$semester' tidak valid";
                        $jumlahGagal++;
                        continue;
                    }

                    // Escape input
                    $semester = mysqli_real_escape_string($koneksi, $semester);
                    $bulan = mysqli_real_escape_string($koneksi, $bulan);
                    $id_jadwal = mysqli_real_escape_string($koneksi, $id_jadwal);
                    $jml_tm = mysqli_real_escape_string($koneksi, $jml_tm);
                    $sks_tempuh = mysqli_real_escape_string($koneksi, $sks_tempuh);

                    // Cek jadwal
                    $cekJadwal = mysqli_query($koneksi,
                        "SELECT id_jdwl FROM t_jadwal WHERE id_jdwl = '$id_jadwal'"
                    );

                    if (mysqli_num_rows($cekJadwal) == 0) {
                        $errors[] = "Baris $i: id_jadwal '$id_jadwal' tidak ditemukan";
                        $jumlahGagal++;
                        continue;
                    }

                    // Cek duplikasi
                    $cekDuplikat = mysqli_query($koneksi,
                        "SELECT id_thd FROM t_transaksi_honor_dosen 
                         WHERE semester = '$semester' 
                         AND bulan = '$bulan' 
                         AND id_jadwal = '$id_jadwal'"
                    );

                    if (mysqli_num_rows($cekDuplikat) > 0) {
                        if (isset($_POST['overwrite']) && $_POST['overwrite'] == '1') {
                            // Update jika ada
                            $update = mysqli_query($koneksi, "
                                UPDATE t_transaksi_honor_dosen
                                SET jml_tm = '$jml_tm', sks_tempuh = '$sks_tempuh'
                                WHERE semester = '$semester' 
                                AND bulan = '$bulan' 
                                AND id_jadwal = '$id_jadwal'
                            ");
                            
                            if ($update) {
                                $jumlahData++;
                            } else {
                                $errors[] = "Baris $i: Gagal mengupdate data";
                                $jumlahGagal++;
                            }
                        } else {
                            $errors[] = "Baris $i: Data untuk id_jadwal '$id_jadwal' sudah ada";
                            $jumlahGagal++;
                        }
                        continue;
                    }

                    // Insert data baru
                    $insert = mysqli_query($koneksi, "
                        INSERT INTO t_transaksi_honor_dosen
                        (semester, bulan, id_jadwal, jml_tm, sks_tempuh)
                        VALUES
                        ('$semester', '$bulan', '$id_jadwal', '$jml_tm', '$sks_tempuh')
                    ");

                    if ($insert) {
                        $jumlahData++;
                    } else {
                        $errors[] = "Baris $i: Gagal menyimpan data - " . mysqli_error($koneksi);
                        $jumlahGagal++;
                    }
                }

                if ($jumlahData > 0) {
                    $success_message = "Berhasil mengimport <strong>$jumlahData</strong> data honor dosen.";
                    if ($jumlahGagal > 0) {
                        $success_message .= " <strong>$jumlahGagal</strong> data gagal.";
                    }
                } else {
                    $error_message = "Tidak ada data yang berhasil diimport.";
                }

                if (!empty($errors)) {
                    $error_message .= '<br>' . implode('<br>', array_slice($errors, 0, 5));
                    if (count($errors) > 5) {
                        $error_message .= '<br>... dan ' . (count($errors) - 5) . ' error lainnya';
                    }
                }

            } catch (Exception $e) {
                $error_message = "Gagal membaca file Excel: " . $e->getMessage();
            }
        }
    }
}

// ======================
// PROSES INPUT MANUAL
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_manual'])) {
    $manual_semester = mysqli_real_escape_string($koneksi, $_POST['manual_semester'] ?? '');
    $manual_bulan = mysqli_real_escape_string($koneksi, strtolower($_POST['manual_bulan'] ?? ''));
    $manual_jadwal = mysqli_real_escape_string($koneksi, $_POST['manual_jadwal'] ?? '');
    $manual_jml_tm = mysqli_real_escape_string($koneksi, $_POST['manual_jml_tm'] ?? '');
    $manual_sks = mysqli_real_escape_string($koneksi, $_POST['manual_sks'] ?? '');

    // Validasi
    if (empty($manual_semester) || empty($manual_bulan) || empty($manual_jadwal)) {
        $error_message = "Semua field wajib diisi!";
    } elseif (!is_numeric($manual_jadwal) || !is_numeric($manual_jml_tm) || !is_numeric($manual_sks)) {
        $error_message = "ID Jadwal, Jumlah TM, dan SKS harus angka!";
    } elseif (!preg_match('/^\d{4}[12]$/', $manual_semester)) {
        $error_message = "Format semester tidak valid!";
    } else {
        // Cek jadwal
        $cekJadwal = mysqli_query($koneksi,
            "SELECT id_jdwl FROM t_jadwal WHERE id_jdwl = '$manual_jadwal'"
        );
        
        if (mysqli_num_rows($cekJadwal) == 0) {
            $error_message = "ID Jadwal tidak ditemukan!";
        } else {
            // Cek duplikasi
            $cekDuplikat = mysqli_query($koneksi,
                "SELECT id_thd FROM t_transaksi_honor_dosen 
                 WHERE semester = '$manual_semester' 
                 AND bulan = '$manual_bulan' 
                 AND id_jadwal = '$manual_jadwal'"
            );
            
            if (mysqli_num_rows($cekDuplikat) > 0) {
                $error_message = "Data untuk ID Jadwal ini sudah ada!";
            } else {
                $insert_manual = mysqli_query($koneksi, "
                    INSERT INTO t_transaksi_honor_dosen
                    (semester, bulan, id_jadwal, jml_tm, sks_tempuh)
                    VALUES
                    ('$manual_semester', '$manual_bulan', '$manual_jadwal', '$manual_jml_tm', '$manual_sks')
                ");
                
                if ($insert_manual) {
                    $success_message = "Data honor dosen berhasil disimpan!";
                } else {
                    $error_message = "Gagal menyimpan data: " . mysqli_error($koneksi);
                }
            }
        }
    }
}

// Ambil data jadwal untuk dropdown
$jadwal_list = [];
$query_jadwal = mysqli_query($koneksi, 
    "SELECT j.id_jdwl, j.kode_matkul, j.nama_matkul, u.nama_user 
     FROM t_jadwal j 
     LEFT JOIN t_user u ON j.id_user = u.id_user 
     ORDER BY j.id_jdwl DESC"
);
while ($row = mysqli_fetch_assoc($query_jadwal)) {
    $jadwal_list[$row['id_jdwl']] = $row['kode_matkul'] . ' - ' . $row['nama_matkul'] . ' (' . $row['nama_user'] . ')';
}

// ======================
// INCLUDE TEMPLATE
// ======================
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- MAIN CONTENT -->
<div class="main-content">
<section class="section">

<div class="section-header">
    <h1>Upload Data Honor Dosen</h1>
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active">
            <a href="<?= BASE_URL ?>admin/index.php">Dashboard</a>
        </div>
        <div class="breadcrumb-item">Upload Honor Dosen</div>
    </div>
</div>

<div class="section-body">

<?php if ($error_message): ?>
<div class="alert alert-danger alert-dismissible show fade">
    <div class="alert-body">
        <button class="close" data-dismiss="alert"><span>×</span></button>
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?= $error_message ?>
    </div>
</div>
<?php endif; ?>

<?php if ($success_message): ?>
<div class="alert alert-success alert-dismissible show fade">
    <div class="alert-body">
        <button class="close" data-dismiss="alert"><span>×</span></button>
        <i class="fas fa-check-circle mr-2"></i>
        <?= $success_message ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
<div class="card-header">
    <h4>Upload File Excel Honor Dosen</h4>
</div>

<div class="card-body">

<div class="alert alert-info">
    <h6><i class="fas fa-info-circle mr-2"></i>Petunjuk Penggunaan</h6>
    <ul class="mb-0 pl-3">
        <li>Kolom wajib: semester, bulan, id_jadwal, jml_tm, sks_tempuh</li>
        <li>Bulan harus sesuai dengan enum database (januari-desember)</li>
        <li>ID jadwal harus sudah ada di tabel t_jadwal</li>
        <li>Format file: .xls / .xlsx (maks. 10MB)</li>
        <li>Format semester: YYYY1 atau YYYY2 (contoh: 20241, 20242)</li>
    </ul>
</div>

<!-- Template Download -->
<div class="card card-primary">
    <div class="card-header">
        <h4><i class="fas fa-download mr-2"></i>Download Template</h4>
    </div>
    <div class="card-body">
        <p>Gunakan template ini untuk memastikan format file Excel sesuai dengan sistem.</p>
        <a href="#" class="btn btn-primary btn-icon icon-left">
            <i class="fas fa-download"></i> Download Template Honor Dosen.xlsx
        </a>
    </div>
</div>

<!-- Upload Form -->
<form method="POST" enctype="multipart/form-data" class="mt-4">
    <div class="form-group">
        <label>Pilih File Excel</label>
        <div class="custom-file">
            <input type="file" class="custom-file-input" id="filexls" name="filexls" accept=".xls,.xlsx" required>
            <label class="custom-file-label" for="filexls">Pilih file...</label>
        </div>
        <small class="form-text text-muted">Format: .xls atau .xlsx (maks. 10MB)</small>
    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <div class="custom-control custom-checkbox mt-4 pt-2">
                <input type="checkbox" class="custom-control-input" id="overwrite" name="overwrite" value="1">
                <label class="custom-control-label" for="overwrite">Timpa data yang sudah ada</label>
                <small class="form-text text-muted">Jika dicentang, data dengan kombinasi yang sama akan ditimpa</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <button type="submit" name="submit" class="btn btn-primary btn-icon icon-left">
            <i class="fas fa-upload"></i> Upload & Proses
        </button>
        <button type="reset" class="btn btn-secondary">Reset</button>
    </div>
</form>

<!-- Manual Input Form -->
<div class="mt-5">
    <h5><i class="fas fa-keyboard mr-2"></i>Input Manual</h5>
    <form action="" method="POST" class="mt-3">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Semester <span class="text-danger">*</span></label>
                <select class="form-control" name="manual_semester" required>
                    <option value="">Pilih Semester</option>
                    <option value="20241">2024 Ganjil (20241)</option>
                    <option value="20242">2024 Genap (20242)</option>
                    <option value="20251">2025 Ganjil (20251)</option>
                    <option value="20252">2025 Genap (20252)</option>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Bulan <span class="text-danger">*</span></label>
                <select class="form-control" name="manual_bulan" required>
                    <option value="">Pilih Bulan</option>
                    <option value="januari">Januari</option>
                    <option value="februari">Februari</option>
                    <option value="maret">Maret</option>
                    <option value="april">April</option>
                    <option value="mei">Mei</option>
                    <option value="juni">Juni</option>
                    <option value="juli">Juli</option>
                    <option value="agustus">Agustus</option>
                    <option value="september">September</option>
                    <option value="oktober">Oktober</option>
                    <option value="november">November</option>
                    <option value="desember">Desember</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>Jadwal <span class="text-danger">*</span></label>
                <select class="form-control" name="manual_jadwal" required>
                    <option value="">Pilih Jadwal</option>
                    <?php foreach ($jadwal_list as $id_jdwl => $label): ?>
                        <option value="<?= htmlspecialchars($id_jdwl) ?>">
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Jumlah TM (Tatap Muka) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="manual_jml_tm" min="0" value="0" required>
            </div>
            <div class="form-group col-md-6">
                <label>SKS Tempuh <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="manual_sks" min="0" max="6" value="3" required>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" name="submit_manual" class="btn btn-success btn-icon icon-left">
                <i class="fas fa-save"></i> Simpan Data Manual
            </button>
        </div>
    </form>
</div>

<hr>

<h5><i class="fas fa-table mr-2"></i>Data Honor Dosen Terbaru</h5>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Semester</th>
                <th>Bulan</th>
                <th>ID Jadwal</th>
                <th>TM</th>
                <th>SKS</th>
                <th>Tanggal Input</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = mysqli_query($koneksi, 
                "SELECT h.semester, h.bulan, h.id_jadwal, h.jml_tm, h.sks_tempuh,
                        j.kode_matkul, j.nama_matkul
                 FROM t_transaksi_honor_dosen h
                 LEFT JOIN t_jadwal j ON h.id_jadwal = j.id_jdwl
                 ORDER BY h.id_thd DESC 
                 LIMIT 10"
            );
            if (mysqli_num_rows($query) > 0):
                while ($row = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['semester']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($row['bulan'])) ?></td>
                    <td>
                        <span class="badge badge-primary"><?= $row['id_jadwal'] ?></span><br>
                        <small><?= htmlspecialchars($row['kode_matkul']) ?> - <?= htmlspecialchars($row['nama_matkul']) ?></small>
                    </td>
                    <td><?= $row['jml_tm'] ?></td>
                    <td><?= $row['sks_tempuh'] ?></td>
                    <td><?= date('d/m/Y') ?></td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="6" class="text-center">Belum ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<hr>

<h5><i class="fas fa-file-excel mr-2"></i>Contoh Format Excel</h5>
<table class="table table-bordered table-sm">
<thead>
<tr>
    <th>semester</th>
    <th>bulan</th>
    <th>id_jadwal</th>
    <th>jml_tm</th>
    <th>sks_tempuh</th>
</tr>
</thead>
<tbody>
<tr>
    <td>20242</td>
    <td>februari</td>
    <td>1</td>
    <td>14</td>
    <td>3</td>
</tr>
<tr>
    <td>20242</td>
    <td>maret</td>
    <td>2</td>
    <td>16</td>
    <td>4</td>
</tr>
</tbody>
</table>

</div>
</div>

</div>
</section>
</div>

<script>
// Untuk mengubah label file input
document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    var fileName = document.getElementById("filexls").files[0].name;
    var nextSibling = e.target.nextElementSibling;
    nextSibling.innerText = fileName;
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>