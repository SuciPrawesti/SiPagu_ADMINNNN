<?php
/**
 * EXPORT LAPORAN KE PDF
 * Lokasi: koordinator/laporan/export_pdf.php
 */

// Auth dan config
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// DEBUG: Tampilkan path untuk membantu troubleshooting
// error_log("Export PDF diakses: " . date('Y-m-d H:i:s'));

// Cari dan include FPDF dengan beberapa opsi path
$fpdf_included = false;

// Opsi 1: Composer autoload
$autoload_path = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
    if (class_exists('FPDF')) {
        $fpdf_included = true;
        // error_log("FPDF loaded via composer autoload");
    }
}

// Opsi 2: FPDF langsung dari vendor/setasign
if (!$fpdf_included) {
    $fpdf_path1 = __DIR__ . '/../../vendor/setasign/fpdf/fpdf.php';
    if (file_exists($fpdf_path1)) {
        require_once $fpdf_path1;
        $fpdf_included = true;
        // error_log("FPDF loaded via setasign path");
    }
}

// Opsi 3: FPDF dari folder vendor/fpdf
if (!$fpdf_included) {
    $fpdf_path2 = __DIR__ . '/../../vendor/fpdf/fpdf.php';
    if (file_exists($fpdf_path2)) {
        require_once $fpdf_path2;
        $fpdf_included = true;
        // error_log("FPDF loaded via fpdf path");
    }
}

// Opsi 4: Download manual jika tidak ada
if (!$fpdf_included) {
    // Coba cari di lokasi lain
    $possible_paths = [
        __DIR__ . '/../../vendor/fpdf/fpdf/fpdf.php',
        __DIR__ . '/../../../vendor/autoload.php',
        __DIR__ . '/../../../../vendor/autoload.php',
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $fpdf_included = true;
            // error_log("FPDF loaded via alternative path: " . $path);
            break;
        }
    }
}

// Jika masih tidak ditemukan, tampilkan error yang jelas
if (!$fpdf_included) {
    die("<h3>Error: Library FPDF tidak ditemukan</h3>
        <p>Silakan install library dengan perintah:</p>
        <pre>composer require setasign/fpdf</pre>
        <p>Atau download manual dari <a href='http://www.fpdf.org/' target='_blank'>fpdf.org</a> 
        dan letakkan di folder <code>SiPagu/vendor/fpdf/</code></p>
        <p><strong>Path yang dicoba:</strong></p>
        <ul>
            <li>" . htmlspecialchars($autoload_path) . "</li>
            <li>" . htmlspecialchars($fpdf_path1 ?? '') . "</li>
            <li>" . htmlspecialchars($fpdf_path2 ?? '') . "</li>
        </ul>");
}

// Sekarang kelas FPDF seharusnya sudah tersedia
// error_log("FPDF class exists: " . (class_exists('FPDF') ? 'YES' : 'NO'));

// Ambil filter dari GET
$tahun = $_GET['tahun'] ?? date('Y');
$semester = $_GET['semester'] ?? '';
$bulan = $_GET['bulan'] ?? '';

// Konversi bulan angka ke nama
$nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Query laporan honor dosen
$where = "1=1";
if ($tahun) {
    $where .= " AND thd.semester LIKE '$tahun%'";
}
if ($semester) {
    $where .= " AND thd.semester = '$semester'";
}
if ($bulan) {
    $where .= " AND thd.bulan = '" . strtolower($nama_bulan[$bulan]) . "'";
}

$query = mysqli_query($koneksi, "
    SELECT 
        thd.semester,
        thd.bulan,
        u.npp_user,
        u.nama_user,
        j.kode_matkul,
        j.nama_matkul,
        thd.sks_tempuh,
        thd.jml_tm,
        thd.jml_tm * u.honor_persks as honor_perhitungan
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    JOIN t_user u ON j.id_user = u.id_user
    WHERE $where
    ORDER BY thd.semester DESC, thd.bulan, u.nama_user
");

// Query summary
$query_summary = mysqli_query($koneksi, "
    SELECT 
        COUNT(DISTINCT u.id_user) as jumlah_dosen,
        COUNT(thd.id_thd) as jumlah_transaksi,
        SUM(thd.sks_tempuh) as total_sks,
        SUM(thd.jml_tm) as total_tm,
        SUM(thd.jml_tm * u.honor_persks) as total_honor
    FROM t_transaksi_honor_dosen thd
    JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    JOIN t_user u ON j.id_user = u.id_user
    WHERE $where
");

$summary = mysqli_fetch_assoc($query_summary);

// Buat class PDF custom yang extends FPDF
class LaporanHonorPDF extends FPDF
{
    private $title;
    private $periode;
    
    function setTitleInfo($title, $periode) {
        $this->title = $title;
        $this->periode = $periode;
    }
    
    // Header
    function Header()
    {
        // Logo
        $logo_paths = [
            __DIR__ . '/../../assets/img/logo.png',
            __DIR__ . '/../../assets/images/logo.png',
            __DIR__ . '/../../img/logo.png',
        ];
        
        $logo_found = false;
        foreach ($logo_paths as $logo_path) {
            if (file_exists($logo_path)) {
                $this->Image($logo_path, 10, 8, 25);
                $logo_found = true;
                break;
            }
        }
        
        // Judul Utama
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 51, 102);
        $this->Cell(0, 8, 'UNIVERSITAS DIAN NUSWANTORO', 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, 'LAPORAN HONOR DOSEN', 0, 1, 'C');
        
        // Sub Judul
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 6, $this->title, 0, 1, 'C');
        $this->Cell(0, 6, $this->periode, 0, 1, 'C');
        
        // Garis pemisah
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY() + 2, 200, $this->GetY() + 2);
        $this->Ln(5);
    }
    
    // Footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . ' dari {nb}', 0, 0, 'C');
        $this->Ln(5);
        $this->Cell(0, 10, 'Dicetak pada: ' . date('d/m/Y H:i:s'), 0, 0, 'C');
    }
    
    // Tabel Header
    function tabelHeader()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220, 230, 241);
        $this->SetTextColor(0, 0, 0);
        
        $this->Cell(10, 8, 'No', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Semester', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Bulan', 1, 0, 'C', true);
        $this->Cell(25, 8, 'NPP', 1, 0, 'C', true);
        $this->Cell(45, 8, 'Nama Dosen', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Kode MK', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Mata Kuliah', 1, 0, 'C', true);
        $this->Cell(10, 8, 'SKS', 1, 0, 'C', true);
        $this->Cell(10, 8, 'TM', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Honor (Rp)', 1, 1, 'C', true);
    }
    
    // Tabel Data
    function tabelData($data)
    {
        $this->SetFont('Arial', '', 8);
        
        $no = 1;
        foreach ($data as $row) {
            // Ganti baris setiap halaman
            if ($this->GetY() > 180) {
                $this->AddPage();
                $this->tabelHeader();
            }
            
            // Warna baris bergantian
            if ($no % 2 == 0) {
                $this->SetFillColor(245, 245, 245);
            } else {
                $this->SetFillColor(255, 255, 255);
            }
            
            $this->Cell(10, 7, $no, 1, 0, 'C', true);
            $this->Cell(20, 7, $row['semester'], 1, 0, 'C', true);
            $this->Cell(20, 7, ucfirst($row['bulan']), 1, 0, 'C', true);
            $this->Cell(25, 7, $row['npp_user'], 1, 0, 'C', true);
            $this->Cell(45, 7, substr($row['nama_user'], 0, 25), 1, 0, 'L', true);
            $this->Cell(20, 7, $row['kode_matkul'], 1, 0, 'C', true);
            $this->Cell(40, 7, substr($row['nama_matkul'], 0, 25), 1, 0, 'L', true);
            $this->Cell(10, 7, $row['sks_tempuh'], 1, 0, 'C', true);
            $this->Cell(10, 7, $row['jml_tm'], 1, 0, 'C', true);
            $this->Cell(30, 7, number_format($row['honor_perhitungan'], 0, ',', '.'), 1, 1, 'R', true);
            
            $no++;
        }
        
        return $no - 1;
    }
}

