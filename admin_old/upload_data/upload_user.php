<?php
/**
 * UPLOAD DATA USER - SiPagu
 * Halaman untuk upload data user dari Excel dengan pendekatan fleksibel
 * Lokasi: admin/upload_user.php
 */

// Include required files
require_once __DIR__ . '../../auth.php';
require_once __DIR__ . '../../../config.php';

// Set page title
$page_title = "Upload Data User";

// Process form submission
$error_message = '';
$success_message = '';
$preview_data = [];

// Direktori untuk file sementara
$temp_dir = __DIR__ . '/../temp_uploads/';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

// Helper functions
function safe_trim($value) {
    return $value !== null ? trim($value) : '';
}

function safe_html($value) {
    return $value !== null ? htmlspecialchars($value) : '';
}

// Field mapping configuration
$field_mapping = [
    'npp' => ['npp', 'npp_user', 'nomor pokok pegawai', 'kode pegawai'],
    'nik' => ['nik', 'nik_user', 'nomor induk kependudukan', 'ktp'],
    'npwp' => ['npwp', 'npwp_user', 'nomor pokok wajib pajak'],
    'norek' => ['norek', 'norek_user', 'rekening', 'no rek', 'no_rekening', 'nomor rekening'],
    'nama' => ['nama', 'nama_user', 'name', 'fullname', 'pegawai'],
    'nohp' => ['nohp', 'nohp_user', 'telepon', 'telp', 'hp', 'handphone', 'phone'],
    'role' => ['role', 'role_user', 'jabatan', 'posisi'],
    'honor' => ['honor', 'honor_persks', 'gaji', 'upah', 'honorarium']
];

