<?php
/**
 * UPLOAD DATA HONOR DOSEN - SiPagu
 * Halaman upload data honor dosen dari Excel
 * Lokasi: admin/upload_honor_dosen.php
 */

// ======================
// SESSION START UNTUK TANGGAL INPUT
// ======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ======================
// AKTIFKAN OUTPUT BUFFERING
// ======================
ob_start();

// ======================
// INCLUDE & KONFIGURASI
// ======================
require_once __DIR__ . '../../auth.php';
require_once __DIR__ . '../../../config.php';

$page_title = "Upload Honor Dosen";

// ======================
// KONFIGURASI MAPPING
// ======================
$mapping_config = [
    'semester' => [
        'required' => true,
        'columns' => ['semester', 'semester_id', 'smst', 'thn_semester', 'semester akademik', 'tahun ajaran'],
        'validation' => function($value) {
            return preg_match('/^\d{4}[12]$/', $value);
        },
        'format' => function($value) {
            $value = preg_replace('/[^0-9]/', '', $value);
            if (strlen($value) === 5) return $value;
            if (strlen($value) === 4) return $value . '1';
            return $value;
        }
    ],
    
    'bulan' => [
        'required' => true,
        'columns' => ['bulan', 'month', 'bln', 'nama_bulan', 'bulan ajar', 'periode'],
        'validation' => function($value) {
            $bulan_valid = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 
                           'juli', 'agustus', 'september', 'oktober', 'november', 'desember',
                           'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec',
                           '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
            return in_array(strtolower($value), $bulan_valid);
        },
        'format' => function($value) {
            $value = strtolower(trim($value));
            
            // Mapping singkatan bulan
            $singkatan = [
                'jan' => 'januari', 'feb' => 'februari', 'mar' => 'maret',
                'apr' => 'april', 'may' => 'mei', 'jun' => 'juni',
                'jul' => 'juli', 'aug' => 'agustus', 'sep' => 'september',
                'oct' => 'oktober', 'nov' => 'november', 'dec' => 'desember'
            ];
            
            if (array_key_exists($value, $singkatan)) {
                return $singkatan[$value];
            }
            
            // Mapping angka bulan
            $angka_bulan = [
                '01' => 'januari', '1' => 'januari',
                '02' => 'februari', '2' => 'februari',
                '03' => 'maret', '3' => 'maret',
                '04' => 'april', '4' => 'april',
                '05' => 'mei', '5' => 'mei',
                '06' => 'juni', '6' => 'juni',
                '07' => 'juli', '7' => 'juli',
                '08' => 'agustus', '8' => 'agustus',
                '09' => 'september', '9' => 'september',
                '10' => 'oktober',
                '11' => 'november',
                '12' => 'desember'
            ];
            
            if (array_key_exists($value, $angka_bulan)) {
                return $angka_bulan[$value];
            }
            
            return $value;
        }
    ],
    
    'id_jadwal' => [
        'required' => true,
        'columns' => ['id_jadwal', 'jadwal_id', 'id_jdwl', 'kode_jadwal', 'jadwal', 'kode jadwal', 'no jadwal'],
        'validation' => function($value) {
            return is_numeric($value) && $value > 0;
        }
    ],
    
    'jml_tm' => [
        'required' => true,
        'columns' => ['jml_tm', 'jumlah_tm', 'tatap_muka', 'tm', 'sks_aktif', 'jumlah tm', 'sesi'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'sks_tempuh' => [
        'required' => true,
        'columns' => ['sks_tempuh', 'sks', 'bobot_sks', 'sks_mata_kuliah'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0 && $value <= 6;
        },
        'default' => 0
    ]
];

function formatSemester($semester)
{
    if (!preg_match('/^\d{4}[12]$/', $semester)) {
        return $semester;
    }

    $tahun = substr($semester, 0, 4);
    $kode  = substr($semester, -1);

    return $tahun . ' ' . ($kode == '1' ? 'Ganjil' : 'Genap');
}

function generateSemester($startYear = 2020, $range = 6)
{
    $list = [];
    $currentYear = date('Y');

    for ($y = $startYear; $y <= $currentYear + $range; $y++) {
        $list[] = $y . '1';
        $list[] = $y . '2';
    }
    return $list;
}

$semesterList = generateSemester(2022, 4);

// ======================
// FLAG UNTUK REDIRECT
// ======================
$should_redirect = false;

// ======================
// PROSES AUTO-MAPPING
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['detect_mapping'])) {
    if (empty($_FILES['filexls']['name'])) {
        $_SESSION['error_message'] = 'Silakan pilih file Excel untuk deteksi mapping.';
        $should_redirect = true;
    } else {
        // OPSI 2: Simpan tanggal upload di session
        $_SESSION['tanggal_input'] = date('d/m/Y');
        
        $file_tmp = $_FILES['filexls']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['filexls']['name'], PATHINFO_EXTENSION));
        $original_name = $_FILES['filexls']['name'];
        
        if (!in_array($file_ext, ['xls', 'xlsx'])) {
            $_SESSION['error_message'] = 'File harus bertipe XLS atau XLSX.';
            $should_redirect = true;
        } else {
            // BUAT FOLDER TEMPORARY
            $tempDir = __DIR__ . '/../storage/tmp_excel/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            // GENERATE NAMA FILE UNIK
            $tempFileName = 'honor_' . time() . '_' . uniqid() . '.' . $file_ext;
            $tempFilePath = $tempDir . $tempFileName;
            
            // COPY FILE KE TEMPORARY FOLDER
            if (move_uploaded_file($file_tmp, $tempFilePath)) {
                require_once __DIR__ . '/../vendor/autoload.php';
                
                try {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tempFilePath);
                    $spreadsheet = $reader->load($tempFilePath);
                    $sheetData = $spreadsheet->getActiveSheet()->toArray();
                    
                    if (count($sheetData) > 0) {
                        $headers = $sheetData[0];
                        $mapping_result = [];
                        
                        // Deteksi mapping otomatis
                        foreach ($headers as $index => $header) {
                            $header_lower = strtolower(trim($header));
                            $mapped = false;
                            
                            foreach ($mapping_config as $field => $config) {
                                foreach ($config['columns'] as $column_name) {
                                    $column_lower = strtolower($column_name);
                                    
                                    // Exact match atau contains
                                    if ($header_lower === $column_lower || 
                                        strpos($header_lower, $column_lower) !== false || 
                                        strpos($column_lower, $header_lower) !== false) {
                                        
                                        $mapping_result[$index] = [
                                            'excel_column' => $index,
                                            'excel_header' => $header,
                                            'system_field' => $field,
                                            'confidence' => 'exact'
                                        ];
                                        $mapped = true;
                                        break 2;
                                    }
                                }
                            }
                            
                            if (!$mapped) {
                                $mapping_result[$index] = [
                                    'excel_column' => $index,
                                    'excel_header' => $header,
                                    'system_field' => ''
                                ];
                            }
                        }
                        
                        // Simpan ke session
                        $_SESSION['temp_mapping'] = $mapping_result;
                        $_SESSION['temp_file'] = $tempFilePath;
                        $_SESSION['temp_file_name'] = $tempFileName;
                        $_SESSION['original_file_name'] = $original_name;
                        
                        $_SESSION['success_message'] = 'Mapping berhasil dideteksi! Semua data akan memiliki tanggal: ' . $_SESSION['tanggal_input'];
                        
                    }
                } catch (Exception $e) {
                    if (file_exists($tempFilePath)) {
                        unlink($tempFilePath);
                    }
                    $_SESSION['error_message'] = "Gagal membaca file Excel: " . $e->getMessage();
                }
            } else {
                $_SESSION['error_message'] = "Gagal menyimpan file sementara.";
            }
        }
    }
    $should_redirect = true;
}

