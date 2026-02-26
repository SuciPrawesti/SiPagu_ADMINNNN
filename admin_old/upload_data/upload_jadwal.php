<?php
/**
 * UPLOAD JADWAL LAIN - SiPagu
 * Halaman untuk upload jadwal lainnya dari Excel dengan pendekatan fleksibel
 * Lokasi: admin/upload_jadwal.php
 */

// Include required files
require_once __DIR__ . '../../auth.php';
require_once __DIR__ . '.../../../config.php';

// Set page title
$page_title = "Upload Jadwal Lain";

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

// FUNGSI UTAMA: Konversi format semester manusiawi ke format database
function parseSemester($tahun_ajaran, $semester_text) {
    $tahun_ajaran = trim($tahun_ajaran);
    $semester_text = trim(strtolower($semester_text));
    
    // Normalisasi tahun ajaran
    // Format yang diterima: 2024, 2024/2025, 2024-2025, 2024 2025
    if (preg_match('/(\d{4})[\/\- ](\d{4})/', $tahun_ajaran, $matches)) {
        $tahun_awal = $matches[1];
        $tahun_akhir = $matches[2];
    } elseif (preg_match('/^\d{4}$/', $tahun_ajaran)) {
        $tahun_awal = $tahun_ajaran;
        $tahun_akhir = $tahun_ajaran + 1;
    } else {
        return null;
    }
    
    // Deteksi semester ganjil/genap
    $is_ganjil = false;
    $is_genap = false;
    
    if (preg_match('/(ganjil|1|i|satu|gasal|odd)/', $semester_text)) {
        $is_ganjil = true;
        $kode_semester = '1';
        $tahun_db = $tahun_awal;
    } elseif (preg_match('/(genap|2|ii|dua|genap|even)/', $semester_text)) {
        $is_genap = true;
        $kode_semester = '2';
        $tahun_db = $tahun_awal;
    } else {
        return null;
    }
    
    // Format akhir: 20241 (2024 Ganjil) atau 20242 (2024 Genap)
    return $tahun_db . $kode_semester;
}

// Alternatif: Fungsi untuk format yang sudah digabung
function parseSemesterCombined($semester_input) {
    $semester_input = trim(strtolower($semester_input));
    
    // Pattern 1: 2024/2025 Ganjil
    if (preg_match('/(\d{4})[\/\- ](\d{4})[^\d]*(ganjil|1|i|gasal|odd)/', $semester_input, $matches)) {
        return $matches[1] . '1';
    }
    if (preg_match('/(\d{4})[\/\- ](\d{4})[^\d]*(genap|2|ii|genap|even)/', $semester_input, $matches)) {
        return $matches[1] . '2';
    }
    
    // Pattern 2: Ganjil 2024/2025
    if (preg_match('/(ganjil|1|i|gasal|odd)[^\d]*(\d{4})[\/\- ](\d{4})/', $semester_input, $matches)) {
        return $matches[2] . '1';
    }
    if (preg_match('/(genap|2|ii|genap|even)[^\d]*(\d{4})[\/\- ](\d{4})/', $semester_input, $matches)) {
        return $matches[2] . '2';
    }
    
    // Pattern 3: 2024 Ganjil
    if (preg_match('/(\d{4})[^\d]*(ganjil|1|i|gasal|odd)/', $semester_input, $matches)) {
        return $matches[1] . '1';
    }
    if (preg_match('/(\d{4})[^\d]*(genap|2|ii|genap|even)/', $semester_input, $matches)) {
        return $matches[1] . '2';
    }
    
    // Pattern 4: Semester 1 2024
    if (preg_match('/semester[^\d]*1[^\d]*(\d{4})/', $semester_input, $matches)) {
        return $matches[1] . '1';
    }
    if (preg_match('/semester[^\d]*2[^\d]*(\d{4})/', $semester_input, $matches)) {
        return $matches[1] . '2';
    }
    
    // Pattern 5: Sudah dalam format database (20241, 20242)
    if (preg_match('/^(\d{4}[12])$/', $semester_input)) {
        return $semester_input;
    }
    
    return null;
}