// Siapkan data
$data = [];
$total_honor = 0;
while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
    $total_honor += $row['honor_perhitungan'];
}

// Buat judul berdasarkan filter
$judul_periode = '';
if ($semester) {
    $judul_periode = "Semester: $semester";
} elseif ($tahun) {
    $judul_periode = "Tahun: $tahun";
} else {
    $judul_periode = "Semua Periode";
}

if ($bulan) {
    $judul_periode .= " | Bulan: " . $nama_bulan[$bulan];
}

// Buat PDF
$pdf = new LaporanHonorPDF('L', 'mm', 'A4');
$pdf->setTitleInfo('SISTEM PENGGAJIAN UDINUS (SiPagu)', $judul_periode);
$pdf->AliasNbPages();
$pdf->AddPage();

// Informasi laporan
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Tanggal Generate: ' . date('d F Y H:i:s'), 0, 1);
$pdf->Cell(0, 6, 'Dicetak oleh: ' . ($_SESSION['username'] ?? 'Koordinator'), 0, 1);
$pdf->Ln(5);

// Ringkasan
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 8, 'RINGKASAN LAPORAN', 0, 1, 'L', true);
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 6, 'Jumlah Dosen: ' . ($summary['jumlah_dosen'] ?? 0), 0, 0);
$pdf->Cell(60, 6, 'Jumlah Transaksi: ' . ($summary['jumlah_transaksi'] ?? 0), 0, 0);
$pdf->Cell(60, 6, 'Total SKS: ' . ($summary['total_sks'] ?? 0), 0, 1);

$pdf->Cell(60, 6, 'Total TM: ' . ($summary['total_tm'] ?? 0), 0, 0);
$pdf->Cell(60, 6, 'Total Honor: Rp ' . number_format($summary['total_honor'] ?? 0, 0, ',', '.'), 0, 1);
$pdf->Ln(8);

// Header Tabel
$pdf->tabelHeader();

// Data Tabel
$jumlah_data = $pdf->tabelData($data);

// Total
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(220, 255, 220);
$pdf->Cell(185, 8, 'TOTAL HONOR', 1, 0, 'R', true);
$pdf->Cell(30, 8, 'Rp ' . number_format($total_honor, 0, ',', '.'), 1, 1, 'R', true);

// Catatan
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 4, 'Catatan:');
$pdf->MultiCell(0, 4, '1. Laporan ini bersifat resmi dan dihasilkan secara otomatis oleh sistem SiPagu UDINUS');
$pdf->MultiCell(0, 4, '2. Dokumen ini sah tanpa tanda tangan basah');
$pdf->MultiCell(0, 4, '3. Untuk pertanyaan atau koreksi, hubungi bagian administrasi');

// Output PDF
$filename = 'Laporan_Honor_' . date('Ymd_His') . '.pdf';
$pdf->Output('I', $filename);
?>