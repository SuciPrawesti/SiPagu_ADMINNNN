<?php
/**
 * UPLOAD DATA PANITIA - SiPagu (VERSION FINAL - IMPROVED)
 * Halaman untuk upload data panitia dari Excel dengan pendekatan fleksibel
 * Lokasi: admin/upload_panitia.php
 */

// Include required files
require_once __DIR__ . '../../auth.php';
require_once __DIR__ . '../../../config.php';

// Set page title
$page_title = "Upload Data Panitia";

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

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

// Normalisasi jabatan dengan format yang lebih manusiawi
function normalizeJabatan($jabatan) {
    $trimmed = trim($jabatan);
    
    if (empty($trimmed)) {
        return '';
    }
    
    // Ubah ke lowercase dulu
    $lower = strtolower($trimmed);
    
    // Daftar kata penghubung yang tetap lowercase (kecuali di awal)
    $small_words = ['dan', 'atau', 'dari', 'untuk', 'pada', 'dengan', 'di', 'ke', 'dalam'];
    
    $words = explode(' ', $lower);
    $result = [];
    
    foreach ($words as $index => $word) {
        // Capitalize jika:
        // 1. Kata pertama, ATAU
        // 2. Bukan kata penghubung
        if ($index === 0 || !in_array($word, $small_words)) {
            $result[] = ucfirst($word);
        } else {
            $result[] = $word;
        }
    }
    
    return implode(' ', $result);
}

// Konversi nilai honor dari format manusiawi ke integer
function parseHonorToInt($value) {
    if ($value === null || $value === '') {
        return 0;
    }
    
    // Hapus semua karakter non-numeric
    $cleaned = preg_replace('/[^0-9]/', '', $value);
    
    // Konversi ke integer
    return (int) $cleaned;
}

