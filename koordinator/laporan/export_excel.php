<?php
/**
 * EXPORT LAPORAN KE EXCEL
 * Lokasi: koordinator/laporan/export_excel.php
 */

// Auth dan config - relative path
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php'; // 2 tingkat ke atas

// Cari autoload.php PhpSpreadsheet
$vendor_path = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($vendor_path)) {
    die("Error: File vendor/autoload.php tidak ditemukan di: $vendor_path<br>
         Pastikan library PhpSpreadsheet sudah terinstall:<br>
         Jalankan: composer require phpoffice/phpspreadsheet");
}

require_once $vendor_path;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Laporan Honor');

// Set properties dokumen
$spreadsheet->getProperties()
    ->setCreator('SiPagu UDINUS')
    ->setLastModifiedBy($_SESSION['username'] ?? 'Koordinator')
    ->setTitle('Laporan Honor Dosen')
    ->setSubject('Laporan Keuangan')
    ->setDescription('Laporan honor mengajar dosen UDINUS')
    ->setKeywords('honor, dosen, laporan, udinus')
    ->setCategory('Laporan');

// Setup halaman
$sheet->getPageSetup()
    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(PageSetup::PAPERSIZE_A4)
    ->setFitToWidth(1)
    ->setFitToHeight(0)
    ->setHorizontalCentered(true);

// Set margin halaman
$sheet->getPageMargins()
    ->setTop(0.5)
    ->setRight(0.5)
    ->setLeft(0.5)
    ->setBottom(0.5);

// Header Dokumen
$sheet->mergeCells('A1:J1');
$sheet->setCellValue('A1', 'UNIVERSITAS DIAN NUSWANTORO');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '003366']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
]);

$sheet->mergeCells('A2:J2');
$sheet->setCellValue('A2', 'LAPORAN HONOR DOSEN');
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '000000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
]);

$sheet->mergeCells('A3:J3');
$sheet->setCellValue('A3', 'SISTEM PENGGAJIAN UDINUS (SiPagu)');
$sheet->getStyle('A3')->applyFromArray([
    'font' => ['bold' => true, 'size' => 12, 'italic' => true, 'color' => ['rgb' => '666666']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
]);

// Set tinggi baris untuk header
$sheet->getRowDimension(1)->setRowHeight(25);
$sheet->getRowDimension(2)->setRowHeight(20);
$sheet->getRowDimension(3)->setRowHeight(18);

// Informasi Periode
$periode = '';
if ($semester) {
    $periode = "Semester: $semester";
} elseif ($tahun) {
    $periode = "Tahun: $tahun";
} else {
    $periode = "Semua Periode";
}

if ($bulan) {
    $periode .= " | Bulan: " . $nama_bulan[$bulan];
}

$sheet->mergeCells('A4:J4');
$sheet->setCellValue('A4', $periode);
$sheet->getStyle('A4')->applyFromArray([
    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '333333']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']]
]);
$sheet->getRowDimension(4)->setRowHeight(18);