// ======================
// PROSES UPLOAD DENGAN MAPPING
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_mapped'])) {
    if (!isset($_SESSION['temp_mapping']) || !isset($_SESSION['temp_file'])) {
        $_SESSION['error_message'] = 'Silakan lakukan deteksi mapping terlebih dahulu.';
        $should_redirect = true;
    } else {
        $mapping = $_SESSION['temp_mapping'];
        $file_tmp = $_SESSION['temp_file'];
        
        // Validasi file masih ada
        if (!file_exists($file_tmp)) {
            $_SESSION['error_message'] = 'File temporer tidak ditemukan. Silakan upload ulang.';
            unset($_SESSION['temp_mapping']);
            unset($_SESSION['temp_file']);
            unset($_SESSION['temp_file_name']);
            unset($_SESSION['original_file_name']);
            unset($_SESSION['tanggal_input']);
            $should_redirect = true;
        } else {
            // Validasi mapping duplikat
            if (isset($_POST['mapping']) && is_array($_POST['mapping'])) {
                $usedFields = [];
                $duplicateFields = [];
                
                foreach ($_POST['mapping'] as $column => $field) {
                    if (!empty($field) && $field !== '') {
                        if (in_array($field, $usedFields)) {
                            $duplicateFields[] = $field;
                        } else {
                            $usedFields[] = $field;
                        }
                    }
                }
                
                if (!empty($duplicateFields)) {
                    $_SESSION['error_message'] = 'Field berikut dipetakan lebih dari satu kolom: ' . 
                                                implode(', ', array_unique($duplicateFields)) . 
                                                '. Harap perbaiki mapping.';
                    $should_redirect = true;
                }
            }
            
            // Validasi field wajib
            if (!isset($_SESSION['error_message'])) {
                $required_fields = ['semester', 'bulan', 'id_jadwal'];
                $mapped_fields = [];
                
                if (isset($_POST['mapping']) && is_array($_POST['mapping'])) {
                    foreach ($_POST['mapping'] as $field) {
                        if (!empty($field) && $field !== '') {
                            $mapped_fields[] = $field;
                        }
                    }
                }
                
                $missing_fields = array_diff($required_fields, array_unique($mapped_fields));
                
                if (!empty($missing_fields)) {
                    $_SESSION['error_message'] = 'Field wajib belum dipetakan: ' . implode(', ', $missing_fields);
                    $should_redirect = true;
                }
            }
            
            // Jika validasi lolos, proses upload
            if (!isset($_SESSION['error_message'])) {
                try {
                    require_once __DIR__ . '/../vendor/autoload.php';
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_tmp);
                    $spreadsheet = $reader->load($file_tmp);
                    $sheetData = $spreadsheet->getActiveSheet()->toArray();
                    
                    $jumlahData = 0;
                    $jumlahGagal = 0;
                    $errors = [];
                    
                    // OPSI 2: Pastikan tanggal sudah ada di session
                    if (!isset($_SESSION['tanggal_input'])) {
                        $_SESSION['tanggal_input'] = date('d/m/Y');
                    }
                    
                    // Proses data
                    for ($i = 1; $i < count($sheetData); $i++) {
                        $row_data = $sheetData[$i];
                        
                        // Prepare data
                        $data = [];
                        foreach ($_POST['mapping'] as $column => $field) {
                            if (!empty($field) && $field !== '') {
                                $value = $row_data[$column] ?? '';
                                $value = is_string($value) ? trim($value) : $value;
                                
                                if (isset($mapping_config[$field]['format'])) {
                                    $value = $mapping_config[$field]['format']($value);
                                }
                                
                                $data[$field] = $value;
                            }
                        }
                        
                        // Validasi data
                        $is_valid = true;
                        $error_msg = [];
                        
                        foreach ($mapping_config as $field => $config) {
                            if ($config['required'] && (!isset($data[$field]) || $data[$field] === '')) {
                                if ($field !== 'jml_tm' && $field !== 'sks_tempuh') {
                                    $is_valid = false;
                                    $error_msg[] = "$field kosong";
                                } elseif (isset($config['default'])) {
                                    $data[$field] = $config['default'];
                                }
                            }
                            
                            if (isset($data[$field]) && $data[$field] !== '' && isset($config['validation'])) {
                                if (!$config['validation']($data[$field])) {
                                    $is_valid = false;
                                    $error_msg[] = "$field tidak valid";
                                }
                            }
                        }
                        
                        // Skip baris kosong
                        $isEmpty = true;
                        foreach ($data as $value) {
                            if (!empty($value) || $value === 0 || $value === '0') {
                                $isEmpty = false;
                                break;
                            }
                        }
                        
                        if ($isEmpty) {
                            continue;
                        }
                        
                        if (!$is_valid) {
                            $errors[] = "Baris " . ($i + 1) . ": " . implode(', ', $error_msg);
                            $jumlahGagal++;
                            continue;
                        }
                        
                        // Escape data
                        $semester = mysqli_real_escape_string($koneksi, $data['semester']);
                        $bulan = mysqli_real_escape_string($koneksi, $data['bulan']);
                        $id_jadwal = mysqli_real_escape_string($koneksi, $data['id_jadwal']);
                        $jml_tm = mysqli_real_escape_string($koneksi, $data['jml_tm'] ?? 0);
                        $sks_tempuh = mysqli_real_escape_string($koneksi, $data['sks_tempuh'] ?? 0);
                        
                        // Cek jadwal
                        $cekJadwal = mysqli_query($koneksi,
                            "SELECT id_jdwl FROM t_jadwal WHERE id_jdwl = '$id_jadwal'"
                        );
                        
                        if (mysqli_num_rows($cekJadwal) == 0) {
                            $errors[] = "Baris " . ($i + 1) . ": id_jadwal '$id_jadwal' tidak ditemukan";
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
                                // Update jika ada (TANPA tanggal di database)
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
                                    $errors[] = "Baris " . ($i + 1) . ": Gagal mengupdate data";
                                    $jumlahGagal++;
                                }
                            } else {
                                $errors[] = "Baris " . ($i + 1) . ": Data untuk id_jadwal '$id_jadwal' sudah ada";
                                $jumlahGagal++;
                            }
                            continue;
                        }
                        
                        // Insert data baru TANPA menyimpan tanggal di database
                        $insert = mysqli_query($koneksi, "
                            INSERT INTO t_transaksi_honor_dosen
                            (semester, bulan, id_jadwal, jml_tm, sks_tempuh)
                            VALUES
                            ('$semester', '$bulan', '$id_jadwal', '$jml_tm', '$sks_tempuh')
                        ");
                        
                        if ($insert) {
                            $jumlahData++;
                        } else {
                            $errors[] = "Baris " . ($i + 1) . ": Gagal menyimpan data - " . mysqli_error($koneksi);
                            $jumlahGagal++;
                        }
                    }
                    
                    // Hapus file temporer
                    if (file_exists($file_tmp)) {
                        unlink($file_tmp);
                    }
                    
                    // Hapus session (kecuali tanggal_input)
                    unset($_SESSION['temp_mapping']);
                    unset($_SESSION['temp_file']);
                    unset($_SESSION['temp_file_name']);
                    unset($_SESSION['original_file_name']);
                    
                    if ($jumlahData > 0) {
                        $_SESSION['success_message'] = "Berhasil mengimport <strong>$jumlahData</strong> data honor dosen dengan tanggal input: <strong>" . $_SESSION['tanggal_input'] . "</strong>.";
                        if ($jumlahGagal > 0) {
                            $_SESSION['success_message'] .= " <strong>$jumlahGagal</strong> data gagal.";
                        }
                    } else {
                        $_SESSION['error_message'] = "Tidak ada data yang berhasil diimport.";
                        unset($_SESSION['tanggal_input']);
                    }
                    
                    if (!empty($errors)) {
                        $error_msg = implode('<br>', array_slice($errors, 0, 5));
                        if (count($errors) > 5) {
                            $error_msg .= '<br>... dan ' . (count($errors) - 5) . ' error lainnya';
                        }
                        $_SESSION['error_message'] = ($_SESSION['error_message'] ?? '') . '<br>' . $error_msg;
                    }
                    
                } catch (Exception $e) {
                    if (file_exists($file_tmp)) {
                        unlink($file_tmp);
                    }
                    unset($_SESSION['tanggal_input']);
                    $_SESSION['error_message'] = "Gagal membaca file Excel: " . $e->getMessage();
                }
            }
            $should_redirect = true;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_manual'])) {
    $manual_semester = mysqli_real_escape_string($koneksi, $_POST['manual_semester'] ?? '');
    $manual_bulan = mysqli_real_escape_string($koneksi, strtolower($_POST['manual_bulan'] ?? ''));
    $manual_jadwal = mysqli_real_escape_string($koneksi, $_POST['manual_jadwal'] ?? '');
    $manual_jml_tm = mysqli_real_escape_string($koneksi, $_POST['manual_jml_tm'] ?? '');
    $manual_sks = mysqli_real_escape_string($koneksi, $_POST['manual_sks'] ?? '');

    if (empty($manual_semester) || empty($manual_bulan) || empty($manual_jadwal)) {
        $_SESSION['error_message'] = "Semester, bulan, dan jadwal wajib diisi!";
    } elseif (!is_numeric($manual_jadwal) || !is_numeric($manual_jml_tm) || !is_numeric($manual_sks)) {
        $_SESSION['error_message'] = "ID Jadwal, Jumlah TM, dan SKS harus angka!";
    } elseif (!preg_match('/^\d{4}[12]$/', $manual_semester)) {
        $_SESSION['error_message'] = "Format semester tidak valid!";
    } else {
        $cekJadwal = mysqli_query($koneksi,
            "SELECT id_jdwl FROM t_jadwal WHERE id_jdwl = '$manual_jadwal'"
        );
        
        if (mysqli_num_rows($cekJadwal) == 0) {
            $_SESSION['error_message'] = "ID Jadwal tidak ditemukan!";
        } else {
            // Cek duplikasi
            $cekDuplikat = mysqli_query($koneksi,
                "SELECT id_thd FROM t_transaksi_honor_dosen 
                 WHERE semester = '$manual_semester' 
                 AND bulan = '$manual_bulan' 
                 AND id_jadwal = '$manual_jadwal'"
            );
            
            if (mysqli_num_rows($cekDuplikat) > 0) {
                $_SESSION['error_message'] = "Data untuk kombinasi semester, bulan, dan ID Jadwal ini sudah ada!";
            } else {
                // OPSI 2: Simpan tanggal input di session
                $_SESSION['tanggal_input'] = date('d/m/Y');
                
                // Insert data manual TANPA menyimpan tanggal di database
                $insert_manual = mysqli_query($koneksi, "
                    INSERT INTO t_transaksi_honor_dosen
                    (semester, bulan, id_jadwal, jml_tm, sks_tempuh)
                    VALUES
                    ('$manual_semester', '$manual_bulan', '$manual_jadwal', '$manual_jml_tm', '$manual_sks')
                ");
                
                if ($insert_manual) {
                    $_SESSION['success_message'] = "Data honor dosen berhasil disimpan dengan tanggal input: <strong>" . $_SESSION['tanggal_input'] . "</strong>!";
                } else {
                    $_SESSION['error_message'] = "Gagal menyimpan data: " . mysqli_error($koneksi);
                    unset($_SESSION['tanggal_input']);
                }
            }
        }
    }
    $should_redirect = true;
}