// Field mapping configuration - DIPERBARUI untuk lebih fleksibel
$field_mapping = [
    'jbtn_pnt' => ['jabatan', 'jbtn', 'jbtn_pnt', 'posisi', 'role', 'position', 'nama jabatan', 'jabatan panitia', 'nama posisi'],
    'honor_std' => ['honor', 'honor standar', 'standar', 'honor dasar', 'gaji', 'gaji pokok', 'honor pokok', 'nominal', 'jumlah', 'total'],
    'honor_p1' => ['honor periode 1', 'periode 1', 'p1', 'tambahan 1', 'bonus 1', 'extra 1', 'insentif 1'],
    'honor_p2' => ['honor periode 2', 'periode 2', 'p2', 'tambahan 2', 'bonus 2', 'extra 2', 'insentif 2']
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
            $unique_name = 'upload_panitia_' . time() . '_' . uniqid() . '.' . $file_ext;
            $temp_file_path = $temp_dir . $unique_name;
            
            // Save file to temp directory
            if (move_uploaded_file($file_tmp, $temp_file_path)) {
                // Process file untuk preview
                $preview_data = process_file_for_preview($temp_file_path, $file_ext, $field_mapping);
                if ($preview_data) {
                    $preview_data['temp_file'] = $unique_name;
                    $_SESSION['upload_temp_file_panitia'] = $unique_name;
                    $_SESSION['overwrite_option_panitia'] = $_POST['overwrite'] ?? '0';
                    $success_message = "✅ Header terdeteksi. Silakan konfirmasi mapping kolom:";
                } else {
                    $error_message = "❌ Gagal membaca file. Pastikan file berformat benar.";
                    unlink($temp_file_path);
                }
            } else {
                $error_message = "❌ Gagal menyimpan file sementara.";
            }
        }
    }
    
    // PREVIEW MODE - DETEKSI ULANG
    elseif (isset($_POST['detect_again']) && isset($_SESSION['upload_temp_file_panitia'])) {
        $temp_file = $_SESSION['upload_temp_file_panitia'];
        $temp_file_path = $temp_dir . $temp_file;
        $file_ext = strtolower(pathinfo($temp_file, PATHINFO_EXTENSION));
        
        if (file_exists($temp_file_path)) {
            $preview_data = process_file_for_preview($temp_file_path, $file_ext, $field_mapping);
            if ($preview_data) {
                $preview_data['temp_file'] = $temp_file;
                $success_message = "✅ Header dideteksi ulang. Silakan konfirmasi mapping:";
            }
        } else {
            $error_message = "❌ File sementara tidak ditemukan. Upload ulang file.";
            unset($_SESSION['upload_temp_file_panitia']);
        }
    }
    
    // CONFIRM MAPPING DAN IMPORT DATA
    elseif (isset($_POST['confirm_mapping']) && isset($_SESSION['upload_temp_file_panitia'])) {
        $temp_file = $_SESSION['upload_temp_file_panitia'];
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
                if (!in_array('jbtn_pnt', $mapped_fields)) {
                    $error_message = "❌ Kolom <strong>Jabatan Panitia</strong> wajib dipetakan untuk proses import!";
                } else {
                    $startRow = $headerRow + 1;
                    $jumlahData = 0;
                    $jumlahGagal = 0;
                    $errors = [];
                    $overwrite = isset($_SESSION['overwrite_option_panitia']) && $_SESSION['overwrite_option_panitia'] == '1';
                    
                    // Mulai transaksi database untuk konsistensi
                    mysqli_begin_transaction($koneksi);
                    
                    try {
                        for ($i = $startRow; $i < count($sheetData); $i++) {
                            $rowData = $sheetData[$i];
                            
                            // Skip baris kosong
                            if (empty(array_filter($rowData, function($val) {
                                return $val !== null && $val !== '';
                            }))) {
                                continue;
                            }
                            
                            // Extract data berdasarkan mapping
                            $data = [
                                'jbtn_pnt' => '',
                                'honor_std' => 0,
                                'honor_p1' => 0,
                                'honor_p2' => 0
                            ];
                            
                            foreach ($column_mapping as $colIndex => $dbField) {
                                if (isset($rowData[$colIndex])) {
                                    if ($dbField === 'jbtn_pnt') {
                                        $data[$dbField] = safe_trim($rowData[$colIndex]);
                                    } else {
                                        $data[$dbField] = $rowData[$colIndex];
                                    }
                                }
                            }
                            
                            // Validasi data wajib
                            if (empty($data['jbtn_pnt'])) {
                                $errors[] = "Baris " . ($i+1) . ": Jabatan panitia tidak boleh kosong";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            // Normalisasi jabatan (Title Case, bukan UPPERCASE)
                            $jabatan_normalized = normalizeJabatan($data['jbtn_pnt']);
                            $jbtn_pnt_escaped = mysqli_real_escape_string($koneksi, $jabatan_normalized);
                            
                            // Parse honor menjadi integer
                            $honor_std = parseHonorToInt($data['honor_std'] ?? 0);
                            $honor_p1 = parseHonorToInt($data['honor_p1'] ?? 0);
                            $honor_p2 = parseHonorToInt($data['honor_p2'] ?? 0);
                            
                            // Cek apakah jabatan sudah ada (case-insensitive)
                            $cek = mysqli_query($koneksi,
                                "SELECT id_pnt, jbtn_pnt FROM t_panitia WHERE LOWER(TRIM(jbtn_pnt)) = LOWER('$jbtn_pnt_escaped')"
                            );
                            
                            if (mysqli_num_rows($cek) > 0) {
                                if ($overwrite) {
                                    $row = mysqli_fetch_assoc($cek);
                                    $id_pnt = $row['id_pnt'];
                                    $jbtn_original = $row['jbtn_pnt'];
                                    
                                    $update = mysqli_query($koneksi, "
                                        UPDATE t_panitia SET
                                            honor_std = '$honor_std',
                                            honor_p1 = '$honor_p1',
                                            honor_p2 = '$honor_p2'
                                        WHERE id_pnt = '$id_pnt'
                                    ");
                                    
                                    if ($update) {
                                        $jumlahData++;
                                    } else {
                                        $errors[] = "Baris " . ($i+1) . ": Gagal update data '$jbtn_original' - " . mysqli_error($koneksi);
                                        $jumlahGagal++;
                                    }
                                } else {
                                    $errors[] = "Baris " . ($i+1) . ": Jabatan <strong>'{$jabatan_normalized}'</strong> sudah ada (gunakan opsi 'Timpa data')";
                                    $jumlahGagal++;
                                }
                                continue;
                            }
                            
                            // Insert data baru
                            $insert = mysqli_query($koneksi, "
                                INSERT INTO t_panitia 
                                (jbtn_pnt, honor_std, honor_p1, honor_p2)
                                VALUES
                                ('$jbtn_pnt_escaped', '$honor_std', '$honor_p1', '$honor_p2')
                            ");
                            
                            if ($insert) {
                                $jumlahData++;
                            } else {
                                $errors[] = "Baris " . ($i+1) . ": Gagal menyimpan data '$jabatan_normalized' - " . mysqli_error($koneksi);
                                $jumlahGagal++;
                            }
                        }
                        
                        // Commit transaksi jika semua sukses
                        mysqli_commit($koneksi);
                        
                    } catch (Exception $e) {
                        // Rollback jika ada error
                        mysqli_rollback($koneksi);
                        throw $e;
                    }
                    
                    // Clean up temp file
                    unlink($temp_file_path);
                    unset($_SESSION['upload_temp_file_panitia']);
                    unset($_SESSION['overwrite_option_panitia']);
                    
                    if ($jumlahData > 0) {
                        $success_message = "✅ Berhasil mengimport <strong>$jumlahData</strong> data panitia.";
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
                
                // Clean up jika masih ada file sementara
                if (isset($temp_file_path) && file_exists($temp_file_path)) {
                    unlink($temp_file_path);
                }
                unset($_SESSION['upload_temp_file_panitia']);
                unset($_SESSION['overwrite_option_panitia']);
            }
        } else {
            $error_message = "❌ File sementara tidak ditemukan.";
            unset($_SESSION['upload_temp_file_panitia']);
            unset($_SESSION['overwrite_option_panitia']);
        }
    }
    
    // MANUAL INPUT
    elseif (isset($_POST['submit_manual'])) {
        $manual_jbtn = $_POST['manual_jbtn'] ?? '';
        $manual_honor_std = $_POST['manual_honor_std'] ?? '0';
        $manual_honor_p1 = $_POST['manual_honor_p1'] ?? '0';
        $manual_honor_p2 = $_POST['manual_honor_p2'] ?? '0';
        
        // Validasi
        if (empty($manual_jbtn)) {
            $error_message = '❌ Jabatan panitia wajib diisi!';
        } 
        // Validasi angka (menggunakan parseHonorToInt untuk konsistensi)
        else {
            // Parse honor
            $honor_std = parseHonorToInt($manual_honor_std);
            $honor_p1 = parseHonorToInt($manual_honor_p1);
            $honor_p2 = parseHonorToInt($manual_honor_p2);
            
            // Normalisasi jabatan (Title Case)
            $jabatan_normalized = normalizeJabatan($manual_jbtn);
            $jbtn_pnt_escaped = mysqli_real_escape_string($koneksi, $jabatan_normalized);
            
            // Check if data already exists (case-insensitive)
            $check = mysqli_query($koneksi,
                "SELECT id_pnt FROM t_panitia WHERE LOWER(TRIM(jbtn_pnt)) = LOWER('$jbtn_pnt_escaped')"
            );
            
            if (mysqli_num_rows($check) > 0) {
                $error_message = "⚠️ Jabatan '<strong>$jabatan_normalized</strong>' sudah ada!";
            } else {
                $insert_manual = mysqli_query($koneksi, "
                    INSERT INTO t_panitia 
                    (jbtn_pnt, honor_std, honor_p1, honor_p2)
                    VALUES
                    ('$jbtn_pnt_escaped', '$honor_std', '$honor_p1', '$honor_p2')
                ");
                
                if ($insert_manual) {
                    $success_message = "✅ Data panitia berhasil disimpan!";
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
        
        // Deteksi header - Cukup 1 header yang match untuk file sederhana
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
            
            // Cukup 1 header yang match untuk file sederhana
            if ($headerMatchCount >= 1) {
                $headerRow = $i;
                break;
            }
        }
        
        // Jika tidak ada yang match, gunakan baris pertama
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
    $files = glob($dir . 'upload_panitia_*');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) { // 1 hour
            unlink($file);
        }
    }
}

// Ambil data panitia terbaru untuk preview
$query = mysqli_query($koneksi, 
    "SELECT jbtn_pnt, honor_std, honor_p1, honor_p2 
     FROM t_panitia 
     ORDER BY id_pnt DESC 
     LIMIT 10"
);
$recent_panitia = [];
while ($row = mysqli_fetch_assoc($query)) {
    $recent_panitia[] = $row;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Upload Data Panitia</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Upload Data Panitia</div>
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
                                <h6><i class="fas fa-info-circle mr-2"></i>Petunjuk Penggunaan</h6>
                                <ul class="mb-0 pl-3">
                                    <li>Kolom wajib: <strong>Jabatan</strong></li>
                                    <li>Kolom honor opsional: diisi 0 jika kosong</li>
                                    <li>Format honor: <code>500000</code>, <code>500.000</code>, atau <code>Rp 500.000</code></li>
                                    <li>Jabatan akan dinormalisasi: "ketua panitia" → "Ketua Panitia"</li>
                                </ul>
                            </div>

                            <!-- Upload Form (Hanya muncul jika tidak ada preview) -->
                            <?php if (empty($preview_data)): ?>
                            <form action="" method="POST" enctype="multipart/form-data" class="mt-4">
                                <div class="form-group">
                                    <label>Pilih File Data Panitia</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="filexls" name="filexls" accept=".xls,.xlsx,.csv" required>
                                        <label class="custom-file-label" for="filexls">Pilih file Excel/CSV...</label>
                                    </div>
                                    <small class="form-text text-muted">Format: .xls, .xlsx, atau .csv (maks. 10MB)</small>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="overwrite" name="overwrite" value="1">
                                    <label class="form-check-label text-warning font-weight-bold" for="overwrite">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Timpa data yang sudah ada
                                    </label>
                                    <small class="form-text text-muted">
                                        Jika dicentang, data dengan <strong>jabatan yang sama</strong> akan diperbarui.
                                        <br><span class="text-danger">Perhatian: Aksi ini tidak dapat dibatalkan!</span>
                                    </small>
                                </div>

                                <div class="form-group">
                                    <button type="submit" name="submit" class="btn btn-primary btn-icon icon-left">
                                        <i class="fas fa-search mr-2"></i> Upload & Deteksi File
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="clearUploadForm(this)">
                                        <i class="fas fa-redo mr-2"></i> Reset Form
                                    </button>
                                </div>
                            </form>
                            <?php endif; ?>

                            <!-- Preview & Mapping Section -->
                            <?php if (!empty($preview_data)): ?>
                            <div class="mt-5 border rounded p-4 bg-light">
                                <h5><i class="fas fa-table mr-2"></i>🔍 Konfirmasi Mapping Kolom</h5>
                                <p class="text-muted">Total baris data: <strong><?= $preview_data['total_rows'] ?></strong> | Baris header: <strong><?= $preview_data['header_row'] + 1 ?></strong></p>
                                
                                <form action="" method="POST" id="mappingForm">
                                    <input type="hidden" name="temp_file" value="<?= safe_html($preview_data['temp_file'] ?? '') ?>">
                                    <input type="hidden" name="header_row" value="<?= $preview_data['header_row'] ?>">
                                    
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
                                                    $sample_data = '';
                                                    $sample_count = 0;
                                                    foreach ($preview_data['sample_data'] as $sampleRow) {
                                                        if (isset($sampleRow[$colIndex]) && $sampleRow[$colIndex] !== null && $sampleRow[$colIndex] !== '') {
                                                            $sample_data .= '<div class="mb-1">' . safe_html(substr($sampleRow[$colIndex], 0, 50)) . 
                                                                      (strlen($sampleRow[$colIndex]) > 50 ? '...' : '') . '</div>';
                                                            $sample_count++;
                                                        }
                                                    }
                                                    $sample_html = $sample_data ?: '<div class="text-muted"><em>Data kosong</em></div>';
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="font-weight-bold"><?= safe_html($header) ?: '<em class="text-muted">(kosong)</em>' ?></div>
                                                        <div class="small text-muted">Kolom <?= chr(65 + $colIndex) ?></div>
                                                    </td>
                                                    <td class="small">
                                                        <?= $sample_html ?>
                                                        <?php if ($sample_count < count($preview_data['sample_data'])): ?>
                                                        <div class="text-muted">
                                                            <small><?= count($preview_data['sample_data']) - $sample_count ?> baris kosong</small>
                                                        </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="form-group mb-2">
                                                            <select class="form-control mapping-select" name="manual_mapping[<?= $colIndex ?>]" 
                                                                    data-original="<?= $detected_value ?>">
                                                                <option value="skip">-- Tidak diimport --</option>
                                                                <option value="jbtn_pnt" <?= $detected_value == 'jbtn_pnt' ? 'selected' : '' ?>>Jabatan Panitia (Wajib)</option>
                                                                <option value="honor_std" <?= $detected_value == 'honor_std' ? 'selected' : '' ?>>Honor Standar</option>
                                                                <option value="honor_p1" <?= $detected_value == 'honor_p1' ? 'selected' : '' ?>>Honor Periode 1</option>
                                                                <option value="honor_p2" <?= $detected_value == 'honor_p2' ? 'selected' : '' ?>>Honor Periode 2</option>
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
                                                <h6 class="mb-1">📋 Petunjuk Mapping</h6>
                                                <p class="mb-0">
                                                    1. <strong class="text-danger">Jabatan Panitia wajib dipetakan</strong><br>
                                                    2. Kolom honor (Standar, Periode 1, Periode 2) <strong>opsional</strong> - jika kosong akan diisi 0<br>
                                                    3. Honor akan dikonversi ke integer: <code>500.000</code> → <code>500000</code><br>
                                                    4. Jabatan akan dinormalisasi: <code>ketua panitia</code> → <code>Ketua Panitia</code>
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
                                            <a href="upload_panitia.php" class="btn btn-secondary btn-icon icon-left">
                                                <i class="fas fa-times mr-2"></i> Batalkan
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>

                            <!-- Manual Input Form -->
                            <div class="mt-5">
                                <h5>⌨️ Input Manual</h5>
                                <form action="" method="POST" class="mt-3" id="manualForm">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Jabatan Panitia <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="manual_jbtn" placeholder="Ketua Panitia" required>
                                            <small class="form-text text-muted">Contoh: Ketua Panitia, Sekretaris, Anggota</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Honor Standar <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="manual_honor_std" placeholder="500000" required>
                                            <small class="form-text text-muted">Format: 500000 atau 500.000</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Honor Periode 1</label>
                                            <input type="text" class="form-control" name="manual_honor_p1" placeholder="750000">
                                            <small class="form-text text-muted">Opsional, kosongi jika tidak ada</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Honor Periode 2</label>
                                            <input type="text" class="form-control" name="manual_honor_p2" placeholder="1000000">
                                            <small class="form-text text-muted">Opsional, kosongi jika tidak ada</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" name="submit_manual" class="btn btn-success btn-icon icon-left">
                                            <i class="fas fa-save mr-2"></i> Simpan Data Manual
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="clearManualForm()">
                                            <i class="fas fa-redo mr-2"></i> Reset Form
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Preview Data -->
                            <div class="mt-5">
                                <h5>📊 Data Panitia Terbaru</h5>
                                <?php if (empty($recent_panitia)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i> Belum ada data panitia.
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Jabatan</th>
                                                <th>Honor Standar</th>
                                                <th>Honor Periode 1</th>
                                                <th>Honor Periode 2</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_panitia as $row): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($row['jbtn_pnt']) ?></strong></td>
                                                <td><?= formatRupiah($row['honor_std']) ?></td>
                                                <td><?= formatRupiah($row['honor_p1']) ?></td>
                                                <td><?= formatRupiah($row['honor_p2']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
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
document.querySelectorAll('.mapping-select').forEach(select => {
    const original = select.getAttribute('data-original');
    if (original) {
        select.value = original;
    }
});

function clearUploadForm(btn) {
    const form = btn.closest('form');
    if (!form) return;

    form.reset();

    const fileInput = form.querySelector('.custom-file-input');
    const fileLabel = form.querySelector('.custom-file-label');

    if (fileInput) fileInput.value = '';
    if (fileLabel) fileLabel.innerText = 'Pilih file Excel/CSV...';
}

function clearManualForm() {
    const form = document.getElementById('manualForm');
    if (form) form.reset();
}

// Validasi mapping form
document.getElementById('mappingForm')?.addEventListener('submit', function(e) {
    if (e.submitter && e.submitter.name === 'confirm_mapping') {
        const selects = document.querySelectorAll('select[name^="manual_mapping"]');
        let hasJabatan = false;
        
        selects.forEach(select => {
            if (select.value === 'jbtn_pnt') {
                hasJabatan = true;
            }
        });
        
        if (!hasJabatan) {
            e.preventDefault();
            alert('❌ Error: Kolom "Jabatan Panitia" wajib dipetakan!');
            return false;
        }
    }
});

// Validasi manual form
document.getElementById('manualForm')?.addEventListener('submit', function(e) {
    if (e.submitter && e.submitter.name === 'submit_manual') {
        const jabatanInput = document.querySelector('input[name="manual_jbtn"]');
        const honorInput = document.querySelector('input[name="manual_honor_std"]');
        
        if (!jabatanInput.value.trim()) {
            e.preventDefault();
            alert('❌ Jabatan Panitia wajib diisi!');
            jabatanInput.focus();
            return false;
        }
        
        // Validasi honor (opsional tapi jika diisi harus angka)
        if (honorInput.value.trim() && !/^[\d.,]+$/.test(honorInput.value.replace(/\./g, ''))) {
            e.preventDefault();
            alert('❌ Honor harus berupa angka! Contoh: 500000 atau 500.000');
            honorInput.focus();
            return false;
        }
    }
});
</script>

<?php 
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>