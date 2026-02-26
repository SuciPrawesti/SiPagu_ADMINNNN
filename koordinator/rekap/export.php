<?php
/**
 * EXPORT DATA - SiPagu Koordinator
 * Lokasi: koordinator/rekap/export.php
 */
require_once __DIR__ . '/../auth.php';

// Koneksi database
require_once __DIR__ . '/../../config.php';

// Get parameters
$type = $_GET['type'] ?? 'dosen'; // dosen, unit, bulanan
$format = $_GET['format'] ?? 'excel'; // excel, pdf
$semester = $_GET['semester'] ?? '';
$status = $_GET['status'] ?? 'disetujui';
$additional_params = $_GET; // Other parameters

// Headers untuk download
if ($format == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="rekap_honor_' . $type . '_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');
} elseif ($format == 'pdf') {
    // Untuk PDF, perlu library seperti TCPDF atau DomPDF
    // Ini adalah placeholder sederhana
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="rekap_honor_' . $type . '_' . date('Ymd_His') . '.pdf"');
}

// Query data berdasarkan type
switch ($type) {
    case 'dosen':
        $dosen = $_GET['dosen'] ?? '';
        $where = "WHERE 1=1";
        if ($semester) $where .= " AND thd.semester = '$semester'";
        if ($dosen) $where .= " AND u.nama_user LIKE '%$dosen%'";
        if ($status) $where .= " AND thd.status = '$status'";
        
        $query = mysqli_query($koneksi, "
            SELECT 
                u.npp_user,
                u.nama_user,
                COUNT(thd.id_thd) as total_matkul,
                SUM(thd.jml_tm) as total_tm,
                SUM(thd.sks_tempuh) as total_sks,
                u.honor_persks,
                SUM(thd.jml_tm * thd.sks_tempuh * u.honor_persks) as total_honor
            FROM t_user u
            LEFT JOIN t_jadwal j ON u.id_user = j.id_user
            LEFT JOIN t_transaksi_honor_dosen thd ON j.id_jdwl = thd.id_jadwal
            $where
            GROUP BY u.id_user, u.nama_user
            HAVING total_honor > 0
            ORDER BY total_honor DESC
        ");
        $title = "Rekap Honor per Dosen";
        break;
        
    case 'unit':
        $jenis = $_GET['jenis'] ?? 'all';
        // Query untuk unit (sederhana)
        $query = mysqli_query($koneksi, "
            SELECT 
                'Dosen Mengajar' as jenis,
                COUNT(DISTINCT u.id_user) as jumlah_orang,
                COUNT(thd.id_thd) as jumlah_transaksi,
                SUM(thd.jml_tm * thd.sks_tempuh * u.honor_persks) as total_honor
            FROM t_transaksi_honor_dosen thd
            LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
            LEFT JOIN t_user u ON j.id_user = u.id_user
            WHERE 1=1 " . ($semester ? "AND thd.semester = '$semester'" : "") . " 
            " . ($status ? "AND thd.status = '$status'" : "") . "
            
            UNION ALL
            
            SELECT 
                'Panitia Ujian' as jenis,
                COUNT(DISTINCT tu.id_user) as jumlah_orang,
                COUNT(tu.id_tu) as jumlah_transaksi,
                SUM(p.honor_std) as total_honor
            FROM t_transaksi_ujian tu
            LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
            WHERE 1=1 " . ($semester ? "AND tu.semester = '$semester'" : "") . " 
            " . ($status ? "AND tu.status = '$status'" : "") . "
            
            UNION ALL
            
            SELECT 
                'PA/TA' as jenis,
                COUNT(DISTINCT tpt.id_user) as jumlah_orang,
                COUNT(tpt.id_tpt) as jumlah_transaksi,
                SUM(tpt.jml_mhs_bimbingan * p.honor_std) as total_honor
            FROM t_transaksi_pa_ta tpt
            LEFT JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
            WHERE 1=1 " . ($semester ? "AND tpt.semester = '$semester'" : "") . " 
            " . ($status ? "AND tpt.status = '$status'" : "") . "
        ");
        $title = "Rekap Honor per Jenis/Unit";
        break;
        
    case 'bulanan':
        $bulan = $_GET['bulan'] ?? '';
        $where = "WHERE YEAR(thd.created_at) = '" . ($semester ? substr($semester, 0, 4) : date('Y')) . "'";
        if ($bulan) {
            $where .= " AND thd.bulan = '$bulan'";
        }
        if ($status) {
            $where .= " AND thd.status = '$status'";
        }
        
        $query = mysqli_query($koneksi, "
            SELECT 
                thd.bulan,
                COUNT(*) as jumlah_data,
                SUM(thd.jml_tm * thd.sks_tempuh * u.honor_persks) as total_honor,
                AVG(thd.jml_tm * thd.sks_tempuh * u.honor_persks) as rata_honor,
                SUM(CASE WHEN thd.status = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
                SUM(CASE WHEN thd.status = 'ditolak' THEN 1 ELSE 0 END) as ditolak,
                SUM(CASE WHEN thd.status = 'menunggu' THEN 1 ELSE 0 END) as menunggu
            FROM t_transaksi_honor_dosen thd
            LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
            LEFT JOIN t_user u ON j.id_user = u.id_user
            $where
            GROUP BY thd.bulan
            ORDER BY 
                CASE thd.bulan
                    WHEN 'januari' THEN 1
                    WHEN 'februari' THEN 2
                    WHEN 'maret' THEN 3
                    WHEN 'april' THEN 4
                    WHEN 'mei' THEN 5
                    WHEN 'juni' THEN 6
                    WHEN 'juli' THEN 7
                    WHEN 'agustus' THEN 8
                    WHEN 'september' THEN 9
                    WHEN 'oktober' THEN 10
                    WHEN 'november' THEN 11
                    WHEN 'desember' THEN 12
                END
        ");
        $title = "Rekap Honor Bulanan";
        break;
        
    default:
        die("Tipe ekspor tidak valid");
}

// Untuk Excel, output HTML table sederhana
if ($format == 'excel') {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . $title . '</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .total-row { background-color: #e8f4fd; font-weight: bold; }
        </style>
    </head>
    <body>';
    
    echo '<h2>' . $title . '</h2>';
    echo '<p>Tanggal Export: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<p>Filter: Semester=' . ($semester ?: 'Semua') . ', Status=' . ($status ?: 'Semua') . '</p>';
    
    echo '<table>';
    echo '<thead><tr>';
    
    // Header berdasarkan type
    if ($type == 'dosen') {
        echo '<th>No</th>
              <th>NPP</th>
              <th>Nama Dosen</th>
              <th>Jumlah Matkul</th>
              <th>Total TM</th>
              <th>Total SKS</th>
              <th>Honor/SKS</th>
              <th>Total Honor</th>';
    } elseif ($type == 'unit') {
        echo '<th>Jenis Honor</th>
              <th>Jumlah Penerima</th>
              <th>Jumlah Transaksi</th>
              <th>Total Honor</th>';
    } elseif ($type == 'bulanan') {
        echo '<th>Bulan</th>
              <th>Jumlah Data</th>
              <th>Total Honor</th>
              <th>Rata-rata Honor</th>
              <th>Disetujui</th>
              <th>Ditolak</th>
              <th>Menunggu</th>';
    }
    
    echo '</tr></thead><tbody>';
    
    $no = 1;
    $total_honor_all = 0;
    while ($row = mysqli_fetch_assoc($query)) {
        echo '<tr>';
        
        if ($type == 'dosen') {
            echo '<td>' . $no++ . '</td>
                  <td>' . htmlspecialchars($row['npp_user']) . '</td>
                  <td>' . htmlspecialchars($row['nama_user']) . '</td>
                  <td>' . $row['total_matkul'] . '</td>
                  <td>' . $row['total_tm'] . '</td>
                  <td>' . $row['total_sks'] . '</td>
                  <td>Rp ' . number_format($row['honor_persks'], 0, ',', '.') . '</td>
                  <td>Rp ' . number_format($row['total_honor'], 0, ',', '.') . '</td>';
            $total_honor_all += $row['total_honor'];
        } elseif ($type == 'unit') {
            echo '<td>' . htmlspecialchars($row['jenis']) . '</td>
                  <td>' . $row['jumlah_orang'] . '</td>
                  <td>' . $row['jumlah_transaksi'] . '</td>
                  <td>Rp ' . number_format($row['total_honor'], 0, ',', '.') . '</td>';
            $total_honor_all += $row['total_honor'];
        } elseif ($type == 'bulanan') {
            echo '<td>' . ucfirst($row['bulan']) . '</td>
                  <td>' . $row['jumlah_data'] . '</td>
                  <td>Rp ' . number_format($row['total_honor'], 0, ',', '.') . '</td>
                  <td>Rp ' . number_format($row['rata_honor'], 0, ',', '.') . '</td>
                  <td>' . $row['disetujui'] . '</td>
                  <td>' . $row['ditolak'] . '</td>
                  <td>' . $row['menunggu'] . '</td>';
            $total_honor_all += $row['total_honor'];
        }
        
        echo '</tr>';
    }
    
    // Total row
    if ($type == 'dosen' || $type == 'unit' || $type == 'bulanan') {
        echo '<tr class="total-row">';
        if ($type == 'dosen') {
            echo '<td colspan="7" style="text-align: right;"><strong>Total Keseluruhan:</strong></td>
                  <td><strong>Rp ' . number_format($total_honor_all, 0, ',', '.') . '</strong></td>';
        } elseif ($type == 'unit') {
            echo '<td colspan="3" style="text-align: right;"><strong>Total Keseluruhan:</strong></td>
                  <td><strong>Rp ' . number_format($total_honor_all, 0, ',', '.') . '</strong></td>';
        } elseif ($type == 'bulanan') {
            echo '<td colspan="3" style="text-align: right;"><strong>Total Keseluruhan:</strong></td>
                  <td colspan="4"><strong>Rp ' . number_format($total_honor_all, 0, ',', '.') . '</strong></td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</body></html>';
    
} elseif ($format == 'pdf') {
    // Untuk implementasi PDF yang sesungguhnya, perlu library seperti:
    // TCPDF, DomPDF, atau mPDF
    
    // Ini adalah placeholder sederhana
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . $title . ' - PDF</title>
        <style>
            body { font-family: Arial, sans-serif; }
            h2 { color: #333; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background-color: #f2f2f2; padding: 10px; text-align: left; border: 1px solid #ddd; }
            td { padding: 8px; border: 1px solid #ddd; }
            .total { font-weight: bold; background-color: #e8f4fd; }
        </style>
    </head>
    <body>
        <h2>' . $title . '</h2>
        <p>Tanggal Export: ' . date('d/m/Y H:i:s') . '</p>
        <p>Filter: Semester=' . ($semester ?: 'Semua') . ', Status=' . ($status ?: 'Semua') . '</p>
        <p><em>Catatan: Ini adalah preview untuk PDF. Untuk PDF yang sesungguhnya, perlu instalasi library PDF generator.</em></p>
        <table>
            <thead><tr>';
    
    // Header
    if ($type == 'dosen') {
        echo '<th>NPP</th><th>Nama Dosen</th><th>Total Matkul</th><th>Total Honor</th>';
    } elseif ($type == 'unit') {
        echo '<th>Jenis Honor</th><th>Jumlah Penerima</th><th>Total Honor</th>';
    } elseif ($type == 'bulanan') {
        echo '<th>Bulan</th><th>Jumlah Data</th><th>Total Honor</th>';
    }
    
    echo '</tr></thead><tbody>';
    
    mysqli_data_seek($query, 0); // Reset pointer
    $total_honor_all = 0;
    while ($row = mysqli_fetch_assoc($query)) {
        echo '<tr>';
        
        if ($type == 'dosen') {
            echo '<td>' . htmlspecialchars($row['npp_user']) . '</td>
                  <td>' . htmlspecialchars($row['nama_user']) . '</td>
                  <td>' . $row['total_matkul'] . '</td>
                  <td>Rp ' . number_format($row['total_honor'], 0, ',', '.') . '</td>';
            $total_honor_all += $row['total_honor'];
        } elseif ($type == 'unit') {
            echo '<td>' . htmlspecialchars($row['jenis']) . '</td>
                  <td>' . $row['jumlah_orang'] . '</td>
                  <td>Rp ' . number_format($row['total_honor'], 0, ',', '.') . '</td>';
            $total_honor_all += $row['total_honor'];
        } elseif ($type == 'bulanan') {
            echo '<td>' . ucfirst($row['bulan']) . '</td>
                  <td>' . $row['jumlah_data'] . '</td>
                  <td>Rp ' . number_format($row['total_honor'], 0, ',', '.') . '</td>';
            $total_honor_all += $row['total_honor'];
        }
        
        echo '</tr>';
    }
    
    // Total
    echo '<tr class="total">';
    if ($type == 'dosen') {
        echo '<td colspan="3" style="text-align: right;">Total:</td>
              <td>Rp ' . number_format($total_honor_all, 0, ',', '.') . '</td>';
    } elseif ($type == 'unit') {
        echo '<td colspan="2" style="text-align: right;">Total:</td>
              <td>Rp ' . number_format($total_honor_all, 0, ',', '.') . '</td>';
    } elseif ($type == 'bulanan') {
        echo '<td colspan="2" style="text-align: right;">Total:</td>
              <td>Rp ' . number_format($total_honor_all, 0, ',', '.') . '</td>';
    }
    echo '</tr>';
    
    echo '</tbody></table>
    </body>
    </html>';
}

exit();