// ======================
// RESET MAPPING & TANGGAL
// ======================
if (isset($_GET['reset'])) {
    if (isset($_SESSION['temp_file']) && file_exists($_SESSION['temp_file'])) {
        unlink($_SESSION['temp_file']);
    }
    
    unset($_SESSION['temp_mapping']);
    unset($_SESSION['temp_file']);
    unset($_SESSION['temp_file_name']);
    unset($_SESSION['original_file_name']);
    unset($_SESSION['tanggal_input']);
    
    $_SESSION['success_message'] = 'Mapping dan tanggal input berhasil direset.';
    $should_redirect = true;
}

// ======================
// CLEAR TANGGAL SAJA
// ======================
if (isset($_GET['clear_date'])) {
    unset($_SESSION['tanggal_input']);
    $_SESSION['success_message'] = 'Tanggal input berhasil dihapus dari session.';
    $should_redirect = true;
}

// ======================
// REDIRECT JIKA PERLU
// ======================
if ($should_redirect) {
    ob_end_clean();
    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit;
}

// ======================
// AMBIL PESAN DARI SESSION
// ======================
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

// ======================
// AMBIL DATA JADWAL UNTUK DROPDOWN
// ======================
$jadwal_list = [];
$query_jadwal = mysqli_query($koneksi, 
    "SELECT j.id_jdwl, j.kode_matkul, j.nama_matkul, u.nama_user 
     FROM t_jadwal j 
     LEFT JOIN t_user u ON j.id_user = u.id_user 
     ORDER BY j.id_jdwl DESC"
);
while ($row = mysqli_fetch_assoc($query_jadwal)) {
    $jadwal_list[$row['id_jdwl']] = $row['kode_matkul'] . ' - ' . $row['nama_matkul'] . ' (' . ($row['nama_user'] ?? 'Tidak ada dosen') . ')';
}