// Field mapping configuration - DIPERBARUI dengan format manusiawi
$field_mapping = [
    'tahun_ajaran' => ['tahun ajaran', 'tahun', 'tahun akademik', 'academic year', 'year', 'periode'],
    'semester_text' => ['semester', 'sem', 'smt', 'semester ajaran', 'periode semester'],
    'semester_combined' => ['semester', 'semester ajaran', 'tahun semester', 'period'],
    'kode_matkul' => ['kode_matkul', 'kode', 'kode mk', 'kode mata kuliah', 'kode kuliah', 'kode matkul', 'course code'],
    'nama_matkul' => ['nama_matkul', 'nama', 'nama mk', 'nama mata kuliah', 'nama kuliah', 'matakuliah', 'mata kuliah', 'course name'],
    'id_user' => ['id_user', 'iduser', 'id dosen', 'dosen id', 'nip', 'npp', 'kode dosen', 'pengampu', 'dosen', 'lecturer'],
    'jml_mhs' => ['jml_mhs', 'jumlah', 'jumlah mhs', 'jumlah mahasiswa', 'mhs', 'mahasiswa', 'kuota', 'students', 'enrollment']
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
            $unique_name = 'upload_jadwal_' . time() . '_' . uniqid() . '.' . $file_ext;
            $temp_file_path = $temp_dir . $unique_name;
            
            // Save file to temp directory
            if (move_uploaded_file($file_tmp, $temp_file_path)) {
                // Process file untuk preview
                $preview_data = process_file_for_preview($temp_file_path, $file_ext, $field_mapping);
                if ($preview_data) {
                    $preview_data['temp_file'] = $unique_name;
                    $_SESSION['upload_temp_file_jadwal'] = $unique_name;
                    $_SESSION['overwrite_option_jadwal'] = $_POST['overwrite'] ?? '0';
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
    elseif (isset($_POST['detect_again']) && isset($_SESSION['upload_temp_file_jadwal'])) {
        $temp_file = $_SESSION['upload_temp_file_jadwal'];
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
            unset($_SESSION['upload_temp_file_jadwal']);
        }
    }
    
    // CONFIRM MAPPING DAN IMPORT DATA
    elseif (isset($_POST['confirm_mapping']) && isset($_SESSION['upload_temp_file_jadwal'])) {
        $temp_file = $_SESSION['upload_temp_file_jadwal'];
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
                
                // Validasi mapping kolom
                $mapped_fields = array_values($column_mapping);
                
                // Periksa apakah mapping semester sudah lengkap
                $has_semester_combined = in_array('semester_combined', $mapped_fields);
                $has_tahun_ajaran = in_array('tahun_ajaran', $mapped_fields);
                $has_semester_text = in_array('semester_text', $mapped_fields);
                $has_semester_db = in_array('semester_db', $mapped_fields);
                
                // Logika untuk menentukan apakah mapping semester sudah cukup
                $semester_mapping_ok = false;
                $semester_mapping_type = '';
                
                if ($has_semester_combined) {
                    $semester_mapping_ok = true;
                    $semester_mapping_type = 'combined';
                } elseif ($has_tahun_ajaran && $has_semester_text) {
                    $semester_mapping_ok = true;
                    $semester_mapping_type = 'separate';
                } elseif ($has_semester_db) {
                    $semester_mapping_ok = true;
                    $semester_mapping_type = 'database';
                }
                
                if (!$semester_mapping_ok) {
                    $error_message = "Kolom <strong>Semester</strong> tidak terpetakan dengan benar!<br>";
                    $error_message .= "Pilih salah satu dari:<br>";
                    $error_message .= "1. <strong>Semester (Gabungan)</strong> - contoh: '2024/2025 Ganjil'<br>";
                    $error_message .= "2. <strong>Tahun Ajaran + Semester (Terpisah)</strong> - contoh: '2024/2025' dan 'Ganjil'<br>";
                    $error_message .= "3. <strong>Semester (Database)</strong> - contoh: '20241'";
                } elseif (!in_array('kode_matkul', $mapped_fields)) {
                    $error_message = "Kolom <strong>Kode Mata Kuliah</strong> wajib dipetakan untuk proses import!";
                } elseif (!in_array('nama_matkul', $mapped_fields)) {
                    $error_message = "Kolom <strong>Nama Mata Kuliah</strong> wajib dipetakan untuk proses import!";
                } elseif (!in_array('id_user', $mapped_fields)) {
                    $error_message = "Kolom <strong>ID User/Dosen</strong> wajib dipetakan untuk proses import!";
                } else {
                    $startRow = $headerRow + 1;
                    $jumlahData = 0;
                    $jumlahGagal = 0;
                    $errors = [];
                    $overwrite = isset($_SESSION['overwrite_option_jadwal']) && $_SESSION['overwrite_option_jadwal'] == '1';
                    
                    // Default values untuk kolom yang tidak dipetakan
                    $default_values = [
                        'jml_mhs' => 0
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
                            'tahun_ajaran' => '',
                            'semester_text' => '',
                            'semester_combined' => '',
                            'semester_db' => '',
                            'kode_matkul' => '',
                            'nama_matkul' => '',
                            'id_user' => ''
                        ]);
                        
                        foreach ($column_mapping as $colIndex => $dbField) {
                            if (isset($rowData[$colIndex])) {
                                $data[$dbField] = safe_trim($rowData[$colIndex]);
                            }
                        }
                        
                        // PROSES SEMESTER BERDASARKAN TIPE MAPPING
                        $semester = '';
                        
                        if ($semester_mapping_type == 'combined') {
                            // Format gabungan: "2024/2025 Ganjil"
                            $semester = parseSemesterCombined($data['semester_combined']);
                        } elseif ($semester_mapping_type == 'separate') {
                            // Format terpisah: "2024/2025" dan "Ganjil"
                            $semester = parseSemester($data['tahun_ajaran'], $data['semester_text']);
                        } elseif ($semester_mapping_type == 'database') {
                            // Format database langsung: "20241"
                            $semester = $data['semester_db'];
                        }
                        
                        // Validasi data wajib
                        if (empty($semester)) {
                            $semester_display = '';
                            if ($semester_mapping_type == 'combined') {
                                $semester_display = $data['semester_combined'];
                            } elseif ($semester_mapping_type == 'separate') {
                                $semester_display = $data['tahun_ajaran'] . ' ' . $data['semester_text'];
                            } else {
                                $semester_display = $data['semester_db'];
                            }
                            $errors[] = "Baris " . ($i+1) . ": Format semester tidak valid: '" . htmlspecialchars($semester_display) . "'";
                            $jumlahGagal++;
                            continue;
                        }
                        
                        if (empty($data['kode_matkul'])) {
                            $errors[] = "Baris " . ($i+1) . ": Kode mata kuliah tidak boleh kosong";
                            $jumlahGagal++;
                            continue;
                        }
                        
                        if (empty($data['nama_matkul'])) {
                            $errors[] = "Baris " . ($i+1) . ": Nama mata kuliah tidak boleh kosong";
                            $jumlahGagal++;
                            continue;
                        }
                        
                        if (empty($data['id_user'])) {
                            $errors[] = "Baris " . ($i+1) . ": ID User/Dosen tidak boleh kosong";
                            $jumlahGagal++;
                            continue;
                        }
                        
                        // Format dan validasi data
                        $kode_matkul = strtoupper(safe_trim($data['kode_matkul']));
                        $nama_matkul = mysqli_real_escape_string($koneksi, safe_trim($data['nama_matkul']));
                        $id_user = safe_trim($data['id_user']);
                        
                        // Validasi semester format database
                        if (!preg_match('/^\d{4}[12]$/', $semester)) {
                            $errors[] = "Baris " . ($i+1) . ": Format semester '$semester' tidak valid setelah konversi";
                            $jumlahGagal++;
                            continue;
                        }
                        
                        // Validasi ID user
                        $check_user = mysqli_query($koneksi, 
                            "SELECT id_user FROM t_user WHERE id_user = '$id_user' OR npp_user = '$id_user'"
                        );
                        
                        if (mysqli_num_rows($check_user) == 0) {
                            $errors[] = "Baris " . ($i+1) . ": ID User/Dosen '$id_user' tidak ditemukan dalam sistem";
                            $jumlahGagal++;
                            continue;
                        }
                        
                        // Jika yang diinput adalah NPP, ambil ID usernya
                        $user_row = mysqli_fetch_assoc($check_user);
                        $id_user = $user_row['id_user'];
                        
                        // Validasi jumlah mahasiswa
                        $jml_mhs = isset($data['jml_mhs']) && $data['jml_mhs'] !== '' ? intval($data['jml_mhs']) : 0;
                        if ($jml_mhs < 0) {
                            $errors[] = "Baris " . ($i+1) . ": Jumlah mahasiswa tidak boleh negatif";
                            $jumlahGagal++;
                            continue;
                        }
                        
                        // Cek apakah kombinasi sudah ada
                        $cek = mysqli_query($koneksi,
                            "SELECT id_jdwl FROM t_jadwal 
                             WHERE semester = '$semester' 
                             AND kode_matkul = '$kode_matkul' 
                             AND id_user = '$id_user'"
                        );
                        
                        if (mysqli_num_rows($cek) > 0) {
                            if ($overwrite) {
                                $update = mysqli_query($koneksi, "
                                    UPDATE t_jadwal SET
                                        nama_matkul = '$nama_matkul',
                                        jml_mhs = '$jml_mhs'
                                    WHERE semester = '$semester' 
                                    AND kode_matkul = '$kode_matkul' 
                                    AND id_user = '$id_user'
                                ");
                                
                                if ($update) {
                                    $jumlahData++;
                                } else {
                                    $errors[] = "Baris " . ($i+1) . ": Gagal update data '$kode_matkul' - " . mysqli_error($koneksi);
                                    $jumlahGagal++;
                                }
                            } else {
                                $errors[] = "Baris " . ($i+1) . ": Data untuk kode '$kode_matkul' sudah ada (gunakan opsi 'Timpa data')";
                                $jumlahGagal++;
                            }
                            continue;
                        }
                        
                        // Insert data baru
                        $insert = mysqli_query($koneksi, "
                            INSERT INTO t_jadwal 
                            (semester, kode_matkul, nama_matkul, id_user, jml_mhs)
                            VALUES
                            ('$semester', '$kode_matkul', '$nama_matkul', '$id_user', '$jml_mhs')
                        ");
                        
                        if ($insert) {
                            $jumlahData++;
                        } else {
                            $errors[] = "Baris " . ($i+1) . ": Gagal menyimpan data '$kode_matkul' - " . mysqli_error($koneksi);
                            $jumlahGagal++;
                        }
                    }
                    
                    // Clean up temp file
                    unlink($temp_file_path);
                    unset($_SESSION['upload_temp_file_jadwal']);
                    unset($_SESSION['overwrite_option_jadwal']);
                    
                    if ($jumlahData > 0) {
                        $success_message = "✅ Berhasil mengimport <strong>$jumlahData</strong> data jadwal.";
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
            unset($_SESSION['upload_temp_file_jadwal']);
            unset($_SESSION['overwrite_option_jadwal']);
        }
    }
    
    // MANUAL INPUT
    elseif (isset($_POST['submit_manual'])) {
        $manual_semester = mysqli_real_escape_string($koneksi, $_POST['manual_semester'] ?? '');
        $manual_kode_matkul = mysqli_real_escape_string($koneksi, $_POST['manual_kode_matkul'] ?? '');
        $manual_nama_matkul = mysqli_real_escape_string($koneksi, $_POST['manual_nama_matkul'] ?? '');
        $manual_user = mysqli_real_escape_string($koneksi, $_POST['manual_user'] ?? '');
        $manual_jml_mhs = mysqli_real_escape_string($koneksi, $_POST['manual_jml_mhs'] ?? '0');
        
        // Validasi
        if (empty($manual_semester) || empty($manual_kode_matkul) || 
            empty($manual_nama_matkul) || empty($manual_user)) {
            $error_message = '❌ Semua field wajib diisi!';
        } 
        // Validasi semester format
        elseif (!preg_match('/^\d{4}[12]$/', $manual_semester)) {
            $error_message = '❌ Format semester tidak valid! Contoh: 20241 untuk 2024 Ganjil';
        }
        else {
            // Check if data already exists
            $check = mysqli_query($koneksi, 
                "SELECT id_jdwl FROM t_jadwal 
                 WHERE semester = '$manual_semester' 
                 AND kode_matkul = '$manual_kode_matkul' 
                 AND id_user = '$manual_user'"
            );
            
            if (mysqli_num_rows($check) > 0) {
                $error_message = "⚠️ Data untuk kode mata kuliah ini sudah ada!";
            } else {
                $manual_kode_matkul = strtoupper($manual_kode_matkul);
                $manual_jml_mhs = intval($manual_jml_mhs);
                
                $insert_manual = mysqli_query($koneksi, "
                    INSERT INTO t_jadwal 
                    (semester, kode_matkul, nama_matkul, id_user, jml_mhs)
                    VALUES
                    ('$manual_semester', '$manual_kode_matkul', '$manual_nama_matkul', 
                     '$manual_user', '$manual_jml_mhs')
                ");
                
                if ($insert_manual) {
                    $success_message = "✅ Data jadwal berhasil disimpan!";
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
    $files = glob($dir . 'upload_jadwal_*');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) { // 1 hour
            unlink($file);
        }
    }
}

// Fetch data for dropdowns
$users = [];
$query = mysqli_query($koneksi, "SELECT id_user, npp_user, nama_user FROM t_user ORDER BY nama_user");
while ($row = mysqli_fetch_assoc($query)) {
    $users[$row['id_user']] = $row['npp_user'] . ' - ' . $row['nama_user'];
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Upload Jadwal Lain</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Upload Jadwal Lain</div>
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
                                    <li><strong>Format Semester yang Didukung:</strong></li>
                                    <li>• <code>2024/2025 Ganjil</code> → 20241</li>
                                    <li>• <code>2024 Ganjil</code> → 20241</li>
                                    <li>• <code>Ganjil 2024/2025</code> → 20241</li>
                                    <li>• <code>Semester 1 2024</code> → 20241</li>
                                    <li>• <code>2024/2025</code> + <code>Ganjil</code> (2 kolom terpisah)</li>
                                    <li>• <code>20241</code> (format database langsung)</li>
                                    <li>Upload file Excel/CSV dengan format apapun - sistem akan otomatis mendeteksi</li>
                                </ul>
                            </div>

                            <!-- Upload Form (Hanya muncul jika tidak ada preview) -->
                            <?php if (empty($preview_data)): ?>
                            <form action="" method="POST" enctype="multipart/form-data" class="mt-4">
                                <div class="form-group">
                                    <label>Pilih File Data Jadwal</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="filexls" name="filexls" accept=".xls,.xlsx,.csv" required>
                                        <label class="custom-file-label" for="filexls">Pilih file Excel/CSV...</label>
                                    </div>
                                    <small class="form-text text-muted">Format: .xls, .xlsx, atau .csv (maks. 10MB)</small>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="overwrite" name="overwrite" value="1">
                                    <label class="form-check-label" for="overwrite">
                                        Timpa data yang sudah ada (update berdasarkan Semester + Kode MK + Dosen)
                                    </label>
                                    <small class="form-text text-muted">Jika dicentang, data dengan kombinasi yang sama akan diperbarui</small>
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
                                    
                                    <div class="alert alert-warning">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-info-circle fa-2x mr-3"></i>
                                            <div>
                                                <h6 class="mb-1">Petunjuk Mapping Semester</h6>
                                                <p class="mb-0">
                                                    Pilih <strong>salah satu</strong> dari 3 opsi berikut untuk mapping semester:
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
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
                                                            <select class="form-control" name="manual_mapping[<?= $colIndex ?>]" 
                                                                    data-original="<?= $detected_value ?>">
                                                                <option value="skip">-- Tidak diimport --</option>
                                                                
                                                                <!-- OPSI 1: SEMESTER GABUNGAN -->
                                                                <optgroup label="Semester (Format Gabungan)">
                                                                    <option value="semester_combined" <?= $detected_value == 'semester_combined' ? 'selected' : '' ?>>
                                                                        Semester (Gabungan) - contoh: "2024/2025 Ganjil"
                                                                    </option>
                                                                </optgroup>
                                                                
                                                                <!-- OPSI 2: TERPISAH -->
                                                                <optgroup label="Semester (Format Terpisah)">
                                                                    <option value="tahun_ajaran" <?= $detected_value == 'tahun_ajaran' ? 'selected' : '' ?>>
                                                                        Tahun Ajaran - contoh: "2024/2025"
                                                                    </option>
                                                                    <option value="semester_text" <?= $detected_value == 'semester_text' ? 'selected' : '' ?>>
                                                                        Semester - contoh: "Ganjil"
                                                                    </option>
                                                                </optgroup>
                                                                
                                                                <!-- OPSI 3: DATABASE LANGSUNG -->
                                                                <optgroup label="Format Database Langsung">
                                                                    <option value="semester_db" <?= $detected_value == 'semester_db' ? 'selected' : '' ?>>
                                                                        Semester (Database) - contoh: "20241"
                                                                    </option>
                                                                </optgroup>
                                                                
                                                                <!-- FIELD LAINNYA -->
                                                                <optgroup label="Data Lainnya">
                                                                    <option value="kode_matkul" <?= $detected_value == 'kode_matkul' ? 'selected' : '' ?>>Kode Mata Kuliah (Wajib)</option>
                                                                    <option value="nama_matkul" <?= $detected_value == 'nama_matkul' ? 'selected' : '' ?>>Nama Mata Kuliah (Wajib)</option>
                                                                    <option value="id_user" <?= $detected_value == 'id_user' ? 'selected' : '' ?>>ID User/Dosen (Wajib)</option>
                                                                    <option value="jml_mhs" <?= $detected_value == 'jml_mhs' ? 'selected' : '' ?>>Jumlah Mahasiswa</option>
                                                                </optgroup>
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
                                                    <strong>Pilih hanya satu dari 3 opsi untuk semester:</strong><br>
                                                    1. <strong>Semester (Gabungan)</strong> - satu kolom berisi "2024/2025 Ganjil"<br>
                                                    2. <strong>Tahun Ajaran + Semester (Terpisah)</strong> - dua kolom berbeda<br>
                                                    3. <strong>Semester (Database)</strong> - langsung "20241"<br><br>
                                                    <strong class="text-danger">Kolom wajib lainnya:</strong> Kode MK, Nama MK, ID Dosen
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
                                            <a href="upload_jadwal.php" class="btn btn-secondary btn-icon icon-left">
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
                                            <label>Kode Mata Kuliah <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="manual_kode_matkul" placeholder="SI101" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Nama Mata Kuliah <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="manual_nama_matkul" placeholder="Algoritma dan Pemrograman" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Dosen Pengampu <span class="text-danger">*</span></label>
                                            <select class="form-control" name="manual_user" required>
                                                <option value="">Pilih Dosen</option>
                                                <?php foreach ($users as $id => $nama): ?>
                                                <option value="<?= $id ?>"><?= htmlspecialchars($nama) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Jumlah Mahasiswa</label>
                                            <input type="number" class="form-control" name="manual_jml_mhs" value="0" min="0">
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

                            <!-- Preview Data -->
                            <div class="mt-5">
                                <h5><i class="fas fa-table mr-2"></i>Data Jadwal Terbaru</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Semester</th>
                                                <th>Kode</th>
                                                <th>Mata Kuliah</th>
                                                <th>Dosen</th>
                                                <th>Jml. Mhs</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = mysqli_query($koneksi, 
                                                "SELECT j.semester, j.kode_matkul, j.nama_matkul, 
                                                        u.nama_user, j.jml_mhs
                                                 FROM t_jadwal j
                                                 LEFT JOIN t_user u ON j.id_user = u.id_user
                                                 ORDER BY j.id_jdwl DESC 
                                                 LIMIT 10"
                                            );
                                            while ($row = mysqli_fetch_assoc($query)): ?>
                                            <tr>
                                                <td><?= formatSemester($row['semester']) ?></td>
                                                <td><strong><?= htmlspecialchars($row['kode_matkul']) ?></strong></td>
                                                <td><?= htmlspecialchars($row['nama_matkul']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                                                <td><?= $row['jml_mhs'] ?></td>
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


// Validasi mapping semester
document.querySelector('form')?.addEventListener('submit', function(e) {
    if (e.submitter && e.submitter.name === 'confirm_mapping') {
        const selects = document.querySelectorAll('select[name^="manual_mapping"]');
        let hasSemesterCombined = false;
        let hasTahunAjaran = false;
        let hasSemesterText = false;
        let hasSemesterDB = false;
        
        selects.forEach(select => {
            if (select.value === 'semester_combined') hasSemesterCombined = true;
            if (select.value === 'tahun_ajaran') hasTahunAjaran = true;
            if (select.value === 'semester_text') hasSemesterText = true;
            if (select.value === 'semester_db') hasSemesterDB = true;
        });
        
        const semesterOptions = [hasSemesterCombined, (hasTahunAjaran && hasSemesterText), hasSemesterDB];
        const selectedOptions = semesterOptions.filter(opt => opt).length;
        
        if (selectedOptions > 1) {
            e.preventDefault();
            alert('❌ Error: Pilih hanya satu opsi untuk mapping semester!\n\nContoh pilihan yang benar:\n• Hanya "Semester (Gabungan)" ATAU\n• "Tahun Ajaran" + "Semester" (dua kolom) ATAU\n• Hanya "Semester (Database)"');
            return false;
        }
    }
});
</script>

<?php 
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>