// Garis pemisah setelah header
$sheet->getStyle('A4:J4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

// Informasi Cetak
$sheet->setCellValue('A5', 'Tanggal Cetak:');
$sheet->setCellValue('B5', date('d F Y H:i:s'));
$sheet->getStyle('A5')->getFont()->setBold(true);
$sheet->setCellValue('A6', 'Dicetak oleh:');
$sheet->setCellValue('B6', $_SESSION['username'] ?? 'Koordinator');
$sheet->getStyle('A6')->getFont()->setBold(true);
$sheet->setCellValue('A7', 'Total Data:');
$sheet->setCellValue('B7', $summary['jumlah_transaksi'] ?? 0 . ' transaksi');
$sheet->getStyle('A7')->getFont()->setBold(true);

// Set alignment untuk informasi cetak
$sheet->getStyle('A5:A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('B5:B7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Ringkasan - POSISI BARU SETELAH INFORMASI CETAK
$sheet->setCellValue('D5', 'RINGKASAN LAPORAN');
$sheet->mergeCells('D5:J5');
$sheet->getStyle('D5')->applyFromArray([
    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

// Data Ringkasan - Format yang lebih rapi
$summary_data = [
    ['D6', 'Jumlah Dosen:', $summary['jumlah_dosen'] ?? 0, 'E6:F6'],
    ['D7', 'Jumlah Transaksi:', $summary['jumlah_transaksi'] ?? 0, 'E7:F7'],
    ['G6', 'Total SKS:', $summary['total_sks'] ?? 0, 'H6:I6'],
    ['G7', 'Total TM:', $summary['total_tm'] ?? 0, 'H7:I7'],
    ['D8', 'Total Honor:', $summary['total_honor'] ?? 0, 'E8:J8']
];

foreach ($summary_data as $data) {
    $sheet->setCellValue($data[0], $data[1]);
    $sheet->getStyle($data[0])->applyFromArray([
        'font' => ['bold' => true, 'size' => 10],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
    ]);
    
    if ($data[0] == 'D8') {
        // Format khusus untuk total honor
        $sheet->mergeCells($data[3]);
        $sheet->setCellValue('E8', $data[2]);
        $sheet->getStyle('E8')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '006600']],
            'numberFormat' => ['formatCode' => '"Rp" #,##0'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    } else {
        $sheet->mergeCells($data[3]);
        $sheet->setCellValue(substr($data[3], 0, 2), $data[2]);
        $sheet->getStyle(substr($data[3], 0, 2))->applyFromArray([
            'font' => ['size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    }
}

// Set tinggi baris untuk ringkasan
$sheet->getRowDimension(5)->setRowHeight(20);
$sheet->getRowDimension(6)->setRowHeight(18);
$sheet->getRowDimension(7)->setRowHeight(18);
$sheet->getRowDimension(8)->setRowHeight(20);

// Spasi sebelum tabel
$sheet->setCellValue('A10', ''); // Baris kosong untuk spasi
$sheet->getRowDimension(10)->setRowHeight(10);

// Header Tabel
$headers = ['No', 'Semester', 'Bulan', 'NPP', 'Nama Dosen', 'Kode MK', 'Mata Kuliah', 'SKS', 'TM', 'Honor (Rp)'];
$row = 11; // Pindah ke baris 11 untuk header tabel

// Set tinggi baris untuk header tabel
$sheet->getRowDimension($row)->setRowHeight(25);

foreach ($headers as $col => $header) {
    $cell = chr(65 + $col) . $row;
    $sheet->setCellValue($cell, $header);
    $sheet->getStyle($cell)->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'FFFFFF']
            ]
        ]
    ]);
    
    // Wrap text untuk header yang panjang
    $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
}

// Set lebar kolom yang lebih optimal
$sheet->getColumnDimension('A')->setWidth(6);  // No
$sheet->getColumnDimension('B')->setWidth(12); // Semester
$sheet->getColumnDimension('C')->setWidth(12); // Bulan
$sheet->getColumnDimension('D')->setWidth(15); // NPP
$sheet->getColumnDimension('E')->setWidth(35); // Nama Dosen
$sheet->getColumnDimension('F')->setWidth(12); // Kode MK
$sheet->getColumnDimension('G')->setWidth(35); // Mata Kuliah
$sheet->getColumnDimension('H')->setWidth(8);  // SKS
$sheet->getColumnDimension('I')->setWidth(8);  // TM
$sheet->getColumnDimension('J')->setWidth(18); // Honor (Rp)

// Data
$row = 12;
$no = 1;
$total_honor = 0;
$max_row = $row; // Untuk menentukan batas akhir data

while ($data = mysqli_fetch_assoc($query)) {
    $honor = $data['honor_perhitungan'] ?? 0;
    $total_honor += $honor;
    
    // Set tinggi baris untuk data (auto height)
    $sheet->getRowDimension($row)->setRowHeight(18);
    
    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, $data['semester']);
    $sheet->setCellValue('C' . $row, ucfirst($data['bulan']));
    $sheet->setCellValue('D' . $row, $data['npp_user']);
    $sheet->setCellValue('E' . $row, $data['nama_user']);
    $sheet->setCellValue('F' . $row, $data['kode_matkul']);
    $sheet->setCellValue('G' . $row, $data['nama_matkul']);
    $sheet->setCellValue('H' . $row, $data['sks_tempuh']);
    $sheet->setCellValue('I' . $row, $data['jml_tm']);
    $sheet->setCellValue('J' . $row, $honor);
    
    // Format number untuk kolom honor
    $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');
    
    // Warna baris bergantian
    $fillColor = ($row % 2 == 0) ? 'FFFFFF' : 'F2F2F2';
    
    // Apply style untuk semua kolom dalam baris ini
    for ($col = 0; $col < 10; $col++) {
        $cell = chr(65 + $col) . $row;
        $sheet->getStyle($cell)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillColor]],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ]);
    }
    
    // Alignment khusus per kolom
    $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('H' . $row . ':I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Wrap text untuk kolom yang berisi teks panjang
    $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
    $sheet->getStyle('G' . $row)->getAlignment()->setWrapText(true);
    
    $max_row = $row;
    $row++;
}