// ======================
// CLEANUP FILE TEMPORER
// ======================
$tempDir = __DIR__ . '/../storage/tmp_excel/';
if (is_dir($tempDir)) {
    $files = glob($tempDir . 'honor_*');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) {
            @unlink($file);
        }
    }
}

// ======================
// OUTPUT HTML
// ======================
ob_end_flush();

// Include template
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

<!-- Info Tanggal Session -->
<?php if (isset($_SESSION['tanggal_input'])): ?>
<div class="alert alert-info alert-dismissible show fade">
    <div class="alert-body">
        <button class="close" data-dismiss="alert"><span>×</span></button>
        <i class="fas fa-calendar-alt mr-2"></i>
        <strong>Tanggal Input Aktif:</strong> <?= $_SESSION['tanggal_input'] ?>
        <small class="ml-2">(Semua data yang diupload akan memiliki tanggal ini)</small>
        <a href="?clear_date=1" class="btn btn-sm btn-light ml-3">Hapus Tanggal</a>
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
        <li>Kolom wajib: semester, bulan</li>
        <li>Semua kolom jumlah yang tidak terisi akan diisi default 0</li>
        <li>Bulan bisa dalam format: nama lengkap, singkatan, atau angka (1-12)</li>
        <li>Format file: .xls / .xlsx (maks. 10MB)</li>
        <li>Format semester: YYYY1 atau YYYY2 (contoh: 20241, 20242)</li>
        <li><strong>Tanggal Input:</strong> Semua data dalam batch upload akan memiliki tanggal yang sama (dari session)</li>
        <li><strong>Catatan:</strong> Tanggal TIDAK disimpan di database, hanya di session browser</li>
    </ul>
