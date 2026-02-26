<?php
/**
 * UPLOAD TRANSAKSI UJIAN - SiPagu
 * Halaman untuk upload data transaksi ujian dari Excel dengan auto-mapping
 * Versi 2.0 - Fixed: masalah "Ahmad Ahmad", transaksi database, validasi ketat
 * Lokasi: admin/upload_tu.php
 */

// ======================
// AKTIFKAN OUTPUT BUFFERING
// ======================
ob_start();

// ======================
// INCLUDE & KONFIGURASI
// ======================
require_once __DIR__ . '../../auth.php';
require_once __DIR__ . '../../../config.php';

$page_title = "Upload Transaksi Ujian";

// ======================
// KONFIGURASI MAPPING TRANSAKSI UJIAN
// ======================
$mapping_config = [
    'semester' => [
        'required' => true,
        'columns' => ['semester', 'semester_id', 'smst', 'thn_semester', 'tahun ajaran', 'periode', 'tahun'],
        'validation' => function($value) {
            $value = preg_replace('/[^0-9]/', '', $value);
            
            // Validasi ketat: harus 4 digit tahun + 1/2
            if (strlen($value) === 4) {
                // Cek jika tahun valid (1900-2100)
                $tahun = intval($value);
                if ($tahun >= 1900 && $tahun <= 2100) {
                    return true;
                }
            } elseif (strlen($value) === 5) {
                $tahun = intval(substr($value, 0, 4));
                $semester = substr($value, 4, 1);
                return ($tahun >= 1900 && $tahun <= 2100 && ($semester == '1' || $semester == '2'));
            }
            
            return false;
        },
        'format' => function($value) {
            $original = $value;
            $value = preg_replace('/[^0-9]/', '', $value);
            
            // Format: YYYY1 atau YYYY2
            if (strlen($value) === 5) {
                $tahun = intval(substr($value, 0, 4));
                $semester = substr($value, 4, 1);
                if ($tahun >= 1900 && $tahun <= 2100 && ($semester == '1' || $semester == '2')) {
                    return $value;
                }
            }
            
            // Jika hanya tahun, default ke ganjil
            if (strlen($value) === 4) {
                $tahun = intval($value);
                if ($tahun >= 1900 && $tahun <= 2100) {
                    return $value . '1';
                }
            }
            
            throw new Exception("Format semester tidak valid: '$original' (contoh: 20241 atau 20242)");
        }
    ],
    
    'id_panitia' => [
        'required' => true,
        'columns' => ['id_panitia', 'panitia_id', 'kode_panitia', 'panitia', 'jabatan_id', 'jabatan', 'nama panitia', 'jbtn_pnt'],
        'validation' => function($value) use ($koneksi) {
            // Prioritas 1: Cek ID (angka)
            if (is_numeric($value) && $value > 0) {
                $query = mysqli_query($koneksi, 
                    "SELECT id_pnt FROM t_panitia WHERE id_pnt = '$value' LIMIT 1"
                );
                return mysqli_num_rows($query) > 0;
            }
            
            // Prioritas 2: Nama jabatan (HANYA jika UNIK)
            $nama = mysqli_real_escape_string($koneksi, trim($value));
            $query = mysqli_query($koneksi, 
                "SELECT COUNT(*) as total FROM t_panitia 
                 WHERE jbtn_pnt LIKE '%$nama%'"
            );
            $row = mysqli_fetch_assoc($query);
            return ($row['total'] == 1); // HANYA boleh 1 hasil
        },
        'format' => function($value) use ($koneksi) {
            // 1. Jika sudah angka (ID)
            if (is_numeric($value)) {
                $query = mysqli_query($koneksi, 
                    "SELECT id_pnt FROM t_panitia WHERE id_pnt = '$value' LIMIT 1"
                );
                if (mysqli_num_rows($query) > 0) {
                    $row = mysqli_fetch_assoc($query);
                    return $row['id_pnt'];
                }
            }
            
            // 2. Nama jabatan (HANYA jika UNIK)
            $nama = mysqli_real_escape_string($koneksi, trim($value));
            $query = mysqli_query($koneksi, 
                "SELECT id_pnt FROM t_panitia 
                 WHERE jbtn_pnt LIKE '%$nama%'"
            );
            
            if (mysqli_num_rows($query) == 1) {
                $row = mysqli_fetch_assoc($query);
                return $row['id_pnt'];
            } elseif (mysqli_num_rows($query) > 1) {
                throw new Exception("Jabatan '$nama' ambigu: ditemukan " . mysqli_num_rows($query) . " hasil. Gunakan ID.");
            }
            
            throw new Exception("Jabatan '$value' tidak ditemukan");
        }
    ],
    
    'id_user' => [
        'required' => true,
        'columns' => ['id_user', 'npp', 'id_user', 'dosen', 'user_id', 'dosen_id', 'nama dosen', 'nama_user', 'user'],
        'validation' => function($value) use ($koneksi) {
            // Prioritas 1: Cek ID/NPP/NIP (angka)
            if (is_numeric($value) && $value > 0) {
                $query = mysqli_query($koneksi, 
                    "SELECT id_user FROM t_user 
                     WHERE id_user = '$value' 
                     OR npp_user = '$value' 
                     LIMIT 1"
                );
                return mysqli_num_rows($query) > 0;
            }
            
            // Prioritas 2: Cek kode dosen (bisa string)
            $value_clean = mysqli_real_escape_string($koneksi, trim($value));
            $query = mysqli_query($koneksi, 
                "SELECT id_user FROM t_user 
                 WHERE id_user = '$value_clean' 
                 LIMIT 1"
            );
            if (mysqli_num_rows($query) > 0) return true;
            
            // Prioritas 3: Nama (HANYA sebagai fallback, dengan validasi ketat)
            $nama = mysqli_real_escape_string($koneksi, trim($value));
            $query = mysqli_query($koneksi, 
                "SELECT COUNT(*) as total FROM t_user 
                 WHERE nama_user LIKE '%$nama%'"
            );
            $row = mysqli_fetch_assoc($query);
            return ($row['total'] == 1); // HANYA boleh 1 hasil
        },
        'format' => function($value) use ($koneksi) {
            // 1. Jika sudah angka (ID/NPP/NIP)
            if (is_numeric($value)) {
                $query = mysqli_query($koneksi, 
                    "SELECT id_user FROM t_user 
                     WHERE id_user = '$value' 
                     OR npp_user = '$value' 
                     LIMIT 1"
                );
                if (mysqli_num_rows($query) > 0) {
                    $row = mysqli_fetch_assoc($query);
                    return $row['id_user'];
                }
                throw new Exception("ID/NPP/NIP '$value' tidak ditemukan");
            }
            
            // 2. Cek kode dosen
            $value_clean = mysqli_real_escape_string($koneksi, trim($value));
            $query = mysqli_query($koneksi, 
                "SELECT id_user FROM t_user 
                 WHERE id_user = '$value_clean' 
                 LIMIT 1"
            );
            if (mysqli_num_rows($query) > 0) {
                $row = mysqli_fetch_assoc($query);
                return $row['id_user'];
            }
            
            // 3. Nama (HANYA jika UNIK)
            $nama = mysqli_real_escape_string($koneksi, trim($value));
            $query = mysqli_query($koneksi, 
                "SELECT id_user FROM t_user 
                 WHERE nama_user LIKE '%$nama%'"
            );
            
            if (mysqli_num_rows($query) == 1) {
                $row = mysqli_fetch_assoc($query);
                return $row['id_user'];
            } elseif (mysqli_num_rows($query) > 1) {
                throw new Exception("Nama '$nama' ambigu: ditemukan " . mysqli_num_rows($query) . " hasil. Gunakan NPP/NIP/ID.");
            }
            
            throw new Exception("Dosen '$value' tidak ditemukan");
        }
    ],
    
    'jml_mhs_prodi' => [
        'required' => false,
        'columns' => ['mahasiswa prodi', 'jml_mhs_prodi', 'jumlah mhs prodi', 'jumlah_mhs_prodi', 'mhs_prodi', 'total_mhs_prodi', 'mhs prodi'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'jml_mhs' => [
        'required' => false,
        'columns' => ['mahasiswa', 'jml_mhs', 'jumlah mahasiswa', 'jumlah_mhs', 'total_mhs', 'mhs', 'jumlah siswa'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'jml_koreksi' => [
        'required' => false,
        'columns' => ['koreksi', 'jml_koreksi', 'jumlah koreksi', 'jumlah_koreksi', 'koreksi ujian', 'ujian koreksi', 'total koreksi'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'jml_matkul' => [
        'required' => false,
        'columns' => ['mata kuliah', 'jml_matkul', 'jumlah matkul', 'jumlah_matkul', 'matkul', 'total matkul', 'jumlah mata kuliah'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'jml_pgws_pagi' => [
        'required' => false,
        'columns' => ['pengawas pagi', 'jml_pgws_pagi', 'jumlah pengawas pagi', 'jumlah_pgws_pagi', 'pgws_pagi', 'pagi', 'pengawas sesi pagi'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'jml_pgws_sore' => [
        'required' => false,
        'columns' => ['pengawas sore', 'jml_pgws_sore', 'jumlah pengawas sore', 'jumlah_pgws_sore', 'pgws_sore', 'sore', 'pengawas sesi sore'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'jml_koor_pagi' => [
        'required' => false,
        'columns' => ['koordinator pagi', 'jml_koor_pagi', 'jumlah koordinator pagi', 'jumlah_koor_pagi', 'koor_pagi', 'koordinator sesi pagi'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'jml_koor_sore' => [
        'required' => false,
        'columns' => ['koordinator sore', 'jml_koor_sore', 'jumlah koordinator sore', 'jumlah_koor_sore', 'koor_sore', 'koordinator sesi sore'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
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
            $tempFileName = 'tu_' . time() . '_' . uniqid() . '.' . $file_ext;
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
                        
                        // Deteksi mapping otomatis dengan matching yang lebih baik
                        foreach ($headers as $index => $header) {
                            $header_clean = strtolower(trim($header ?? ''));
                            
                            // Skip kolom dengan header kosong
                            if ($header_clean === '') {
                                $mapping_result[$index] = [
                                    'excel_column' => $index,
                                    'excel_header' => '',
                                    'system_field' => '',
                                    'skip' => true
                                ];
                                continue;
                            }
                            
                            $header_no_space = str_replace(' ', '', $header_clean);
                            $header_no_underscore = str_replace('_', '', $header_clean);
                            
                            $mapped = false;
                            
                            // Coba semua kombinasi matching
                            foreach ($mapping_config as $field => $config) {
                                foreach ($config['columns'] as $column_name) {
                                    $column_clean = strtolower($column_name);
                                    $column_no_space = str_replace(' ', '', $column_clean);
                                    $column_no_underscore = str_replace('_', '', $column_clean);
                                    
                                    // 1. Exact match (case insensitive)
                                    if ($header_clean === $column_clean) {
                                        $mapping_result[$index] = [
                                            'excel_column' => $index,
                                            'excel_header' => $header ?? '',
                                            'system_field' => $field,
                                            'confidence' => 'exact'
                                        ];
                                        $mapped = true;
                                        break 2;
                                    }
                                    
                                    // 2. Match tanpa spasi/underscore
                                    if ($header_no_space === $column_no_space || 
                                        $header_no_underscore === $column_no_underscore) {
                                        $mapping_result[$index] = [
                                            'excel_column' => $index,
                                            'excel_header' => $header ?? '',
                                            'system_field' => $field,
                                            'confidence' => 'exact'
                                        ];
                                        $mapped = true;
                                        break 2;
                                    }
                                    
                                    // 3. Contains match (lebih fleksibel)
                                    if (strpos($header_clean, $column_clean) !== false || 
                                        strpos($column_clean, $header_clean) !== false) {
                                        $mapping_result[$index] = [
                                            'excel_column' => $index,
                                            'excel_header' => $header ?? '',
                                            'system_field' => $field,
                                            'confidence' => 'contains'
                                        ];
                                        $mapped = true;
                                        break 2;
                                    }
                                    
                                    // 4. Match tanpa karakter spesial
                                    $header_simple = preg_replace('/[^a-z0-9]/', '', $header_clean);
                                    $column_simple = preg_replace('/[^a-z0-9]/', '', $column_clean);
                                    
                                    if ($header_simple === $column_simple) {
                                        $mapping_result[$index] = [
                                            'excel_column' => $index,
                                            'excel_header' => $header ?? '',
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
                                    'excel_header' => $header ?? '',
                                    'system_field' => ''
                                ];
                            }
                        }
                        
                        // Simpan ke session
                        $_SESSION['temp_mapping_tu'] = $mapping_result;
                        $_SESSION['temp_file_tu'] = $tempFilePath;
                        $_SESSION['temp_file_name_tu'] = $tempFileName;
                        $_SESSION['original_file_name_tu'] = $original_name;
                        
                        $_SESSION['success_message'] = 'Mapping berhasil dideteksi! Silakan konfirmasi mapping di bawah.';
                        
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
// PROSES UPLOAD DENGAN MAPPING (DENGAN TRANSAKSI DATABASE)
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_mapped'])) {
    if (!isset($_SESSION['temp_mapping_tu']) || !isset($_SESSION['temp_file_tu'])) {
        $_SESSION['error_message'] = 'Silakan lakukan deteksi mapping terlebih dahulu.';
        $should_redirect = true;
    } else {
        $mapping = $_SESSION['temp_mapping_tu'];
        $file_tmp = $_SESSION['temp_file_tu'];
        
        // Validasi file masih ada
        if (!file_exists($file_tmp)) {
            $_SESSION['error_message'] = 'File temporer tidak ditemukan. Silakan upload ulang.';
            unset($_SESSION['temp_mapping_tu']);
            unset($_SESSION['temp_file_tu']);
            unset($_SESSION['temp_file_name_tu']);
            unset($_SESSION['original_file_name_tu']);
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
                $required_fields = ['semester', 'id_panitia', 'id_user'];
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
            
            // Jika validasi lolos, proses upload dengan transaksi database
            if (!isset($_SESSION['error_message'])) {
                try {
                    require_once __DIR__ . '/../vendor/autoload.php';
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_tmp);
                    $spreadsheet = $reader->load($file_tmp);
                    $sheetData = $spreadsheet->getActiveSheet()->toArray();
                    
                    $jumlahData = 0;
                    $jumlahGagal = 0;
                    $errors = [];
                    $successData = [];
                    
                    // ======================
                    // MULAI TRANSAKSI DATABASE
                    // ======================
                    mysqli_begin_transaction($koneksi);
                    
                    try {
                        for ($i = 1; $i < count($sheetData); $i++) {
                            $row_data = $sheetData[$i];
                            
                            // Skip baris kosong
                            $isEmpty = true;
                            foreach ($row_data as $cell) {
                                if (!empty($cell) || $cell === 0 || $cell === '0') {
                                    $isEmpty = false;
                                    break;
                                }
                            }
                            
                            if ($isEmpty) continue;
                            
                            // Prepare data
                            $data = [];
                            foreach ($_POST['mapping'] as $column => $field) {
                                if (!empty($field) && $field !== '') {
                                    $value = $row_data[$column] ?? '';
                                    $value = is_string($value) ? trim($value) : $value;
                                    
                                    // Normalisasi dengan try-catch untuk error format
                                    try {
                                        if (isset($mapping_config[$field]['format'])) {
                                            $value = $mapping_config[$field]['format']($value);
                                        }
                                        $data[$field] = $value;
                                    } catch (Exception $e) {
                                        $errors[] = "Baris " . ($i + 1) . ": " . $e->getMessage();
                                        $jumlahGagal++;
                                        continue 2; // Skip ke baris berikutnya
                                    }
                                }
                            }
                            
                            // Validasi data
                            $is_valid = true;
                            $error_msg = [];
                            
                            foreach ($mapping_config as $field => $config) {
                                if ($config['required'] && (!isset($data[$field]) || $data[$field] === '')) {
                                    $is_valid = false;
                                    $error_msg[] = "$field kosong";
                                } elseif (isset($data[$field]) && $data[$field] !== '' && isset($config['validation'])) {
                                    if (!$config['validation']($data[$field])) {
                                        $is_valid = false;
                                        $error_msg[] = "$field tidak valid: '" . $data[$field] . "'";
                                    }
                                }
                                
                                if (!isset($data[$field]) && isset($config['default'])) {
                                    $data[$field] = $config['default'];
                                }
                            }
                            
                            if (!$is_valid) {
                                $errors[] = "Baris " . ($i + 1) . ": " . implode(', ', $error_msg);
                                $jumlahGagal++;
                                continue;
                            }
                            
                            // Escape data
                            $semester = mysqli_real_escape_string($koneksi, $data['semester']);
                            $id_panitia = mysqli_real_escape_string($koneksi, $data['id_panitia']);
                            $id_user = mysqli_real_escape_string($koneksi, $data['id_user']);
                            $jml_mhs_prodi = mysqli_real_escape_string($koneksi, $data['jml_mhs_prodi'] ?? 0);
                            $jml_mhs = mysqli_real_escape_string($koneksi, $data['jml_mhs'] ?? 0);
                            $jml_koreksi = mysqli_real_escape_string($koneksi, $data['jml_koreksi'] ?? 0);
                            $jml_matkul = mysqli_real_escape_string($koneksi, $data['jml_matkul'] ?? 0);
                            $jml_pgws_pagi = mysqli_real_escape_string($koneksi, $data['jml_pgws_pagi'] ?? 0);
                            $jml_pgws_sore = mysqli_real_escape_string($koneksi, $data['jml_pgws_sore'] ?? 0);
                            $jml_koor_pagi = mysqli_real_escape_string($koneksi, $data['jml_koor_pagi'] ?? 0);
                            $jml_koor_sore = mysqli_real_escape_string($koneksi, $data['jml_koor_sore'] ?? 0);
                            
                            // Cek user dengan validasi ketat
                            $cekUser = mysqli_query($koneksi,
                                "SELECT id_user, nama_user FROM t_user WHERE id_user = '$id_user'"
                            );
                            
                            if (mysqli_num_rows($cekUser) == 0) {
                                $errors[] = "Baris " . ($i + 1) . ": id_user '$id_user' tidak ditemukan";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            $user_data = mysqli_fetch_assoc($cekUser);
                            $nama_user = $user_data['nama_user'];
                            
                            // Cek panitia
                            $cekPanitia = mysqli_query($koneksi,
                                "SELECT id_pnt, jbtn_pnt FROM t_panitia WHERE id_pnt = '$id_panitia'"
                            );
                            
                            if (mysqli_num_rows($cekPanitia) == 0) {
                                $errors[] = "Baris " . ($i + 1) . ": id_panitia '$id_panitia' tidak ditemukan";
                                $jumlahGagal++;
                                continue;
                            }
                            
                            $panitia_data = mysqli_fetch_assoc($cekPanitia);
                            $jbtn_panitia = $panitia_data['jbtn_pnt'];
                            
                            // Cek duplikasi
                            $cekDuplikat = mysqli_query($koneksi,
                                "SELECT id_tu FROM t_transaksi_ujian 
                                 WHERE semester = '$semester' 
                                 AND id_user = '$id_user' 
                                 AND id_panitia = '$id_panitia'"
                            );
                            
                            if (mysqli_num_rows($cekDuplikat) > 0) {
                                if (isset($_POST['overwrite']) && $_POST['overwrite'] == '1') {
                                    $update = mysqli_query($koneksi, "
                                        UPDATE t_transaksi_ujian
                                        SET jml_mhs_prodi = '$jml_mhs_prodi',
                                            jml_mhs = '$jml_mhs',
                                            jml_koreksi = '$jml_koreksi',
                                            jml_matkul = '$jml_matkul',
                                            jml_pgws_pagi = '$jml_pgws_pagi',
                                            jml_pgws_sore = '$jml_pgws_sore',
                                            jml_koor_pagi = '$jml_koor_pagi',
                                            jml_koor_sore = '$jml_koor_sore'
                                        WHERE semester = '$semester' 
                                        AND id_user = '$id_user' 
                                        AND id_panitia = '$id_panitia'
                                    ");
                                    
                                    if ($update) {
                                        $jumlahData++;
                                        $successData[] = "Baris " . ($i + 1) . ": Data untuk $nama_user ($jbtn_panitia) di semester " . formatSemester($semester) . " berhasil diupdate";
                                    } else {
                                        throw new Exception("Baris " . ($i + 1) . ": Gagal mengupdate data - " . mysqli_error($koneksi));
                                    }
                                } else {
                                    $errors[] = "Baris " . ($i + 1) . ": Data untuk $nama_user ($jbtn_panitia) di semester " . formatSemester($semester) . " sudah ada";
                                    $jumlahGagal++;
                                }
                                continue;
                            }
                            
                            // Insert data baru
                            $insert = mysqli_query($koneksi, "
                                INSERT INTO t_transaksi_ujian
                                (semester, id_panitia, id_user, jml_mhs_prodi, jml_mhs, jml_koreksi, jml_matkul, 
                                 jml_pgws_pagi, jml_pgws_sore, jml_koor_pagi, jml_koor_sore)
                                VALUES
                                ('$semester', '$id_panitia', '$id_user', '$jml_mhs_prodi', '$jml_mhs', '$jml_koreksi', '$jml_matkul',
                                 '$jml_pgws_pagi', '$jml_pgws_sore', '$jml_koor_pagi', '$jml_koor_sore')
                            ");
                            
                            if (!$insert) {
                                throw new Exception("Baris " . ($i + 1) . ": Gagal menyimpan data - " . mysqli_error($koneksi));
                            }
                            
                            $jumlahData++;
                            $successData[] = "Baris " . ($i + 1) . ": Data untuk $nama_user ($jbtn_panitia) di semester " . formatSemester($semester) . " berhasil disimpan";
                        }
                        
                        // ======================
                        // COMMIT TRANSAKSI JIKA SUKSES SEMUA
                        // ======================
                        mysqli_commit($koneksi);
                        
                    } catch (Exception $e) {
                        // ROLLBACK jika ada error
                        mysqli_rollback($koneksi);
                        throw $e;
                    }
                    
                    // Hapus file temporer
                    if (file_exists($file_tmp)) {
                        unlink($file_tmp);
                    }
                    
                    // Hapus session
                    unset($_SESSION['temp_mapping_tu']);
                    unset($_SESSION['temp_file_tu']);
                    unset($_SESSION['temp_file_name_tu']);
                    unset($_SESSION['original_file_name_tu']);
                    
                    if ($jumlahData > 0) {
                        $_SESSION['success_message'] = "Berhasil mengimport <strong>$jumlahData</strong> data transaksi ujian.";
                        if ($jumlahGagal > 0) {
                            $_SESSION['success_message'] .= " <strong>$jumlahGagal</strong> data gagal.";
                        }
                        // Tampilkan detail data yang berhasil
                        if (!empty($successData) && count($successData) <= 10) {
                            $_SESSION['success_message'] .= "<br><br><strong>Detail Data Berhasil:</strong><br>" . implode('<br>', $successData);
                        }
                    } else {
                        $_SESSION['error_message'] = "Tidak ada data yang berhasil diimport.";
                    }
                    
                    if (!empty($errors)) {
                        $error_msg = implode('<br>', array_slice($errors, 0, 10));
                        if (count($errors) > 10) {
                            $error_msg .= '<br>... dan ' . (count($errors) - 10) . ' error lainnya';
                        }
                        $_SESSION['error_message'] = ($_SESSION['error_message'] ?? '') . '<br><br><strong>Error Detail:</strong><br>' . $error_msg;
                    }
                    
                } catch (Exception $e) {
                    if (file_exists($file_tmp)) {
                        unlink($file_tmp);
                    }
                    $_SESSION['error_message'] = "Gagal memproses file: " . $e->getMessage();
                }
            }
            $should_redirect = true;
        }
    }
}

// ======================
// PROSES INPUT MANUAL
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_manual'])) {
    $manual_semester = mysqli_real_escape_string($koneksi, $_POST['manual_semester'] ?? '');
    $manual_panitia = mysqli_real_escape_string($koneksi, $_POST['manual_panitia'] ?? '');
    $manual_user = mysqli_real_escape_string($koneksi, $_POST['manual_user'] ?? '');
    $manual_jml_mhs_prodi = mysqli_real_escape_string($koneksi, $_POST['manual_jml_mhs_prodi'] ?? '0');
    $manual_jml_mhs = mysqli_real_escape_string($koneksi, $_POST['manual_jml_mhs'] ?? '0');
    $manual_jml_koreksi = mysqli_real_escape_string($koneksi, $_POST['manual_jml_koreksi'] ?? '0');
    $manual_jml_matkul = mysqli_real_escape_string($koneksi, $_POST['manual_jml_matkul'] ?? '0');
    $manual_jml_pgws_pagi = mysqli_real_escape_string($koneksi, $_POST['manual_jml_pgws_pagi'] ?? '0');
    $manual_jml_pgws_sore = mysqli_real_escape_string($koneksi, $_POST['manual_jml_pgws_sore'] ?? '0');
    $manual_jml_koor_pagi = mysqli_real_escape_string($koneksi, $_POST['manual_jml_koor_pagi'] ?? '0');
    $manual_jml_koor_sore = mysqli_real_escape_string($koneksi, $_POST['manual_jml_koor_sore'] ?? '0');

    // Validasi
    if (empty($manual_semester) || empty($manual_panitia) || empty($manual_user)) {
        $_SESSION['error_message'] = "Semester, Panitia, dan User wajib diisi!";
    } elseif (!is_numeric($manual_user) || !is_numeric($manual_panitia)) {
        $_SESSION['error_message'] = "ID User dan ID Panitia harus angka!";
    } elseif (!preg_match('/^\d{4}[12]$/', $manual_semester)) {
        $_SESSION['error_message'] = "Format semester tidak valid!";
    } else {
        // Cek user
        $cekUser = mysqli_query($koneksi,
            "SELECT id_user FROM t_user WHERE id_user = '$manual_user'"
        );
        
        if (mysqli_num_rows($cekUser) == 0) {
            $_SESSION['error_message'] = "ID User tidak ditemukan!";
        } else {
            // Cek panitia
            $cekPanitia = mysqli_query($koneksi,
                "SELECT id_pnt FROM t_panitia WHERE id_pnt = '$manual_panitia'"
            );
            
            if (mysqli_num_rows($cekPanitia) == 0) {
                $_SESSION['error_message'] = "ID Panitia tidak ditemukan!";
            } else {
                // Cek duplikasi
                $cekDuplikat = mysqli_query($koneksi,
                    "SELECT id_tu FROM t_transaksi_ujian 
                     WHERE semester = '$manual_semester' 
                     AND id_user = '$manual_user' 
                     AND id_panitia = '$manual_panitia'"
                );
                
                if (mysqli_num_rows($cekDuplikat) > 0) {
                    $_SESSION['error_message'] = "Data untuk kombinasi ini sudah ada!";
                } else {
                    $insert_manual = mysqli_query($koneksi, "
                        INSERT INTO t_transaksi_ujian
                        (semester, id_panitia, id_user, jml_mhs_prodi, jml_mhs, jml_koreksi, jml_matkul, 
                         jml_pgws_pagi, jml_pgws_sore, jml_koor_pagi, jml_koor_sore)
                        VALUES
                        ('$manual_semester', '$manual_panitia', '$manual_user', '$manual_jml_mhs_prodi', 
                         '$manual_jml_mhs', '$manual_jml_koreksi', '$manual_jml_matkul',
                         '$manual_jml_pgws_pagi', '$manual_jml_pgws_sore', 
                         '$manual_jml_koor_pagi', '$manual_jml_koor_sore')
                    ");
                    
                    if ($insert_manual) {
                        $_SESSION['success_message'] = "Data transaksi ujian berhasil disimpan!";
                    } else {
                        $_SESSION['error_message'] = "Gagal menyimpan data: " . mysqli_error($koneksi);
                    }
                }
            }
        }
    }
    $should_redirect = true;
}

// ======================
// RESET MAPPING
// ======================
if (isset($_GET['reset'])) {
    if (isset($_SESSION['temp_file_tu']) && file_exists($_SESSION['temp_file_tu'])) {
        unlink($_SESSION['temp_file_tu']);
    }
    
    unset($_SESSION['temp_mapping_tu']);
    unset($_SESSION['temp_file_tu']);
    unset($_SESSION['temp_file_name_tu']);
    unset($_SESSION['original_file_name_tu']);
    
    $_SESSION['success_message'] = 'Mapping berhasil direset.';
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
// AMBIL DATA UNTUK DROPDOWN
// ======================
$users = [];
$query_users = mysqli_query($koneksi, "SELECT id_user, npp_user, nama_user FROM t_user ORDER BY nama_user");
while ($row = mysqli_fetch_assoc($query_users)) {
    $users[$row['id_user']] = $row['npp_user'] . ' - ' . $row['nama_user'];
}

$panitia = [];
$query_panitia = mysqli_query($koneksi, "SELECT id_pnt, jbtn_pnt FROM t_panitia ORDER BY jbtn_pnt");
while ($row = mysqli_fetch_assoc($query_panitia)) {
    $panitia[$row['id_pnt']] = $row['jbtn_pnt'];
}

// ======================
// CLEANUP FILE TEMPORER
// ======================
$tempDir = __DIR__ . '/../storage/tmp_excel/';
if (is_dir($tempDir)) {
    $files = glob($tempDir . 'tu_*');
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
    <h1>Upload Data Transaksi Ujian</h1>
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active">
            <a href="<?= BASE_URL ?>admin/index.php">Dashboard</a>
        </div>
        <div class="breadcrumb-item">Upload Transaksi Ujian</div>
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

<!-- Debug Info (jika ada) -->
<?php if (isset($_SESSION['debug_errors'])): ?>
<div class="alert alert-warning alert-dismissible show fade">
    <div class="alert-body">
        <button class="close" data-dismiss="alert"><span>×</span></button>
        <h6><i class="fas fa-bug mr-2"></i>Debug Info</h6>
        <pre class="mt-2" style="font-size: 12px; max-height: 200px; overflow: auto;"><?= htmlspecialchars($_SESSION['debug_errors']) ?></pre>
    </div>
</div>
<?php unset($_SESSION['debug_errors']); ?>
<?php endif; ?>

<div class="card">
<div class="card-header">
    <h4>Upload File Excel Transaksi Ujian</h4>
</div>

<div class="card-body">

<div class="alert alert-info">
    <h6><i class="fas fa-info-circle mr-2"></i>Petunjuk Penggunaan</h6>
    <ul class="mb-0 pl-3">
        <li><strong>Kolom wajib:</strong> Semester, Jabatan Panitia, Dosen/User</li>
        <li><strong>Untuk "Dosen":</strong> Gunakan <strong>NPP/NIP/ID</strong> (direkomendasikan), nama hanya sebagai fallback</li>
        <li><strong>Untuk "Panitia":</strong> Gunakan <strong>ID</strong> (direkomendasikan), nama jabatan hanya sebagai fallback</li>
        <li><strong>Semua kolom jumlah akan dibuat default 0 jika tidak terisi</li>
        <li><strong>Format semester:</strong> 20241 (Ganjil) atau 20242 (Genap) atau hanya tahun (2024)</li>
        <li>Format file: .xls / .xlsx (maks. 10MB)</li>
        <li class="text-danger"><strong>PERHATIAN:</strong> Hindari menggunakan hanya nama untuk mencegah data salah mapping!</li>
    </ul>
</div>

<!-- Step 1: Upload dan Deteksi Mapping -->
<div class="card card-primary">
    <div class="card-header">
        <h4><i class="fas fa-upload mr-2"></i>Step 1: Upload File Excel</h4>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label>Pilih File Excel</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="filexls" name="filexls" accept=".xls,.xlsx" required>
                    <label class="custom-file-label" for="filexls">Pilih file...</label>
                </div>
                <small class="form-text text-muted">Format: .xls atau .xlsx (maks. 10MB)</small>
            </div>
            
            <div class="form-group">
                <button type="submit" name="detect_mapping" class="btn btn-primary btn-icon icon-left" id="detectBtn">
                    <i class="fas fa-search"></i> Deteksi Format & Lanjutkan
                </button>
                <button type="button" class="btn btn-secondary" onclick="clearUploadForm(this)">
                    Reset
                </button>

                <div class="spinner-border text-primary d-none" role="status" id="loadingSpinner">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_SESSION['temp_mapping_tu'])): 
    $current_mapping = $_SESSION['temp_mapping_tu'];
?>
<!-- Step 2: Konfigurasi Mapping -->
<div class="card card-success mt-4">
    <div class="card-header">
        <h4><i class="fas fa-sitemap mr-2"></i>Step 2: Konfigurasi Mapping Kolom</h4>
        <div class="card-header-action">
            <small class="text-success">
                <i class="fas fa-file-excel"></i> File: <?= htmlspecialchars($_SESSION['original_file_name_tu'] ?? '') ?>
            </small>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" id="mappingForm">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Perhatian Penting:</strong>
                <ul class="mb-0 mt-2 pl-3">
                    <li>Pastikan semua field wajib (semester, id_panitia, id_user) sudah dipetakan</li>
                    <li>Field wajib <strong>tidak boleh</strong> dipetakan ke kolom Excel yang kosong</li>
                    <li>Gunakan <strong>ID/NPP/NIP</strong> untuk Dosen dan <strong>ID</strong> untuk Panitia (direkomendasikan)</li>
                </ul>
            </div>
            
            <?php 
            // Tampilkan contoh data dari file
            if (isset($_SESSION['temp_file_tu']) && file_exists($_SESSION['temp_file_tu'])) {
                require_once __DIR__ . '/../vendor/autoload.php';
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($_SESSION['temp_file_tu']);
                $spreadsheet = $reader->load($_SESSION['temp_file_tu']);
                $sheetData = $spreadsheet->getActiveSheet()->toArray();
                
                if (count($sheetData) > 1) {
                    $sample_row = $sheetData[1]; // Baris kedua (data pertama)
                }
            }
            ?>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Kolom di Excel</th>
                            <th>Header di Excel</th>
                            <th>Field di Sistem</th>
                            <?php if (isset($sample_row)): ?>
                            <th>Contoh Data</th>
                            <?php endif; ?>
                            <th>Status Deteksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_mapping as $index => $map): 
                            $excel_header = $map['excel_header'] ?? '';
                            $system_field = $map['system_field'] ?? '';
                            $confidence = $map['confidence'] ?? '';
                            $skip = $map['skip'] ?? false;
                        ?>
                        <tr class="<?= $skip ? 'table-secondary' : '' ?>">
                            <td><?= chr(65 + $map['excel_column']) ?></td>
                            <td>
                                <?= htmlspecialchars($excel_header) ?>
                                <?php if ($skip): ?>
                                    <small class="text-danger d-block">(kolom kosong)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="form-control" name="mapping[<?= $map['excel_column'] ?>]" <?= $skip ? 'disabled' : 'required' ?>>
                                    <option value="">-- Tidak Dipetakan --</option>
                                    <?php foreach ($mapping_config as $field => $config): ?>
                                        <option value="<?= $field ?>" 
                                            <?= ($system_field == $field) ? 'selected' : '' ?>>
                                            <?= $field ?> 
                                            <?php if ($field === 'id_user' || $field === 'id_panitia'): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                            <?php if ($confidence != '' && $system_field == $field): ?>
                                                <small class="text-warning">(<?= $confidence ?>)</small>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($skip): ?>
                                    <input type="hidden" name="mapping[<?= $map['excel_column'] ?>]" value="">
                                    <small class="text-muted">Kolom ini akan dilewati</small>
                                <?php endif; ?>
                            </td>
                            <?php if (isset($sample_row)): ?>
                            <td><small class="text-muted"><?= htmlspecialchars($sample_row[$index] ?? '') ?></small></td>
                            <?php endif; ?>
                            <td>
                                <?php if ($skip): ?>
                                    <span class="badge badge-secondary"><i class="fas fa-ban"></i> Skip</span>
                                <?php elseif ($confidence == 'exact'): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Exact</span>
                                <?php elseif ($confidence == 'contains'): ?>
                                    <span class="badge badge-warning"><i class="fas fa-search"></i> Contains</span>
                                <?php elseif (!empty($system_field)): ?>
                                    <span class="badge badge-info"><i class="fas fa-bolt"></i> Detected</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><i class="fas fa-question"></i> Manual</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="overwrite" name="overwrite" value="1">
                    <label class="custom-control-label" for="overwrite">
                        <strong class="text-danger">Timpa data yang sudah ada</strong>
                    </label>
                    <small class="form-text text-muted">Jika dicentang, data dengan kombinasi yang sama akan ditimpa. Hati-hati, data lama akan hilang!</small>
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
            <div class="form-group col-md-4">
                <label>Semester <span class="text-danger">*</span></label>
                <select class="form-control" name="manual_semester" required>
                    <option value="">Pilih Semester</option>
                    <?php foreach ($semesterList as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>">
                            <?= formatSemester($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Panitia (Jabatan) <span class="text-danger">*</span></label>
                <select class="form-control" name="manual_panitia" required>
                    <option value="">Pilih Panitia</option>
                    <?php foreach ($panitia as $id => $nama): ?>
                        <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>User (Dosen) <span class="text-danger">*</span></label>
                <select class="form-control" name="manual_user" required>
                    <option value="">Pilih User</option>
                    <?php foreach ($users as $id => $nama): ?>
                        <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Jml. Mhs Prodi</label>
                <input type="number" class="form-control" name="manual_jml_mhs_prodi" value="0" min="0">
            </div>
            <div class="form-group col-md-3">
                <label>Jml. Mahasiswa</label>
                <input type="number" class="form-control" name="manual_jml_mhs" value="0" min="0">
            </div>
            <div class="form-group col-md-3">
                <label>Jml. Koreksi</label>
                <input type="number" class="form-control" name="manual_jml_koreksi" value="0" min="0">
            </div>
            <div class="form-group col-md-3">
                <label>Jml. Mata Kuliah</label>
                <input type="number" class="form-control" name="manual_jml_matkul" value="0" min="0">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Pengawas Pagi</label>
                <input type="number" class="form-control" name="manual_jml_pgws_pagi" value="0" min="0">
            </div>
            <div class="form-group col-md-3">
                <label>Pengawas Sore</label>
                <input type="number" class="form-control" name="manual_jml_pgws_sore" value="0" min="0">
            </div>
            <div class="form-group col-md-3">
                <label>Koordinator Pagi</label>
                <input type="number" class="form-control" name="manual_jml_koor_pagi" value="0" min="0">
            </div>
            <div class="form-group col-md-3">
                <label>Koordinator Sore</label>
                <input type="number" class="form-control" name="manual_jml_koor_sore" value="0" min="0">
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

<h5><i class="fas fa-table mr-2"></i>Data Transaksi Ujian Terbaru</h5>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Semester</th>
                <th>Jabatan</th>
                <th>User</th>
                <th>Jml. Mhs Prodi</th>
                <th>Jml. Mahasiswa</th>
                <th>Jml. Koreksi</th>
                <th>Jml. Mata Kuliah</th>
                <th>Pengawas Pagi</th>
                <th>Pengawas Sore</th>
                <th>Koordinator Pagi</th>
                <th>Koordinator Sore</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = mysqli_query($koneksi, 
                "SELECT tu.*, p.jbtn_pnt, u.nama_user
                 FROM t_transaksi_ujian tu
                 LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
                 LEFT JOIN t_user u ON tu.id_user = u.id_user
                 ORDER BY tu.id_tu DESC 
                 LIMIT 10"
            );
            
            if (mysqli_num_rows($query) > 0):
                $no = 1;
                while ($row = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= formatSemester(htmlspecialchars($row['semester'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($row['jbtn_pnt'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                    <td><?= $row['jml_mhs_prodi'] ?? 0 ?></td>
                    <td><?= $row['jml_mhs'] ?? 0 ?></td>
                    <td><?= $row['jml_koreksi'] ?? 0 ?></td>
                    <td><?= $row['jml_matkul'] ?? 0 ?></td>
                    <td><?= $row['jml_pgws_pagi'] ?? 0 ?></td>
                    <td><?= $row['jml_pgws_sore'] ?? 0 ?></td>
                    <td><?= $row['jml_koor_pagi'] ?? 0 ?></td>
                    <td><?= $row['jml_koor_sore'] ?? 0 ?></td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="12" class="text-center">Belum ada data</td>
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

// Reset to original detected value
document.querySelectorAll('select[name^="manual_mapping"]').forEach(select => {
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

// Loading indicator untuk upload
const uploadForm = document.getElementById('uploadForm');
const detectBtn = document.getElementById('detectBtn');
const loadingSpinner = document.getElementById('loadingSpinner');

if (uploadForm && detectBtn && loadingSpinner) {
    uploadForm.addEventListener('submit', function() {
        detectBtn.classList.add('d-none');
        loadingSpinner.classList.remove('d-none');
    });
}

// Validasi form mapping yang lebih ketat
document.addEventListener('DOMContentLoaded', function() {
    const mappingForm = document.getElementById('mappingForm');
    if (mappingForm) {
        mappingForm.addEventListener('submit', function(e) {
            const selects = this.querySelectorAll('select[name^="mapping"]:not([disabled])');
            const required = ['semester', 'id_panitia', 'id_user'];
            let mapped = [];
            let emptyRequired = [];
            
            // Validasi field wajib
            selects.forEach(select => {
                const field = select.value;
                if (field && field !== '') {
                    mapped.push(field);
                    
                    // Cek jika ada mapping untuk field wajib ke kolom kosong
                    if (required.includes(field)) {
                        const columnIndex = select.name.match(/\[(\d+)\]/)[1];
                        const headerCell = select.closest('tr').querySelector('td:nth-child(2)');
                        const headerText = headerCell?.textContent.trim() || '';
                        
                        // Cek jika ada indikasi kolom kosong
                        if (headerText === '' || headerText.includes('(kolom kosong)')) {
                            emptyRequired.push(`${field} dipetakan ke kolom kosong`);
                        }
                    }
                }
            });
            
            if (emptyRequired.length > 0) {
                e.preventDefault();
                alert('ERROR:\n\n' + emptyRequired.join('\n') + 
                      '\n\nField wajib tidak boleh dipetakan ke kolom Excel yang kosong!\nSilakan pilih kolom lain yang berisi data.');
                return false;
            }
            
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
            
            // Validasi khusus untuk nama (warning saja)
            const namaFields = selects.filter(select => 
                select.value === 'id_user' || select.value === 'id_panitia'
            );
            
            namaFields.forEach(select => {
                const headerCell = select.closest('tr').querySelector('td:nth-child(2)');
                const headerText = headerCell?.textContent.trim().toLowerCase() || '';
                
                if (headerText.includes('nama') || headerText.includes('dosen') || headerText.includes('panitia')) {
                    const fieldName = select.value === 'id_user' ? 'Dosen' : 'Panitia';
                    const useId = confirm(
                        `PERINGATAN: Anda memetakan field ${fieldName} ke kolom yang mungkin berisi NAMA.\n` +
                        `Ini bisa menyebabkan masalah mapping jika ada nama yang sama.\n\n` +
                        `Rekomendasi: Gunakan ID/NPP/NIP untuk Dosen dan ID untuk Panitia.\n\n` +
                        `Apakah Anda yakin ingin melanjutkan?`
                    );
                    
                    if (!useId) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
            
            const overwrite = document.getElementById('overwrite');
            if (overwrite && overwrite.checked) {
                const confirmed = confirm(
                    'PERINGATAN: Mode Timpa diaktifkan!\n\n' +
                    'Data yang sudah ada dengan kombinasi yang sama akan ditimpa.\n' +
                    'Data lama akan hilang dan tidak dapat dikembalikan.\n\n' +
                    'Yakin ingin melanjutkan dengan mode timpa?'
                );
                
                if (!confirmed) {
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