// Jika tidak ada data
if ($no == 1) {
    $sheet->mergeCells('A' . $row . ':J' . $row);
    $sheet->setCellValue('A' . $row, 'TIDAK ADA DATA UNTUK DITAMPILKAN');
    $sheet->getStyle('A' . $row)->applyFromArray([
        'font' => ['bold' => true, 'italic' => true, 'size' => 12, 'color' => ['rgb' => '999999']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $sheet->getRowDimension($row)->setRowHeight(40);
    $max_row = $row;
    $row++;
}

// Baris Total
$total_row = $row;
$sheet->mergeCells('A' . $total_row . ':I' . $total_row);
$sheet->setCellValue('A' . $total_row, 'TOTAL HONOR');
$sheet->setCellValue('J' . $total_row, $total_honor);
$sheet->getStyle('A' . $total_row . ':J' . $total_row)->applyFromArray([
    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5B9BD5']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '4472C4']]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER]
]);
$sheet->getStyle('A' . $total_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('J' . $total_row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
$sheet->getRowDimension($total_row)->setRowHeight(22);

// Catatan
$catatan_row = $total_row + 2;
$sheet->setCellValue('A' . $catatan_row, 'CATATAN:');
$sheet->getStyle('A' . $catatan_row)->applyFromArray([
    'font' => ['bold' => true, 'italic' => true, 'size' => 10, 'color' => ['rgb' => '333333']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
]);

$catatan = [
    '1. Laporan ini bersifat resmi dan dihasilkan secara otomatis oleh sistem SiPagu UDINUS',
    '2. Dokumen ini sah tanpa tanda tangan basah',
    '3. Untuk pertanyaan atau koreksi, hubungi bagian administrasi',
    '4. Perhitungan honor berdasarkan: TM × Tarif per TM dosen'
];

foreach ($catatan as $index => $text) {
    $row_num = $catatan_row + $index + 1;
    $sheet->setCellValue('A' . $row_num, $text);
    $sheet->getStyle('A' . $row_num)->applyFromArray([
        'font' => ['size' => 9, 'color' => ['rgb' => '666666']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
    ]);
    // Wrap text untuk catatan
    $sheet->getStyle('A' . $row_num)->getAlignment()->setWrapText(true);
    $sheet->mergeCells('A' . $row_num . ':J' . $row_num);
}

// Set alignment untuk semua cell
$last_row = $catatan_row + count($catatan);
$sheet->getStyle('A1:J' . $last_row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Auto filter untuk header tabel
$sheet->setAutoFilter('A11:J11');

// Freeze panes (header tabel tetap saat scroll)
$sheet->freezePane('A12');

// Set print area
$sheet->getPageSetup()->setPrintArea('A1:J' . $total_row);

// Header untuk file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Laporan_Honor_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// Simpan memori
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Bersihkan memory
$spreadsheet->disconnectWorksheets();
unset($spreadsheet, $writer);
exit;
?>