</div>

<!-- Step 1: Upload dan Deteksi Mapping -->
<div class="card card-primary">
    <div class="card-header">
        <h4><i class="fas fa-upload mr-2"></i>Step 1: Upload File Excel</h4>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Pilih File Excel</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="filexls" name="filexls" accept=".xls,.xlsx" required>
                    <label class="custom-file-label" for="filexls">Pilih file...</label>
                </div>
                <small class="form-text text-muted">Format: .xls atau .xlsx (maks. 10MB)</small>
            </div>
            
            <div class="form-group">
                <button type="submit" name="detect_mapping" class="btn btn-primary btn-icon icon-left">
                    <i class="fas fa-search"></i> Deteksi Format & Lanjutkan
                </button>
                <button type="button" class="btn btn-secondary" onclick="clearUploadForm(this)">
                    Reset
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_SESSION['temp_mapping'])): 
    $current_mapping = $_SESSION['temp_mapping'];
?>
<!-- Step 2: Konfigurasi Mapping -->
<div class="card card-success mt-4">
    <div class="card-header">
        <h4><i class="fas fa-sitemap mr-2"></i>Step 2: Konfigurasi Mapping Kolom</h4>
    </div>
    <div class="card-body">
        <form method="POST" id="mappingForm">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Perhatian:</strong> Pastikan semua field wajib (semester, bulan, id_jadwal) sudah dipetakan.
            </div>
            
            <?php if (isset($_SESSION['tanggal_input'])): ?>
            <div class="alert alert-info mb-4">
                <i class="fas fa-calendar-day mr-2"></i>
                <strong>Tanggal Input:</strong> Semua data akan memiliki tanggal: <strong><?= $_SESSION['tanggal_input'] ?></strong>
            </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Kolom di Excel</th>
                            <th>Header di Excel</th>
                            <th>Field di Sistem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_mapping as $index => $map): ?>
                        <tr>
                            <td><?= chr(65 + $map['excel_column']) ?></td>
                            <td><?= htmlspecialchars($map['excel_header']) ?></td>
                            <td>
                                <select class="form-control" name="mapping[<?= $map['excel_column'] ?>]" required>
                                    <option value="">-- Tidak Dipetakan --</option>
                                    <?php foreach ($mapping_config as $field => $config): ?>
                                        <option value="<?= $field ?>" 
                                            <?= (isset($map['system_field']) && $map['system_field'] == $field) ? 'selected' : '' ?>>
                                            <?= $field ?> 
                                            <?php if (isset($map['confidence']) && $map['confidence'] == 'fuzzy'): ?>
                                                <small class="text-warning">(terdeteksi)</small>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="overwrite" name="overwrite" value="1">
                    <label class="custom-control-label" for="overwrite">Timpa data yang sudah ada</label>
                    <small class="form-text text-muted">Jika dicentang, data dengan kombinasi semester-bulan-id_jadwal yang sama akan ditimpa</small>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" name="submit_mapped" class="btn btn-success btn-icon icon-left">
                    <i class="fas fa-play"></i> Proses Upload dengan Mapping Ini
                </button>
                <a href="?reset=1" class="btn btn-secondary">Reset Mapping</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Manual Input Form -->