// Process file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // UPLOAD FILE BARU
    if (isset($_POST['submit']) && isset($_FILES['filexls'])) {
        $file_name = $_FILES['filexls']['name'];
        $file_tmp = $_FILES['filexls']['tmp_name'];
        $file_size = $_FILES['filexls']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validasi ekstensi file
        $allowed_ext = ['xls', 'xlsx', 'csv'];
        if (!in_array($file_ext, $allowed_ext)) {
            $error_message = 'File harus bertipe XLS, XLSX, atau CSV.';
        }
        // Validasi ukuran file (10MB max)
        elseif ($file_size > 10 * 1024 * 1024) {
            $error_message = 'File terlalu besar. Maksimal 10MB.';
        }
        else {
            // Generate unique filename
            $unique_name = 'upload_' . time() . '_' . uniqid() . '.' . $file_ext;
            $temp_file_path = $temp_dir . $unique_name;
            
            // Save file to temp directory
            if (move_uploaded_file($file_tmp, $temp_file_path)) {
                // Process file untuk preview
                $preview_data = process_file_for_preview($temp_file_path, $file_ext, $field_mapping);
                if ($preview_data) {
                    $preview_data['temp_file'] = $unique_name;
                    $_SESSION['upload_temp_file'] = $unique_name;
                    $success_message = "Header terdeteksi. Silakan konfirmasi mapping kolom:";
                } else {
                    $error_message = "Gagal membaca file. Pastikan file berformat benar.";
                    unlink($temp_file_path);
                }
            } else {
                $error_message = "Gagal menyimpan file sementara.";
            }
        }
    }
    
    // PREVIEW MODE - DETEKSI ULANG
    elseif (isset($_POST['detect_again']) && isset($_SESSION['upload_temp_file'])) {
        $temp_file = $_SESSION['upload_temp_file'];
        $temp_file_path = $temp_dir . $temp_file;
        $file_ext = strtolower(pathinfo($temp_file, PATHINFO_EXTENSION));
        
        if (file_exists($temp_file_path)) {
            $preview_data = process_file_for_preview($temp_file_path, $file_ext, $field_mapping);
            if ($preview_data) {
                $preview_data['temp_file'] = $temp_file;
                $success_message = "Header dideteksi ulang. Silakan konfirmasi mapping:";
            }
        } else {
            $error_message = "File sementara tidak ditemukan. Upload ulang file.";
            unset($_SESSION['upload_temp_file']);
        }
    }
    
    // CONFIRM MAPPING DAN IMPORT DATA
    elseif (isset($_POST['confirm_mapping']) && isset($_SESSION['upload_temp_file'])) {
        $temp_file = $_SESSION['upload_temp_file'];
        $temp_file_path = $temp_dir . $temp_file;
        $file_ext = strtolower(pathinfo($temp_file, PATHINFO_EXTENSION));
        
        if (file_exists($temp_file_path)) {
            // Include PhpSpreadsheet
            require_once __DIR__ . '/../vendor/autoload.php';
            
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($temp_file_path);
                if ($file_ext == 'csv') {
                    $reader->setReadDataOnly(true);
                    $reader->setReadEmptyCells(false);
                }
                
                $spreadsheet = $reader->load($temp_file_path);
                $sheetData = $spreadsheet->getActiveSheet()->toArray();
                
                // Get header row dari session atau deteksi
                $headerRow = isset($_POST['header_row']) ? (int)$_POST['header_row'] : 0;
                
                // Get column mapping dari form - hanya ambil yang tidak kosong
                $column_mapping = [];
                if (isset($_POST['manual_mapping'])) {
                    foreach ($_POST['manual_mapping'] as $colIndex => $dbField) {
                        if (!empty($dbField) && $dbField != 'skip') {
                            $column_mapping[$colIndex] = $dbField;
                        }
                    }
                }
                
                // Validate required columns
                $mapped_fields = array_values($column_mapping);
                if (!in_array('npp', $mapped_fields)) {
                    $error_message = "Kolom <strong>NPP</strong> wajib dipetakan untuk proses import!";
                } elseif (!in_array('nama', $mapped_fields)) {
                    $error_message = "Kolom <strong>Nama</strong> wajib dipetakan untuk proses import!";
                } else {
                    $startRow = $headerRow + 1;
                    $jumlahData = 0;
                    $jumlahGagal = 0;
                    $errors = [];
                    $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] == '1';
                    
                    // Default values untuk kolom yang tidak dipetakan
                    $default_values = [
                        'nik' => '',
                        'npwp' => '',
                        'norek' => '',
                        'nohp' => '',
                        'role' => 'staff',
                        'honor' => 0
                    ];
                    
                    for ($i = $startRow; $i < count($sheetData); $i++) {
                        $rowData = $sheetData[$i];
                        
                        // Skip baris kosong
                        if (empty(array_filter($rowData, function($val) {
                            return $val !== null && $val !== '';
                        }))) {
                            continue;
                        }
                        
                        // Extract data berdasarkan mapping
                        $data = array_merge($default_values, [
                            'npp' => '',
                            'nama' => ''
                        ]);
                        
                        foreach ($column_mapping as $colIndex => $dbField) {
                            if (isset($rowData[$colIndex])) {
                                $data[$dbField] = safe_trim($rowData[$colIndex]);
                            }
                        }
                        
                        // Validasi data wajib
                        if (empty($data['npp'])) {
                            $errors[] = "Baris " . ($i+1) . ": NPP tidak boleh kosong";
                            $jumlahGagal++;
                            continue;
                        }
                        
                        if (empty($data['nama'])) {
                            $errors[] = "Baris " . ($i+1) . ": Nama tidak boleh kosong";
                            $jumlahGagal++;
                            continue;
                        }
                        
                        // Format data
                        $npp_user = $data['npp'];
                        $nik_user = $data['nik'];
                        $npwp_user = $data['npwp'];
                        $norek_user = $data['norek'];
                        $nama_user = mysqli_real_escape_string($koneksi, $data['nama']);
                        $nohp_user = preg_replace('/[^0-9]/', '', $data['nohp']);
                        $role_user = !empty($data['role']) ? $data['role'] : 'staff';
                        $honor_persks = isset($data['honor']) && $data['honor'] !== '' ? floatval($data['honor']) : 0;
                        
                        // Cek apakah NPP sudah ada
                        $cek = mysqli_query($koneksi,
                            "SELECT id_user FROM t_user WHERE npp_user = '$npp_user'"
                        );
                        
                        if (mysqli_num_rows($cek) > 0) {
                            if ($overwrite) {
$update = mysqli_query($koneksi, "
    UPDATE t_user SET
        nik_user = '$nik_user',
        npwp_user = '$npwp_user',
        norek_user = '$norek_user',
        nama_user = '$nama_user',
        nohp_user = '$nohp_user',
        role_user = '$role_user',
        honor_persks = '$honor_persks'
    WHERE npp_user = '$npp_user'
");                                
                                if ($update) {
                                    $jumlahData++;
                                } else {
                                    $errors[] = "Baris " . ($i+1) . ": Gagal update data '$npp_user' - " . mysqli_error($koneksi);
                                    $jumlahGagal++;
                                }
                            } else {
                                $errors[] = "Baris " . ($i+1) . ": NPP '$npp_user' sudah ada (gunakan opsi 'Timpa data')";
                                $jumlahGagal++;
                            }
                            continue;
                        }
                        
                        // Insert data baru
                        $pw_user = password_hash($npp_user, PASSWORD_DEFAULT);

$insert = mysqli_query($koneksi, "
    INSERT INTO t_user 
    (npp_user, nik_user, npwp_user, norek_user, nama_user, nohp_user, pw_user, role_user, honor_persks)
    VALUES
    ('$npp_user', '$nik_user', '$npwp_user', '$norek_user', '$nama_user', '$nohp_user', '$pw_user', '$role_user', '$honor_persks')
");

                        
                        if ($insert) {
                            $jumlahData++;
                        } else {
                            $errors[] = "Baris " . ($i+1) . ": Gagal menyimpan data '$npp_user' - " . mysqli_error($koneksi);
                            $jumlahGagal++;
                        }
                    }
                    
                    // Clean up temp file
                    unlink($temp_file_path);
                    unset($_SESSION['upload_temp_file']);
                    
                    if ($jumlahData > 0) {
                        $success_message = "✅ Berhasil mengimport <strong>$jumlahData</strong> data user.";
                        if ($jumlahGagal > 0) {
                            $success_message .= " <strong>$jumlahGagal</strong> data gagal.";
                        }
                        if (!empty($errors)) {
                            $error_message = "⚠️ Beberapa error ditemukan:<br>" . implode('<br>', array_slice($errors, 0, 10));
                            if (count($errors) > 10) {
                                $error_message .= '<br>... dan ' . (count($errors) - 10) . ' error lainnya';
                            }
                        }
                    } else {
                        $error_message = "❌ Tidak ada data yang berhasil diimport.";
                        if (!empty($errors)) {
                            $error_message .= '<br>' . implode('<br>', array_slice($errors, 0, 10));
                        }
                    }
                }
                
            } catch (Exception $e) {
                $error_message = "❌ Terjadi kesalahan: " . $e->getMessage();
            }
        } else {
            $error_message = "❌ File sementara tidak ditemukan.";
            unset($_SESSION['upload_temp_file']);
        }
    }
    
    // MANUAL INPUT
    elseif (isset($_POST['submit_manual'])) {
        $manual_npp = mysqli_real_escape_string($koneksi, $_POST['manual_npp'] ?? '');
        $manual_nik = mysqli_real_escape_string($koneksi, $_POST['manual_nik'] ?? '');
        $manual_npwp = mysqli_real_escape_string($koneksi, $_POST['manual_npwp'] ?? '');
        $manual_norek = mysqli_real_escape_string($koneksi, $_POST['manual_norek'] ?? '');
        $manual_nama = mysqli_real_escape_string($koneksi, $_POST['manual_nama'] ?? '');
        $manual_nohp = mysqli_real_escape_string($koneksi, $_POST['manual_nohp'] ?? '');
        $manual_role = mysqli_real_escape_string($koneksi, $_POST['manual_role'] ?? 'staff');
        
        // Clean phone number
        $manual_nohp = preg_replace('/[^0-9]/', '', $manual_nohp);
        
        // Validate NPP format
        if (!preg_match('/^\d{4}\.\d{2}\.\d{4}\.\d{3}$/', $manual_npp)) {
            $error_message = '❌ Format NPP tidak valid!';
        } 
        // Validate NIK
        elseif (!preg_match('/^\d{16}$/', $manual_nik)) {
            $error_message = '❌ NIK harus 16 digit angka!';
        }
        elseif (empty($manual_nama)) {
            $error_message = '❌ Nama tidak boleh kosong!';
        }
        else {
            // Check if NPP already exists
            $check = mysqli_query($koneksi, "SELECT id_user FROM t_user WHERE npp_user = '$manual_npp'");
            
            if (mysqli_num_rows($check) > 0) {
                $error_message = "⚠️ NPP sudah terdaftar!";
            } else {
                $manual_pw = password_hash($manual_npp, PASSWORD_DEFAULT);
                $manual_honor = 0;
                
                $insert_manual = mysqli_query($koneksi, "
                    INSERT INTO t_user 
                    (npp_user, nik_user, npwp_user, norek_user, nama_user, nohp_user, pw_user, role_user, honor_persks)
                    VALUES
                    ('$manual_npp', '$manual_nik', '$manual_npwp', '$manual_norek', '$manual_nama', '$manual_nohp', '$manual_pw', '$manual_role', '$manual_honor')
                ");
                
                if ($insert_manual) {
                    $success_message = "✅ Data user berhasil disimpan!";
                } else {
                    $error_message = "❌ Gagal menyimpan data: " . mysqli_error($koneksi);
                }
            }
        }
    }
}

