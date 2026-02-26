<?php
/**
 * UPLOAD PANITIA PA/TA - SiPagu
 * Halaman untuk upload data panitia PA/TA dari Excel dengan auto-mapping
 * Lokasi: admin/upload_tpata.php
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

$page_title = "Upload Panitia PA/TA";

// ======================
// KONFIGURASI MAPPING PA/TA YANG DIPERBAIKI
// ======================
$mapping_config = [
    'semester' => [
        'required' => true,
        'columns' => ['semester', 'semester_id', 'smst', 'thn_semester', 'tahun ajaran', 'periode'],
        'validation' => function($value) {
            $value = preg_replace('/[^0-9]/', '', $value);
            if (strlen($value) === 4) {
                $value .= '1'; // Default ke ganjil jika hanya tahun
            }
            return preg_match('/^\d{4}[12]$/', $value);
        },
        'format' => function($value) {
            $value = preg_replace('/[^0-9]/', '', $value);
            if (strlen($value) === 5) return $value;
            if (strlen($value) === 4) return $value . '1';
            return $value;
        }
    ],
    
    'periode_wisuda' => [
        'required' => true,
        'columns' => ['periode wisuda', 'periode_wisuda', 'wisuda', 'periode', 'masa wisuda', 'bulan wisuda', 'bulan_wisuda'],
        'validation' => function($value) {
            // Setelah dinormalisasi, validasi format akhir
            $value = strtolower(trim($value));
            $bulan_valid = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 
                           'juli', 'agustus', 'september', 'oktober', 'november', 'desember'];
            return in_array($value, $bulan_valid);
        },
        'format' => function($value) {
            $value = strtolower(trim($value));
            
            // Mapping singkatan yang lebih lengkap
            $singkatan = [
                'jan' => 'januari', 'feb' => 'februari', 'mar' => 'maret',
                'apr' => 'april', 'may' => 'mei', 'jun' => 'juni',
                'jul' => 'juli', 'aug' => 'agustus', 
                'sep' => 'september', 'sept' => 'september',
                'oct' => 'oktober', 'nov' => 'november', 'dec' => 'desember',
                'januari' => 'januari', 'februari' => 'februari', 'maret' => 'maret',
                'april' => 'april', 'mei' => 'mei', 'juni' => 'juni',
                'juli' => 'juli', 'agustus' => 'agustus', 'september' => 'september',
                'oktober' => 'oktober', 'november' => 'november', 'desember' => 'desember'
            ];
            
            if (array_key_exists($value, $singkatan)) {
                return $singkatan[$value];
            }
            
            // Mapping angka
            $angka = [
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
            
            if (array_key_exists($value, $angka)) {
                return $angka[$value];
            }
            
            return $value;
        }
    ],
    
    'id_user' => [
        'required' => true,
        'columns' => ['dosen', 'id_user', 'user_id', 'npp', 'dosen_id', 'kode_dosen', 'nip', 'nama dosen'],
        'validation' => function($value) use ($koneksi) {
            // Jika nilai bukan angka, coba cari berdasarkan nama dosen
            if (!is_numeric($value)) {
                $nama = mysqli_real_escape_string($koneksi, $value);
                $query = mysqli_query($koneksi, "SELECT id_user FROM t_user WHERE nama_user LIKE '%$nama%' LIMIT 1");
                return mysqli_num_rows($query) > 0;
            }
            return is_numeric($value) && $value > 0;
        },
        'format' => function($value) use ($koneksi) {
            // Jika value adalah nama dosen, cari id-nya
            if (!is_numeric($value)) {
                $nama = mysqli_real_escape_string($koneksi, $value);
                $query = mysqli_query($koneksi, "SELECT id_user FROM t_user WHERE nama_user LIKE '%$nama%' LIMIT 1");
                if (mysqli_num_rows($query) > 0) {
                    $row = mysqli_fetch_assoc($query);
                    return $row['id_user'];
                }
            }
            return $value;
        }
    ],
    
    'id_panitia' => [
        'required' => true,
        'columns' => ['panitia', 'id_panitia', 'panitia_id', 'jabatan_id', 'kode_panitia', 'jabatan', 'nama panitia'],
        'validation' => function($value) use ($koneksi) {
            // Jika nilai bukan angka, coba cari berdasarkan nama jabatan
            if (!is_numeric($value)) {
                $nama = mysqli_real_escape_string($koneksi, $value);
                $query = mysqli_query($koneksi, "SELECT id_pnt FROM t_panitia WHERE jbtn_pnt LIKE '%$nama%' LIMIT 1");
                return mysqli_num_rows($query) > 0;
            }
            return is_numeric($value) && $value > 0;
        },
        'format' => function($value) use ($koneksi) {
            // Jika value adalah nama jabatan, cari id-nya
            if (!is_numeric($value)) {
                $nama = mysqli_real_escape_string($koneksi, $value);
                $query = mysqli_query($koneksi, "SELECT id_pnt FROM t_panitia WHERE jbtn_pnt LIKE '%$nama%' LIMIT 1");
                if (mysqli_num_rows($query) > 0) {
                    $row = mysqli_fetch_assoc($query);
                    return $row['id_pnt'];
                }
            }
            return $value;
        }
    ],
    
    'prodi' => [
        'required' => true,
        'columns' => ['prodi', 'program studi', 'program_studi', 'jurusan', 'fakultas', 'kode_prodi'],
        'validation' => function($value) {
            return !empty(trim($value));
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
    
    'jml_mhs_bimbingan' => [
        'required' => false,
        'columns' => ['mahasiswa bimbingan', 'jml_mhs_bimbingan', 'jumlah mhs bimbingan', 'jumlah_mhs_bimbingan', 'mhs_bimbingan', 'bimbingan_mhs', 'mhs bimbingan'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'jml_pgji_1' => [
        'required' => false,
        'columns' => ['penguji 1', 'jml_pgji_1', 'penguji_1', 'jumlah_penguji_1', 'pgji1', 'jumlah penguji 1'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'jml_pgji_2' => [
        'required' => false,
        'columns' => ['penguji 2', 'jml_pgji_2', 'penguji_2', 'jumlah_penguji_2', 'pgji2', 'jumlah penguji 2'],
        'validation' => function($value) {
            return is_numeric($value) && $value >= 0;
        },
        'default' => 0
    ],
    
    'ketua_pgji' => [
        'required' => false,
        'columns' => ['ketua penguji', 'ketua_pgji', 'ketua_penguji', 'nama_ketua_penguji', 'ketua'],
        'validation' => function($value) {
            return true; // Boleh kosong
        },
        'default' => ''
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
// PROSES AUTO-MAPPING YANG DIPERBAIKI
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
            $tempFileName = 'pata_' . time() . '_' . uniqid() . '.' . $file_ext;
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
                        $_SESSION['temp_mapping_pata'] = $mapping_result;
                        $_SESSION['temp_file_pata'] = $tempFilePath;
                        $_SESSION['temp_file_name_pata'] = $tempFileName;
                        $_SESSION['original_file_name_pata'] = $original_name;
                        
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
// PROSES UPLOAD DENGAN MAPPING
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_mapped'])) {
    if (!isset($_SESSION['temp_mapping_pata']) || !isset($_SESSION['temp_file_pata'])) {
        $_SESSION['error_message'] = 'Silakan lakukan deteksi mapping terlebih dahulu.';
        $should_redirect = true;
    } else {
        $mapping = $_SESSION['temp_mapping_pata'];
        $file_tmp = $_SESSION['temp_file_pata'];
        
        // Validasi file masih ada
        if (!file_exists($file_tmp)) {
            $_SESSION['error_message'] = 'File temporer tidak ditemukan. Silakan upload ulang.';
            unset($_SESSION['temp_mapping_pata']);
            unset($_SESSION['temp_file_pata']);
            unset($_SESSION['temp_file_name_pata']);
            unset($_SESSION['original_file_name_pata']);
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
                $required_fields = ['semester', 'periode_wisuda', 'id_user', 'id_panitia', 'prodi'];
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
                    $successData = [];
                    
                    // Proses data
                    for ($i = 2; $i < count($sheetData); $i++) {
                        $row_data = $sheetData[$i];
                        
                        // Skip baris kosong
                        $isEmpty = true;
                        foreach ($row_data as $cell) {
                            if (!empty($cell) || $cell === 0 || $cell === '0') {
                                $isEmpty = false;
                                break;
                            }
                        }
                        
                        if ($isEmpty) {
                            continue;
                        }
                        
                        // Prepare data
                        $data = [];
                        foreach ($_POST['mapping'] as $column => $field) {
                            if (!empty($field) && $field !== '') {
                                $value = $row_data[$column] ?? '';
                                $value = is_string($value) ? trim($value) : $value;
                                
                                // Normalisasi dulu SEBELUM validasi
                                if (isset($mapping_config[$field]['format'])) {
                                    $value = $mapping_config[$field]['format']($value);
                                }
                                
                                $data[$field] = $value;
                            }
                        }
                        
                        // Validasi data SETELAH normalisasi
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
                            
                            // Set default value jika kosong dan ada default
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
                        $periode_wisuda = mysqli_real_escape_string($koneksi, $data['periode_wisuda']);
                        $id_user = mysqli_real_escape_string($koneksi, $data['id_user']);
                        $id_panitia = mysqli_real_escape_string($koneksi, $data['id_panitia']);
                        $prodi = mysqli_real_escape_string($koneksi, $data['prodi']);
                        $jml_mhs_prodi = mysqli_real_escape_string($koneksi, $data['jml_mhs_prodi'] ?? 0);
                        $jml_mhs_bimbingan = mysqli_real_escape_string($koneksi, $data['jml_mhs_bimbingan'] ?? 0);
                        $jml_pgji_1 = mysqli_real_escape_string($koneksi, $data['jml_pgji_1'] ?? 0);
                        $jml_pgji_2 = mysqli_real_escape_string($koneksi, $data['jml_pgji_2'] ?? 0);
                        $ketua_pgji = mysqli_real_escape_string($koneksi, $data['ketua_pgji'] ?? '');
                        
                        // Cek user
                        $cekUser = mysqli_query($koneksi,
                            "SELECT id_user, nama_user FROM t_user WHERE id_user = '$id_user'"
                        );
                        
                        if (mysqli_num_rows($cekUser) == 0) {
                            $errors[] = "Baris " . ($i + 1) . ": id_user '$id_user' tidak ditemukan di database";
                            $jumlahGagal++;
                            continue;
                        } else {
                            $user_data = mysqli_fetch_assoc($cekUser);
                            $nama_user = $user_data['nama_user'];
                        }
                        
                        // Cek panitia
                        $cekPanitia = mysqli_query($koneksi,
                            "SELECT id_pnt, jbtn_pnt FROM t_panitia WHERE id_pnt = '$id_panitia'"
                        );
                        
                        if (mysqli_num_rows($cekPanitia) == 0) {
                            $errors[] = "Baris " . ($i + 1) . ": id_panitia '$id_panitia' tidak ditemukan di database";
                            $jumlahGagal++;
                            continue;
                        } else {
                            $panitia_data = mysqli_fetch_assoc($cekPanitia);
                            $jbtn_panitia = $panitia_data['jbtn_pnt'];
                        }
                        
                        // Cek duplikasi
                        $cekDuplikat = mysqli_query($koneksi,
                            "SELECT id_tpt FROM t_transaksi_pa_ta 
                             WHERE semester = '$semester' 
                             AND id_user = '$id_user' 
                             AND id_panitia = '$id_panitia'
                             AND prodi = '$prodi'"
                        );
                        
                        if (mysqli_num_rows($cekDuplikat) > 0) {
                            if (isset($_POST['overwrite']) && $_POST['overwrite'] == '1') {
                                // Update jika ada
                                $update = mysqli_query($koneksi, "
                                    UPDATE t_transaksi_pa_ta
                                    SET periode_wisuda = '$periode_wisuda',
                                        jml_mhs_prodi = '$jml_mhs_prodi',
                                        jml_mhs_bimbingan = '$jml_mhs_bimbingan',
                                        jml_pgji_1 = '$jml_pgji_1',
                                        jml_pgji_2 = '$jml_pgji_2',
                                        ketua_pgji = '$ketua_pgji'
                                    WHERE semester = '$semester' 
                                    AND id_user = '$id_user' 
                                    AND id_panitia = '$id_panitia'
                                    AND prodi = '$prodi'
                                ");
                                
                                if ($update) {
                                    $jumlahData++;
                                    $successData[] = "Baris " . ($i + 1) . ": Data untuk $nama_user ($jbtn_panitia) di semester " . formatSemester($semester) . " berhasil diupdate";
                                } else {
                                    $errors[] = "Baris " . ($i + 1) . ": Gagal mengupdate data - " . mysqli_error($koneksi);
                                    $jumlahGagal++;
                                }
                            } else {
                                $errors[] = "Baris " . ($i + 1) . ": Data untuk $nama_user ($jbtn_panitia) di semester " . formatSemester($semester) . " sudah ada";
                                $jumlahGagal++;
                            }
                            continue;
                        }
                        
                        // Insert data baru
                        $insert = mysqli_query($koneksi, "
                            INSERT INTO t_transaksi_pa_ta
                            (semester, periode_wisuda, id_user, id_panitia, prodi, 
                             jml_mhs_prodi, jml_mhs_bimbingan, jml_pgji_1, jml_pgji_2, ketua_pgji)
                            VALUES
                            ('$semester', '$periode_wisuda', '$id_user', '$id_panitia', '$prodi',
                             '$jml_mhs_prodi', '$jml_mhs_bimbingan', 
                             '$jml_pgji_1', '$jml_pgji_2', '$ketua_pgji')
                        ");
                        
                        if ($insert) {
                            $jumlahData++;
                            $successData[] = "Baris " . ($i + 1) . ": Data untuk $nama_user ($jbtn_panitia) di semester " . formatSemester($semester) . " berhasil disimpan";
                        } else {
                            $errors[] = "Baris " . ($i + 1) . ": Gagal menyimpan data - " . mysqli_error($koneksi);
                            $jumlahGagal++;
                        }
                    }
                    
                    // Hapus file temporer
                    if (file_exists($file_tmp)) {
                        unlink($file_tmp);
                    }
                    
                    // Hapus session
                    unset($_SESSION['temp_mapping_pata']);
                    unset($_SESSION['temp_file_pata']);
                    unset($_SESSION['temp_file_name_pata']);
                    unset($_SESSION['original_file_name_pata']);
                    
                    if ($jumlahData > 0) {
                        $_SESSION['success_message'] = "Berhasil mengimport <strong>$jumlahData</strong> data panitia PA/TA.";
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
                    $_SESSION['error_message'] = "Gagal membaca file Excel: " . $e->getMessage();
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
    $manual_periode = mysqli_real_escape_string($koneksi, strtolower($_POST['manual_periode'] ?? ''));
    $manual_user = mysqli_real_escape_string($koneksi, $_POST['manual_user'] ?? '');
    $manual_panitia = mysqli_real_escape_string($koneksi, $_POST['manual_panitia'] ?? '');
    $manual_prodi = mysqli_real_escape_string($koneksi, $_POST['manual_prodi'] ?? '');
    $manual_jml_mhs_prodi = mysqli_real_escape_string($koneksi, $_POST['manual_jml_mhs_prodi'] ?? '0');
    $manual_jml_mhs_bimbingan = mysqli_real_escape_string($koneksi, $_POST['manual_jml_mhs_bimbingan'] ?? '0');
    $manual_jml_pgji_1 = mysqli_real_escape_string($koneksi, $_POST['manual_jml_pgji_1'] ?? '0');
    $manual_jml_pgji_2 = mysqli_real_escape_string($koneksi, $_POST['manual_jml_pgji_2'] ?? '0');
    $manual_ketua_pgji = mysqli_real_escape_string($koneksi, $_POST['manual_ketua_pgji'] ?? '');

    // Validasi
    if (empty($manual_semester) || empty($manual_periode) || empty($manual_user) || 
        empty($manual_panitia) || empty($manual_prodi)) {
        $_SESSION['error_message'] = "Semua field wajib harus diisi!";
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
                    "SELECT id_tpt FROM t_transaksi_pa_ta 
                     WHERE semester = '$manual_semester' 
                     AND id_user = '$manual_user' 
                     AND id_panitia = '$manual_panitia'
                     AND prodi = '$manual_prodi'"
                );
                
                if (mysqli_num_rows($cekDuplikat) > 0) {
                    $_SESSION['error_message'] = "Data untuk kombinasi ini sudah ada!";
                } else {
                    $insert_manual = mysqli_query($koneksi, "
                        INSERT INTO t_transaksi_pa_ta
                        (semester, periode_wisuda, id_user, id_panitia, prodi, 
                         jml_mhs_prodi, jml_mhs_bimbingan, jml_pgji_1, jml_pgji_2, ketua_pgji)
                        VALUES
                        ('$manual_semester', '$manual_periode', '$manual_user', '$manual_panitia', '$manual_prodi',
                         '$manual_jml_mhs_prodi', '$manual_jml_mhs_bimbingan', 
                         '$manual_jml_pgji_1', '$manual_jml_pgji_2', '$manual_ketua_pgji')
                    ");
                    
                    if ($insert_manual) {
                        $_SESSION['success_message'] = "Data panitia PA/TA berhasil disimpan!";
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
    if (isset($_SESSION['temp_file_pata']) && file_exists($_SESSION['temp_file_pata'])) {
        unlink($_SESSION['temp_file_pata']);
    }
    
    unset($_SESSION['temp_mapping_pata']);
    unset($_SESSION['temp_file_pata']);
    unset($_SESSION['temp_file_name_pata']);
    unset($_SESSION['original_file_name_pata']);
    
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
    $files = glob($tempDir . 'pata_*');
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
    <h1>Upload Data Panitia PA/TA</h1>
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active">
            <a href="<?= BASE_URL ?>admin/index.php">Dashboard</a>
        </div>
        <div class="breadcrumb-item">Upload Panitia PA/TA</div>
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
    <h4>Upload File Excel Panitia PA/TA</h4>
</div>

<div class="card-body">

<div class="alert alert-info">
    <h6><i class="fas fa-info-circle mr-2"></i>Petunjuk Penggunaan</h6>
    <ul class="mb-0 pl-3">
        <li><strong>Kolom wajib:</strong> Semester, Periode Wisuda, Dosen, Panitia, Prodi</li>
        <li><strong>Kolom optional:</strong> Mahasiswa Prodi, Mahasiswa Bimbingan, Penguji 1, Penguji 2, Ketua Penguji</li>
        <li><strong>Untuk "Dosen" dan "Panitia":</strong> Bisa menggunakan ID atau Nama</li>
        <li><strong>Periode wisuda:</strong> nama bulan (januari-desember), singkatan (jan, feb, dst), atau angka 1-12</li>
        <li><strong>Format semester:</strong> YYYY1 (ganjil) atau YYYY2 (genap) atau hanya tahun (YYYY)</li>
        <li>Format file: .xls / .xlsx (maks. 10MB)</li>
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

<?php if (isset($_SESSION['temp_mapping_pata'])): 
    $current_mapping = $_SESSION['temp_mapping_pata'];
?>
<!-- Step 2: Konfigurasi Mapping -->
<div class="card card-success mt-4">
    <div class="card-header">
        <h4><i class="fas fa-sitemap mr-2"></i>Step 2: Konfigurasi Mapping Kolom</h4>
        <div class="card-header-action">
            <small class="text-success">
                <i class="fas fa-file-excel"></i> File: <?= htmlspecialchars($_SESSION['original_file_name_pata'] ?? '') ?>
            </small>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" id="mappingForm">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Perhatian:</strong> Pastikan semua field wajib (semester, periode_wisuda, id_user, id_panitia, prodi) sudah dipetakan.
            </div>
            
            <?php 
            // Tampilkan contoh data dari file
            if (isset($_SESSION['temp_file_pata']) && file_exists($_SESSION['temp_file_pata'])) {
                require_once __DIR__ . '/../vendor/autoload.php';
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($_SESSION['temp_file_pata']);
                $spreadsheet = $reader->load($_SESSION['temp_file_pata']);
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
                        ?>
                        <tr>
                            <td><?= chr(65 + $map['excel_column']) ?></td>
                            <td><?= htmlspecialchars($excel_header) ?></td>
                            <td>
                                <select class="form-control" name="mapping[<?= $map['excel_column'] ?>]" required>
                                    <option value="">-- Tidak Dipetakan --</option>
                                    <?php foreach ($mapping_config as $field => $config): ?>
                                        <option value="<?= $field ?>" 
                                            <?= ($system_field == $field) ? 'selected' : '' ?>>
                                            <?= $field ?> 
                                            <?php if ($confidence != '' && $system_field == $field): ?>
                                                <small class="text-warning">(<?= $confidence ?>)</small>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <?php if (isset($sample_row)): ?>
                            <td><small class="text-muted"><?= htmlspecialchars($sample_row[$index] ?? '') ?></small></td>
                            <?php endif; ?>
                            <td>
                                <?php if ($confidence == 'exact'): ?>
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
                    <label class="custom-control-label" for="overwrite">Timpa data yang sudah ada</label>
                    <small class="form-text text-muted">Jika dicentang, data dengan kombinasi yang sama akan ditimpa</small>
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
                        <option value="<?= htmlspecialchars($s) ?>">
                            <?= formatSemester($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Periode Wisuda <span class="text-danger">*</span></label>
                <select class="form-control" name="manual_periode" required>
                    <option value="">Pilih Periode</option>
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
            <div class="form-group col-md-3">
                <label>User (Dosen) <span class="text-danger">*</span></label>
                <select class="form-control" name="manual_user" required>
                    <option value="">Pilih User</option>
                    <?php foreach ($users as $id => $nama): ?>
                        <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Panitia (Jabatan) <span class="text-danger">*</span></label>
                <select class="form-control" name="manual_panitia" required>
                    <option value="">Pilih Panitia</option>
                    <?php foreach ($panitia as $id => $nama): ?>
                        <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Program Studi <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="manual_prodi" placeholder="SI, TI, MI, dll" required>
            </div>
            <div class="form-group col-md-4">
                <label>Jml. Mhs Prodi</label>
                <input type="number" class="form-control" name="manual_jml_mhs_prodi" value="0" min="0">
            </div>
            <div class="form-group col-md-4">
                <label>Jml. Mhs Bimbingan</label>
                <input type="number" class="form-control" name="manual_jml_mhs_bimbingan" value="0" min="0">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Jml. Penguji 1</label>
                <input type="number" class="form-control" name="manual_jml_pgji_1" value="0" min="0">
            </div>
            <div class="form-group col-md-3">
                <label>Jml. Penguji 2</label>
                <input type="number" class="form-control" name="manual_jml_pgji_2" value="0" min="0">
            </div>
            <div class="form-group col-md-6">
                <label>Ketua Penguji</label>
                <input type="text" class="form-control" name="manual_ketua_pgji" placeholder="Nama ketua penguji">
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

<h5><i class="fas fa-table mr-2"></i>Data Panitia PA/TA Terbaru</h5>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Semester</th>
                <th>Periode Wisuda</th>
                <th>User</th>
                <th>Jabatan</th>
                <th>Program Studi</th>
                <th>Jml. Mhs Prodi</th>
                <th>Jml. Mhs Bimbingan</th>
                <th>Jml. Penguji 1</th>
                <th>Jml. Penguji 2</th>
                <th>Ketua Penguji</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = mysqli_query($koneksi, 
                "SELECT tp.*, u.nama_user, p.jbtn_pnt
                 FROM t_transaksi_pa_ta tp
                 LEFT JOIN t_user u ON tp.id_user = u.id_user
                 LEFT JOIN t_panitia p ON tp.id_panitia = p.id_pnt
                 ORDER BY tp.id_tpt DESC 
                 LIMIT 10"
            );
            
            if (mysqli_num_rows($query) > 0):
                $no = 1;
                while ($row = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= formatSemester(htmlspecialchars($row['semester'] ?? '')) ?></td>
                    <td><?= ucfirst(htmlspecialchars($row['periode_wisuda'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['jbtn_pnt'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['prodi'] ?? '') ?></td>
                    <td><?= $row['jml_mhs_prodi'] ?? 0 ?></td>
                    <td><?= $row['jml_mhs_bimbingan'] ?? 0 ?></td>
                    <td><?= $row['jml_pgji_1'] ?? 0 ?></td>
                    <td><?= $row['jml_pgji_2'] ?? 0 ?></td>
                    <td><?= htmlspecialchars($row['ketua_pgji'] ?? '') ?></td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="11" class="text-center">Belum ada data</td>
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

// Validasi form mapping
document.addEventListener('DOMContentLoaded', function() {
    const mappingForm = document.getElementById('mappingForm');
    if (mappingForm) {
        mappingForm.addEventListener('submit', function(e) {
            const selects = this.querySelectorAll('select[name^="mapping"]');
            const required = ['semester', 'periode_wisuda', 'id_user', 'id_panitia', 'prodi'];
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