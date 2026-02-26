<?php
/**
 * REKAP PER UNIT/PRODI - SiPagu Koordinator
 * Lokasi: koordinator/rekap/rekap_unit.php
 */
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';

// Filter
$semester = $_GET['semester'] ?? '';
$jenis = $_GET['jenis'] ?? 'all'; // all, dosen, panitia, pata

// Build queries untuk setiap unit
$where_dosen = $semester ? "AND thd.semester = '$semester'" : "";
$where_panitia = $semester ? "AND tu.semester = '$semester'" : "";
$where_pata = $semester ? "AND tpt.semester = '$semester'" : "";

// Query rekap honor dosen per prodi
$rekap_dosen = mysqli_query($koneksi, "
    SELECT 
        'Dosen Mengajar' as jenis,
        COUNT(DISTINCT u.id_user) as jumlah_orang,
        COUNT(thd.id_thd) as jumlah_transaksi,
        SUM(thd.jml_tm * thd.sks_tempuh * u.honor_persks) as total_honor
    FROM t_transaksi_honor_dosen thd
    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    LEFT JOIN t_user u ON j.id_user = u.id_user
    WHERE 1=1 $where_dosen
");

// Query rekap honor panitia
$rekap_panitia = mysqli_query($koneksi, "
    SELECT 
        'Panitia Ujian' as jenis,
        COUNT(DISTINCT tu.id_user) as jumlah_orang,
        COUNT(tu.id_tu) as jumlah_transaksi,
        SUM(p.honor_std) as total_honor
    FROM t_transaksi_ujian tu
    LEFT JOIN t_panitia p ON tu.id_panitia = p.id_pnt
    WHERE 1=1 $where_panitia
");

// Query rekap honor PA/TA
$rekap_pata = mysqli_query($koneksi, "
    SELECT 
        'PA/TA' as jenis,
        COUNT(DISTINCT tpt.id_user) as jumlah_orang,
        COUNT(tpt.id_tpt) as jumlah_transaksi,
        SUM(tpt.jml_mhs_bimbingan * p.honor_std) as total_honor
    FROM t_transaksi_pa_ta tpt
    LEFT JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
    WHERE 1=1 $where_pata
");

// Ambil data
$dosen_data = mysqli_fetch_assoc($rekap_dosen);
$panitia_data = mysqli_fetch_assoc($rekap_panitia);
$pata_data = mysqli_fetch_assoc($rekap_pata);

// Hitung total semua jenis
$total_orang = ($dosen_data['jumlah_orang'] ?? 0) + 
               ($panitia_data['jumlah_orang'] ?? 0) + 
               ($pata_data['jumlah_orang'] ?? 0);
               
$total_transaksi = ($dosen_data['jumlah_transaksi'] ?? 0) + 
                   ($panitia_data['jumlah_transaksi'] ?? 0) + 
                   ($pata_data['jumlah_transaksi'] ?? 0);
                   
$total_honor = ($dosen_data['total_honor'] ?? 0) + 
               ($panitia_data['total_honor'] ?? 0) + 
               ($pata_data['total_honor'] ?? 0);

// Query untuk filter semester
$semesters = mysqli_query($koneksi, "
    SELECT DISTINCT semester FROM (
        SELECT semester FROM t_transaksi_honor_dosen
        UNION
        SELECT semester FROM t_transaksi_ujian
        UNION
        SELECT semester FROM t_transaksi_pa_ta
    ) as semua_semester
    ORDER BY semester DESC
    LIMIT 5
");

// Hitung persentase untuk grafik
$persen_dosen = $total_honor > 0 ? round(($dosen_data['total_honor'] ?? 0) / $total_honor * 100, 1) : 0;
$persen_panitia = $total_honor > 0 ? round(($panitia_data['total_honor'] ?? 0) / $total_honor * 100, 1) : 0;
$persen_pata = $total_honor > 0 ? round(($pata_data['total_honor'] ?? 0) / $total_honor * 100, 1) : 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_koordinator.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Rekap Honor per Unit/Jenis</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Rekap per Unit</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Rekapitulasi Honor Berdasarkan Jenis Kegiatan</h4>
                            <div class="card-header-action">
                                <div class="badge badge-info">
                                    <i class="fas fa-layer-group mr-1"></i> Per Unit
                                </div>
                                <div class="dropdown d-inline">
                                    <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-download mr-1"></i> Export
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="exportDropdown">
                                        <a class="dropdown-item" href="#" onclick="exportToPDF()">
                                            <i class="fas fa-file-pdf text-danger mr-1"></i> PDF
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="exportToExcel()">
                                            <i class="fas fa-file-excel text-success mr-1"></i> Excel
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="exportToPrint()">
                                            <i class="fas fa-print text-primary mr-1"></i> Print
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filter Card -->
                            <div class="card card-primary mb-4">
                                <div class="card-header">
                                    <h4><i class="fas fa-filter mr-2"></i>Filter Rekap Unit</h4>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="filter-form">
                                        <div class="row align-items-end">
                                            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                                                <div class="form-group mb-0">
                                                    <label class="form-label">Semester</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text">
                                                                <i class="fas fa-graduation-cap"></i>
                                                            </div>
                                                        </div>
                                                        <select name="semester" class="form-control select2">
                                                            <option value="">Semua Semester</option>
                                                            <?php 
                                                            mysqli_data_seek($semesters, 0);
                                                            while ($sem = mysqli_fetch_assoc($semesters)): 
                                                            ?>
                                                            <option value="<?= htmlspecialchars($sem['semester']) ?>" <?= $sem['semester'] == $semester ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($sem['semester']) ?>
                                                            </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                                                <div class="form-group mb-0">
                                                    <label class="form-label">Jenis Honor</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text">
                                                                <i class="fas fa-layer-group"></i>
                                                            </div>
                                                        </div>
                                                        <select name="jenis" class="form-control select2">
                                                            <option value="all" <?= $jenis == 'all' ? 'selected' : '' ?>>Semua Jenis</option>
                                                            <option value="dosen" <?= $jenis == 'dosen' ? 'selected' : '' ?>>Dosen Mengajar</option>
                                                            <option value="panitia" <?= $jenis == 'panitia' ? 'selected' : '' ?>>Panitia Ujian</option>
                                                            <option value="pata" <?= $jenis == 'pata' ? 'selected' : '' ?>>PA/TA</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-12">
                                                <div class="form-group mb-0">
                                                    <label class="form-label d-none d-md-block">&nbsp;</label>
                                                    <div class="btn-group w-100" role="group">
                                                        <button type="submit" class="btn btn-primary btn-lg">
                                                            <i class="fas fa-filter mr-1"></i> Terapkan Filter
                                                        </button>
                                                        <a href="rekap_unit.php" class="btn btn-outline-secondary btn-lg">
                                                            <i class="fas fa-sync-alt mr-1"></i> Reset
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Info Alert -->
                            <div class="alert alert-info alert-has-icon">
                                <div class="alert-icon"><i class="fas fa-info-circle"></i></div>
                                <div class="alert-body">
                                    <div class="alert-title">Informasi Rekap Unit</div>
                                    <p class="mb-0">
                                        <strong>Fitur Rekap per Unit/Jenis:</strong> 
                                        <ul class="mb-0 pl-3">
                                            <li>Menampilkan data rekap honor berdasarkan jenis kegiatan akademik</li>
                                            <li>Statistik mencakup tiga jenis honor: Dosen Mengajar, Panitia Ujian, dan PA/TA</li>
                                            <li>Distribusi honor ditampilkan dalam bentuk grafik dan tabel perbandingan</li>
                                            <li>Data dapat diekspor dalam format PDF, Excel, atau dicetak langsung</li>
                                        </ul>
                                    </p>
                                </div>
                            </div>

                            <!-- Summary Cards -->
                            <div class="row mb-4">
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-primary">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Dosen Mengajar</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($dosen_data['jumlah_orang'] ?? 0) ?>
                                            </div>
                                            <div class="card-footer">
                                                <small class="text-primary">
                                                    Rp <?= number_format($dosen_data['total_honor'] ?? 0, 0, ',', '.') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-success">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Panitia Ujian</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($panitia_data['jumlah_orang'] ?? 0) ?>
                                            </div>
                                            <div class="card-footer">
                                                <small class="text-success">
                                                    Rp <?= number_format($panitia_data['total_honor'] ?? 0, 0, ',', '.') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-warning">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>PA/TA</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($pata_data['jumlah_orang'] ?? 0) ?>
                                            </div>
                                            <div class="card-footer">
                                                <small class="text-warning">
                                                    Rp <?= number_format($pata_data['total_honor'] ?? 0, 0, ',', '.') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-info">
                                            <i class="fas fa-chart-pie"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Semua</h4>
                                            </div>
                                            <div class="card-body">
                                                <?= number_format($total_orang) ?>
                                            </div>
                                            <div class="card-footer">
                                                <small class="text-info">
                                                    Rp <?= number_format($total_honor, 0, ',', '.') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Grafik Distribusi -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4><i class="fas fa-chart-pie mr-2"></i>Distribusi Honor per Jenis</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="chart-container" style="height: 250px; position: relative;">
                                                <!-- Simple pie chart using CSS -->
                                                <div class="pie-chart">
                                                    <div class="pie-slice" style="--percentage: <?= $persen_dosen ?>%; --color: #4361ee;">
                                                        <div class="pie-center">
                                                            <span class="pie-value"><?= $persen_dosen ?>%</span>
                                                        </div>
                                                    </div>
                                                    <div class="pie-slice" style="--percentage: <?= $persen_panitia ?>%; --color: #28a745;">
                                                        <div class="pie-center">
                                                            <span class="pie-value"><?= $persen_panitia ?>%</span>
                                                        </div>
                                                    </div>
                                                    <div class="pie-slice" style="--percentage: <?= $persen_pata ?>%; --color: #ffc107;">
                                                        <div class="pie-center">
                                                            <span class="pie-value"><?= $persen_pata ?>%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="chart-legend">
                                                <div class="legend-item mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-primary">Dosen Mengajar</span>
                                                        <span class="font-weight-bold"><?= $persen_dosen ?>%</span>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-primary" style="width: <?= $persen_dosen ?>%"></div>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">
                                                        <?= number_format($dosen_data['jumlah_orang'] ?? 0) ?> orang • 
                                                        Rp <?= number_format($dosen_data['total_honor'] ?? 0, 0, ',', '.') ?>
                                                    </small>
                                                </div>
                                                <div class="legend-item mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-success">Panitia Ujian</span>
                                                        <span class="font-weight-bold"><?= $persen_panitia ?>%</span>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-success" style="width: <?= $persen_panitia ?>%"></div>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">
                                                        <?= number_format($panitia_data['jumlah_orang'] ?? 0) ?> orang • 
                                                        Rp <?= number_format($panitia_data['total_honor'] ?? 0, 0, ',', '.') ?>
                                                    </small>
                                                </div>
                                                <div class="legend-item">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-warning">PA/TA</span>
                                                        <span class="font-weight-bold"><?= $persen_pata ?>%</span>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-warning" style="width: <?= $persen_pata ?>%"></div>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">
                                                        <?= number_format($pata_data['jumlah_orang'] ?? 0) ?> orang • 
                                                        Rp <?= number_format($pata_data['total_honor'] ?? 0, 0, ',', '.') ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Detail -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4><i class="fas fa-table mr-2"></i>Detail Rekap per Jenis Honor</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th class="text-center align-middle" style="width: 50px;">#</th>
                                                    <th class="align-middle">Jenis Honor</th>
                                                    <th class="text-center align-middle">Jumlah Penerima</th>
                                                    <th class="text-center align-middle">Jumlah Transaksi</th>
                                                    <th class="text-center align-middle">Total Honor</th>
                                                    <th class="text-center align-middle">Rata-rata per Orang</th>
                                                    <th class="text-center align-middle">Persentase</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $data_array = [
                                                    ['jenis' => 'Dosen Mengajar', 'data' => $dosen_data, 'color' => 'primary'],
                                                    ['jenis' => 'Panitia Ujian', 'data' => $panitia_data, 'color' => 'success'],
                                                    ['jenis' => 'PA/TA', 'data' => $pata_data, 'color' => 'warning']
                                                ];
                                                
                                                $no = 1;
                                                foreach ($data_array as $item): 
                                                    $data = $item['data'];
                                                    $color = $item['color'];
                                                    $percentage = $total_honor > 0 ? (($data['total_honor'] ?? 0) / $total_honor) * 100 : 0;
                                                    $avg_per_orang = ($data['jumlah_orang'] ?? 0) > 0 ? ($data['total_honor'] ?? 0) / ($data['jumlah_orang'] ?? 1) : 0;
                                                ?>
                                                <tr>
                                                    <td class="text-center align-middle">
                                                        <span class="text-muted"><?= $no++ ?></span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="d-flex align-items-center">
                                                            <?php if($item['jenis'] == 'Dosen Mengajar'): ?>
                                                                <div class="avatar-icon-wrapper mr-2">
                                                                    <div class="avatar-icon bg-primary">
                                                                        <i class="fas fa-user-graduate"></i>
                                                                    </div>
                                                                </div>
                                                            <?php elseif($item['jenis'] == 'Panitia Ujian'): ?>
                                                                <div class="avatar-icon-wrapper mr-2">
                                                                    <div class="avatar-icon bg-success">
                                                                        <i class="fas fa-users"></i>
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="avatar-icon-wrapper mr-2">
                                                                    <div class="avatar-icon bg-warning">
                                                                        <i class="fas fa-user-tie"></i>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            <span class="font-weight-bold text-dark">
                                                                <?= $item['jenis'] ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-<?= $color ?> badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                            <?= number_format($data['jumlah_orang'] ?? 0) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-secondary badge-pill p-2" style="min-width: 35px; display: inline-block;">
                                                            <?= number_format($data['jumlah_transaksi'] ?? 0) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="font-weight-bold text-<?= $color ?>">
                                                            Rp <?= number_format($data['total_honor'] ?? 0, 0, ',', '.') ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="text-dark">
                                                            Rp <?= number_format($avg_per_orang, 0, ',', '.') ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <div class="d-flex align-items-center justify-content-center">
                                                            <div class="progress flex-grow-1 mr-2" style="height: 8px; max-width: 100px;">
                                                                <div class="progress-bar bg-<?= $color ?>" style="width: <?= $percentage ?>%"></div>
                                                            </div>
                                                            <span class="font-weight-bold"><?= number_format($percentage, 1) ?>%</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                
                                                <!-- Total Keseluruhan -->
                                                <tr class="table-active font-weight-bold">
                                                    <td colspan="2" class="text-right align-middle">TOTAL KESELURUHAN:</td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-info p-2"><?= number_format($total_orang) ?></span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-info p-2"><?= number_format($total_transaksi) ?></span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="text-primary">
                                                            Rp <?= number_format($total_honor, 0, ',', '.') ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <?php 
                                                        $avg_total = $total_orang > 0 ? $total_honor / $total_orang : 0;
                                                        ?>
                                                        <span class="text-dark">
                                                            Rp <?= number_format($avg_total, 0, ',', '.') ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="text-dark font-weight-bold">100%</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Report Summary -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4><i class="fas fa-clipboard-list mr-2"></i>Ringkasan Laporan</h4>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td><strong>Periode Laporan</strong></td>
                                                    <td>:</td>
                                                    <td>
                                                        <?= $semester ? "Semester $semester" : "Semua Semester" ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Jenis Honor</strong></td>
                                                    <td>:</td>
                                                    <td>
                                                        <?= $jenis == 'all' ? 'Semua Jenis' : 
                                                            ($jenis == 'dosen' ? 'Dosen Mengajar' : 
                                                            ($jenis == 'panitia' ? 'Panitia Ujian' : 'PA/TA')) ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Tanggal Generate</strong></td>
                                                    <td>:</td>
                                                    <td><?= date('d F Y H:i:s') ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Jumlah Data</strong></td>
                                                    <td>:</td>
                                                    <td><?= number_format($total_orang) ?> penerima</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Transaksi</strong></td>
                                                    <td>:</td>
                                                    <td><?= number_format($total_transaksi) ?> transaksi</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Honor</strong></td>
                                                    <td>:</td>
                                                    <td>Rp <?= number_format($total_honor, 0, ',', '.') ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Dibuat Oleh</strong></td>
                                                    <td>:</td>
                                                    <td><?= htmlspecialchars($_SESSION['username'] ?? 'Koordinator') ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4><i class="fas fa-sticky-note mr-2"></i>Catatan Laporan</h4>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                Laporan rekap per unit ini memberikan gambaran distribusi honor berdasarkan jenis kegiatan akademik.
                                            </p>
                                            <ul class="mb-0 pl-3">
                                                <li>Data diambil dari tiga tabel transaksi: dosen, panitia, dan PA/TA</li>
                                                <li>Dosen Mengajar: honor berdasarkan SKS dan jumlah TM</li>
                                                <li>Panitia Ujian: honor tetap berdasarkan peran panitia</li>
                                                <li>PA/TA: honor berdasarkan jumlah mahasiswa bimbingan</li>
                                                <li>Rata-rata per orang dihitung dari total honor dibagi jumlah penerima</li>
                                                <li>Laporan ini untuk monitoring alokasi anggaran honor</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<!-- End Main Content -->

<style>
/* Custom styles untuk halaman rekap unit */
.avatar-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.avatar-icon.bg-primary {
    background-color: #4361ee;
}

.avatar-icon.bg-success {
    background-color: #28a745;
}

.avatar-icon.bg-warning {
    background-color: #ffc107;
}

.avatar-icon.bg-info {
    background-color: #17a2b8;
}

.empty-state {
    padding: 2rem;
    text-align: center;
}

.empty-state-icon {
    margin-bottom: 1.5rem;
}

.empty-state h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.card-statistic-1 {
    height: 100%;
}

.card-statistic-1 .card-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}

.card-statistic-1 .card-body {
    font-size: 1.8rem;
    font-weight: bold;
    padding-bottom: 0.5rem;
}

.card-statistic-1 .card-footer {
    background: transparent;
    border-top: 1px solid #e3e6f0;
    padding-top: 0.5rem;
    font-size: 0.9rem;
}

.table-active {
    background-color: rgba(0, 123, 255, 0.1) !important;
}

/* Responsive table */
.table-responsive {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    overflow: hidden;
}

.table-responsive table {
    margin-bottom: 0;
    min-width: 900px;
}

.table-responsive th {
    white-space: nowrap;
    font-size: 0.85rem;
    padding: 12px 8px;
    border-bottom: 2px solid #e3e6f0 !important;
}

.table-responsive td {
    padding: 10px 8px;
    vertical-align: middle;
    font-size: 0.9rem;
}

.table-responsive .badge-pill {
    font-size: 0.8rem;
    padding: 0.35em 0.65em;
}

/* Filter form buttons */
.filter-form .btn-group {
    margin-top: 10px;
}

.filter-form .btn {
    padding: 8px 20px;
    font-size: 14px;
}

/* Pie Chart Styles */
.pie-chart {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    position: relative;
    margin: 0 auto;
    background: conic-gradient(
        var(--color) 0% var(--percentage, 0%),
        transparent var(--percentage, 0%) 100%
    );
    overflow: hidden;
}

.pie-center {
    position: absolute;
    width: 120px;
    height: 120px;
    background: white;
    border-radius: 50%;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.pie-value {
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
}

.chart-legend {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
}

.legend-item {
    padding: 0.5rem 0;
}

.legend-item:not(:last-child) {
    border-bottom: 1px solid #e3e6f0;
}

/* Mobile Responsiveness */
@media (max-width: 991.98px) {
    .card-header-action .btn {
        margin-bottom: 5px;
    }
    
    .filter-form .btn-group {
        margin-top: 15px;
    }
}

@media (max-width: 768px) {
    /* Summary cards - 2 per row */
    .col-xl-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    
    /* Chart adjustments */
    .chart-container {
        height: 200px !important;
    }
    
    .pie-chart {
        width: 150px;
        height: 150px;
    }
    
    .pie-center {
        width: 90px;
        height: 90px;
    }
    
    /* Filter form adjustments */
    .filter-form .btn-group .btn {
        width: 100%;
        margin-bottom: 5px;
    }
}

@media (max-width: 576px) {
    /* Adjust avatar size */
    .avatar-icon {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    /* Reduce font sizes */
    .table-responsive td {
        font-size: 0.8rem;
        padding: 8px 5px;
    }
    
    .table-responsive th {
        font-size: 0.75rem;
        padding: 10px 5px;
    }
    
    /* Summary cards - 1 per row */
    .col-xl-3 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 15px;
    }
    
    .card-statistic-1 .card-body {
        font-size: 1.5rem;
    }
    
    /* Chart adjustments for mobile */
    .chart-container {
        height: 150px !important;
    }
    
    .pie-chart {
        width: 120px;
        height: 120px;
    }
    
    .pie-center {
        width: 70px;
        height: 70px;
    }
    
    .pie-value {
        font-size: 0.9rem;
    }
    
    /* Filter form buttons adjustments for mobile */
    .filter-form .col-lg-4:last-child {
        margin-top: 15px;
    }
    
    .filter-form .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .filter-form .btn-group .btn {
        width: 100%;
        margin-bottom: 5px;
    }
    
    /* Adjust summary cards layout */
    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 15px;
    }
}

/* Print optimization */
@media print {
    .navbar, .sidebar, .card-header-action, .btn, .pagination, .modal,
    .filter-form, .alert, .card-statistic-1, .card-header, .breadcrumb,
    .section-header {
        display: none !important;
    }
    
    .card {
        border: 0 !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 11px;
        border: 1px solid #000 !important;
    }
    
    .table th {
        background-color: #f8f9fa !important;
        border: 1px solid #000 !important;
    }
    
    .table td {
        border: 1px solid #000 !important;
    }
    
    /* Hide chart in print */
    .pie-chart, .chart-legend {
        display: none !important;
    }
    
    /* Show report title */
    .main-content h1 {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
    }
}

/* Hover effects */
.table-responsive tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transition: background-color 0.2s ease;
}

/* Scrollbar styling for table */
.table-responsive::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<!-- Custom JavaScript untuk halaman ini -->
<script>
$(document).ready(function() {
    // Inisialisasi select2
    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Pilih...',
            theme: 'bootstrap4'
        });
    }
    
    // Responsive table adjustment
    function adjustTableResponsive() {
        const width = $(window).width();
        const tableResponsive = $('.table-responsive');
        
        if (width < 768) {
            tableResponsive.css('max-height', '400px');
            tableResponsive.css('overflow-y', 'auto');
        } else {
            tableResponsive.css('max-height', '');
            tableResponsive.css('overflow-y', '');
        }
    }
    
    // Initial adjustment
    adjustTableResponsive();
    
    // Adjust on resize
    $(window).resize(function() {
        adjustTableResponsive();
    });
});

// Fungsi export
function exportToPDF() {
    // Menggunakan export.php yang sudah ada
    const semester = '<?= $semester ?>';
    const jenis = '<?= $jenis ?>';
    
    window.location.href = '<?= KOORDINATOR_URL ?>rekap/export.php?' + 
        'type=unit&format=pdf' + 
        '&semester=' + semester + 
        '&jenis=' + jenis;
}

function exportToExcel() {
    // Menggunakan export.php yang sudah ada
    const semester = '<?= $semester ?>';
    const jenis = '<?= $jenis ?>';
    
    window.location.href = '<?= KOORDINATOR_URL ?>rekap/export.php?' + 
        'type=unit&format=excel' + 
        '&semester=' + semester + 
        '&jenis=' + jenis;
}

function exportToPrint() {
    window.print();
}

// Tambahkan event listener untuk tombol print
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        exportToPrint();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/footer_scripts.php'; ?>