// Function untuk memproses file untuk preview
function process_file_for_preview($file_path, $file_ext, $field_mapping) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    try {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
        if ($file_ext == 'csv') {
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
        }
        
        $spreadsheet = $reader->load($file_path);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        
        // Deteksi header
        $headerRow = null;
        for ($i = 0; $i < min(10, count($sheetData)); $i++) {
            $headerMatchCount = 0;
            foreach ($sheetData[$i] as $cell) {
                $cellLower = strtolower(safe_trim($cell));
                foreach ($field_mapping as $field => $keywords) {
                    if (in_array($cellLower, $keywords)) {
                        $headerMatchCount++;
                        break;
                    }
                }
            }
            
            if ($headerMatchCount >= 2) {
                $headerRow = $i;
                break;
            }
        }
        
        if ($headerRow === null) {
            $headerRow = 0;
        }
        
        // Map headers
        $column_mapping = [];
        foreach ($sheetData[$headerRow] as $colIndex => $header) {
            $headerLower = strtolower(safe_trim($header));
            $mapped = false;
            
            foreach ($field_mapping as $dbField => $keywords) {
                if (in_array($headerLower, $keywords)) {
                    $column_mapping[$colIndex] = $dbField;
                    $mapped = true;
                    break;
                }
            }
            
            if (!$mapped) {
                $column_mapping[$colIndex] = null;
            }
        }
        
        return [
            'headers' => $sheetData[$headerRow],
            'mapping' => $column_mapping,
            'sample_data' => array_slice($sheetData, $headerRow + 1, 5),
            'total_rows' => count($sheetData) - ($headerRow + 1),
            'header_row' => $headerRow
        ];
        
    } catch (Exception $e) {
        return false;
    }
}