<div class="mt-5">
    <h5><i class="fas fa-keyboard mr-2"></i>Input Manual</h5>
    <form action="" method="POST" class="mt-3" id="manualForm">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Semester <span class="text-danger">*</span></label>
                <select class="form-control" name="manual_semester" required>
                    <option value="">Pilih Semester</option>
                    <?php foreach ($semesterList as $s): ?>
                        <option value="<?= $s ?>">
                            <?= formatSemester($s) ?>
                        </option>
                    <?php endforeach; ?>
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
                <input type="number" class="form-control" name="manual_sks" min="0" value="0" required>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" name="submit_manual" class="btn btn-success btn-icon icon-left">
                <i class="fas fa-save"></i> Simpan Data Manual
            </button>
            <button type="button" class="btn btn-secondary" onclick="clearManualForm()">
                <i class="fas fa-redo mr-2"></i> Reset Form
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
                <th>Mata Kuliah</th>
                <th>Jumlah Tatap Muka</th>
                <th>SKS Tempuh</th>
                <th>Tanggal Input</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = mysqli_query($koneksi, 
                "SELECT h.semester, h.bulan, h.jml_tm, h.sks_tempuh,
                        j.kode_matkul, j.nama_matkul
                 FROM t_transaksi_honor_dosen h
                 LEFT JOIN t_jadwal j ON h.id_jadwal = j.id_jdwl
                 ORDER BY h.id_thd DESC 
                 LIMIT 10"
            );
            if (mysqli_num_rows($query) > 0):
                while ($row = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <td><?= formatSemester(htmlspecialchars($row['semester'])) ?></td>
                    <td><?= ucfirst(htmlspecialchars($row['bulan'])) ?></td>
                    <td>
                        <?= htmlspecialchars($row['kode_matkul']) ?> - <?= htmlspecialchars($row['nama_matkul']) ?>
                    </td>
                    <td><?= $row['jml_tm'] ?></td>
                    <td><?= $row['sks_tempuh'] ?></td>
                    <td>
                        <?= 
                            // OPSI 2: Ambil dari session jika ada, jika tidak tampilkan tanggal hari ini
                            $_SESSION['tanggal_input'] ?? date('d/m/Y')
                        ?>
                    </td>
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

</div>
</div>

</div>
</section>
</div>

<script>
// Untuk mengubah label file input
document.querySelector('.custom-file-input')?.addEventListener('change', function(e) {
    var fileName = document.getElementById("filexls").files[0]?.name;
    if (fileName) {
        var nextSibling = e.target.nextElementSibling;
        nextSibling.innerText = fileName;
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

// Validasi form mapping
document.addEventListener('DOMContentLoaded', function() {
    const mappingForm = document.getElementById('mappingForm');
    if (mappingForm) {
        mappingForm.addEventListener('submit', function(e) {
            const selects = this.querySelectorAll('select[name^="mapping"]');
            const required = ['semester', 'bulan', 'id_jadwal'];
            let mapped = [];
            
            selects.forEach(select => {
                if (select.value && select.value !== '') {
                    mapped.push(select.value);
                }
            });
            
            const uniqueMapped = [...new Set(mapped)];
            const allRequiredMapped = required.every(field => uniqueMapped.includes(field));
            
            if (!allRequiredMapped) {
                e.preventDefault();
                const missing = required.filter(f => !uniqueMapped.includes(f));
                alert('ERROR: Field wajib berikut belum dipetakan:\n' + 
                      missing.join(', ') + '\n\nSemua field wajib harus dipetakan ke kolom Excel!');
                return false;
            }
            
            const fieldCounts = {};
            selects.forEach(select => {
                if (select.value && select.value !== '') {
                    fieldCounts[select.value] = (fieldCounts[select.value] || 0) + 1;
                }
            });
            
            const duplicateFields = Object.keys(fieldCounts).filter(field => fieldCounts[field] > 1);
            if (duplicateFields.length > 0) {
                e.preventDefault();
                alert('ERROR: Field berikut dipetakan ke lebih dari satu kolom:\n' + 
                      duplicateFields.join(', ') + '\n\nSetiap field sistem hanya boleh dipetakan ke satu kolom Excel!');
                return false;
            }
            
            const overwrite = document.getElementById('overwrite');
            if (overwrite && overwrite.checked) {
                if (!confirm('PERINGATAN: Mode Timpa diaktifkan!\n\nData yang sudah ada akan ditimpa. Lanjutkan?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
    }
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>