// Clean old temp files (older than 1 hour)
clean_old_temp_files($temp_dir);

function clean_old_temp_files($dir) {
    $files = glob($dir . 'upload_*');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) { // 1 hour
            unlink($file);
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Upload Data User</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Upload Data User</div>
            </div>
        </div>

        <div class="section-body">
            <!-- Display Messages -->
            <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible show fade">
                <div class="alert-body">
                    <button class="close" data-dismiss="alert">
                        <span>×</span>
                    </button>
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= $error_message ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible show fade">
                <div class="alert-body">
                    <button class="close" data-dismiss="alert">
                        <span>×</span>
                    </button>
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= $success_message ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Upload File Excel/CSV</h4>
                        </div>
                        <div class="card-body">
                            <!-- Instructions -->
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle mr-2"></i>petunjuk Penggunaan</h6>
                                <ul class="mb-0 pl-3">
                                    <li>Upload file Excel/CSV dengan format .xls, .xlsx, atau .csv</li>
                                    <li>Sistem akan otomatis mendeteksi kolom</li>
                                    <li>Pilih <strong>"Tidak diimport"</strong> untuk kolom yang ingin dilewati</li>
                                    <li>Hanya kolom <strong>NPP dan Nama</strong> yang wajib dipetakan</li>
                                </ul>
                            </div>

                            <!-- Upload Form (Hanya muncul jika tidak ada preview) -->
                            <?php if (empty($preview_data)): ?>
                            <form action="" method="POST" enctype="multipart/form-data" class="mt-4">
                                <div class="form-group">
                                    <label>Pilih File Data User</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="filexls" name="filexls" accept=".xls,.xlsx,.csv" required>
                                        <label class="custom-file-label" for="filexls">Pilih file Excel/CSV...</label>
                                    </div>
                                    <small class="form-text text-muted">Format: .xls, .xlsx, atau .csv (maks. 10MB)</small>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="overwrite" name="overwrite" value="1">
                                    <label class="form-check-label" for="overwrite">
                                        Timpa data yang sudah ada (update berdasarkan NPP)
                                    </label>
                                    <small class="form-text text-muted">Jika dicentang, data dengan NPP yang sama akan diperbarui</small>
                                </div>

                                <div class="form-group">
                                    <button type="submit" name="submit" class="btn btn-primary btn-icon icon-left">
                                        <i class="fas fa-search mr-2"></i> Upload & Deteksi File
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="clearUploadForm(this)">
    Reset
</button>

                                </div>
                            </form>
                            <?php endif; ?>

                            <!-- Preview & Mapping Section -->
                            <?php if (!empty($preview_data)): ?>
                            <div class="mt-5 border rounded p-4">
                                <h5><i class="fas fa-table mr-2"></i>Konfirmasi Mapping Kolom</h5>
                                <p class="text-muted">Total baris data: <strong><?= $preview_data['total_rows'] ?></strong> | Baris header: <strong><?= $preview_data['header_row'] + 1 ?></strong></p>
                                
                                <form action="" method="POST">
                                    <input type="hidden" name="temp_file" value="<?= safe_html($preview_data['temp_file'] ?? '') ?>">
                                    <input type="hidden" name="header_row" value="<?= $preview_data['header_row'] ?>">
                                    <input type="hidden" name="overwrite" value="<?= $_POST['overwrite'] ?? '0' ?>">
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th width="20%">Kolom File</th>
                                                    <th width="30%">Contoh Data (5 baris pertama)</th>
                                                    <th width="50%">Mapping ke Database</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($preview_data['headers'] as $colIndex => $header): 
                                                    $is_detected = isset($preview_data['mapping'][$colIndex]) && $preview_data['mapping'][$colIndex] !== null;
                                                    $detected_value = $preview_data['mapping'][$colIndex] ?? '';
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="font-weight-bold"><?= safe_html($header) ?: '<em class="text-muted">(kosong)</em>' ?></div>
                                                        <div class="small text-muted">Kolom <?= chr(65 + $colIndex) ?></div>
                                                    </td>
                                                    <td class="small">
                                                        <?php 
                                                        $sample = '';
                                                        $sample_count = 0;
                                                        foreach ($preview_data['sample_data'] as $sampleRow) {
                                                            if (isset($sampleRow[$colIndex]) && $sampleRow[$colIndex] !== null && $sampleRow[$colIndex] !== '') {
                                                                $sample .= '<div class="mb-1">' . safe_html(substr($sampleRow[$colIndex], 0, 50)) . 
                                                                          (strlen($sampleRow[$colIndex]) > 50 ? '...' : '') . '</div>';
                                                                $sample_count++;
                                                            }
                                                        }
                                                        echo $sample ?: '<div class="text-muted"><em>Data kosong</em></div>';
                                                        ?>
                                                        <?php if ($sample_count < count($preview_data['sample_data'])): ?>
                                                        <div class="text-muted">
                                                            <small><?= count($preview_data['sample_data']) - $sample_count ?> baris kosong</small>
                                                        </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="form-group mb-2">
                                                            <select class="form-control" name="manual_mapping[<?= $colIndex ?>]" 
                                                                    data-original="<?= $detected_value ?>">
                                                                <option value="skip">-- Tidak diimport --</option>
                                                                <option value="npp" <?= $detected_value == 'npp' ? 'selected' : '' ?>>NPP (Wajib)</option>
                                                                <option value="nama" <?= $detected_value == 'nama' ? 'selected' : '' ?>>Nama (Wajib)</option>
                                                                <option value="nik" <?= $detected_value == 'nik' ? 'selected' : '' ?>>NIK</option>
                                                                <option value="npwp" <?= $detected_value == 'npwp' ? 'selected' : '' ?>>NPWP</option>
                                                                <option value="norek" <?= $detected_value == 'norek' ? 'selected' : '' ?>>No Rekening</option>
                                                                <option value="nohp" <?= $detected_value == 'nohp' ? 'selected' : '' ?>>No HP</option>
                                                                <option value="role" <?= $detected_value == 'role' ? 'selected' : '' ?>>Role</option>
                                                                <option value="honor" <?= $detected_value == 'honor' ? 'selected' : '' ?>>Honor/SKS</option>
                                                            </select>
                                                        </div>
                                                        <div class="small text-muted">
                                                            <?php if ($is_detected): ?>
                                                            <i class="fas fa-lightbulb text-warning"></i> 
                                                            Terdeteksi: <span class="font-weight-bold"><?= ucfirst($detected_value) ?></span>
                                                            <?php else: ?>
                                                            <i class="fas fa-question-circle text-info"></i> 
                                                            Tidak terdeteksi otomatis
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                                            <div>
                                                <h6 class="mb-1">Perhatian!</h6>
                                                <p class="mb-0">
                                                    Pastikan kolom <strong class="text-danger">NPP</strong> dan <strong class="text-danger">Nama</strong> sudah dipetakan dengan benar.<br>
                                                    Kolom lainnya bisa dipilih <strong>"Tidak diimport"</strong> jika tidak diperlukan.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group d-flex justify-content-between">
                                        <div>
                                            <button type="submit" name="confirm_mapping" class="btn btn-success btn-icon icon-left btn-lg">
                                                <i class="fas fa-upload mr-2"></i> Import Data
                                            </button>
                                            <button type="submit" name="detect_again" class="btn btn-warning btn-icon icon-left">
                                                <i class="fas fa-sync mr-2"></i> Deteksi Ulang
                                            </button>
                                        </div>
                                        <div>
                                            <a href="upload_user.php" class="btn btn-secondary btn-icon icon-left">
                                                <i class="fas fa-times mr-2"></i> Batalkan
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>

                            <!-- Manual Input Form -->
                            <div class="mt-5">
                                <h5><i class="fas fa-keyboard mr-2"></i>Input Manual</h5>
                                <form action="" method="POST" class="mt-3">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>NPP <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="manual_npp" placeholder="0686.11.1995.071" required>
                                            <small class="form-text text-muted">Format: XXXX.XX.XXXX.XXX</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>NIK <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="manual_nik" placeholder="3374010101950001" maxlength="16" required>
                                            <small class="form-text text-muted">16 digit Nomor Induk Kependudukan</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>NPWP <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="manual_npwp" placeholder="12.345.678.9-012.000" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Nomor Rekening <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="manual_norek" placeholder="1410001234567" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="manual_nama" placeholder="Dr. Andi Prasetyo, M.Kom" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Nomor HP <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="manual_nohp" placeholder="081234567890" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Role <span class="text-danger">*</span></label>
                                            <select class="form-control" name="manual_role" required>
                                                <option value="">Pilih Role</option>
                                                <option value="admin">Admin</option>
                                                <option value="koordinator">Koordinator</option>
                                                <option value="staff" selected>Staff</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" name="submit_manual" class="btn btn-success btn-icon icon-left">
                                            <i class="fas fa-save"></i> Simpan Data Manual
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Preview Data -->
                            <div class="mt-5">
                                <h5><i class="fas fa-table mr-2"></i>Data User Terbaru</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>NPP</th>
                                                <th>Nama</th>
                                                <th>NIK</th>
                                                <th>NPWP</th>
                                                <th>No Rekening</th>
                                                <th>No HP</th>
                                                <th>Honor / SKS</th>
                                                <th>Role</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = mysqli_query($koneksi, 
                                                "SELECT npp_user, nama_user, nik_user, npwp_user, norek_user, nohp_user, honor_persks, role_user 
                                                 FROM t_user 
                                                 ORDER BY id_user DESC 
                                                 LIMIT 10"
                                            );
                                            while ($row = mysqli_fetch_assoc($query)): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['npp_user']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_user']) ?></td>
                                                <td><?= htmlspecialchars($row['nik_user']) ?></td>
                                                <td><?= htmlspecialchars($row['npwp_user']) ?></td>
                                                <td><?= htmlspecialchars($row['norek_user']) ?></td>
                                                <td><?= htmlspecialchars($row['nohp_user']) ?></td>
                                                <td><?= htmlspecialchars($row['honor_persks']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= 
                                                        $row['role_user'] === 'admin' ? 'danger' :
                                                        ($row['role_user'] === 'koordinator' ? 'warning' : 'secondary')
                                                    ?>">
                                                        <?= ucfirst($row['role_user']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
// File input label update
document.querySelector('.custom-file-input')?.addEventListener('change', function(e) {
    var fileName = this.files[0] ? this.files[0].name : "Pilih file...";
    var nextSibling = e.target.nextElementSibling;
    nextSibling.innerText = fileName;
});

// Reset to original detected value
document.querySelectorAll('select[name^="manual_mapping"]').forEach(select => {
    const original = select.getAttribute('data-original');
    if (original) {
        select.value = original;
    }
});

function clearUploadForm(btn) {
    const form = btn.closest('form'); // ⬅️ form yang BENAR
    if (!form) return;

    form.reset();

    const fileInput = form.querySelector('.custom-file-input');
    const fileLabel = form.querySelector('.custom-file-label');

    if (fileInput) fileInput.value = '';
    if (fileLabel) fileLabel.innerText = 'Pilih file Excel/CSV...';
}
</script>

<?php 
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>