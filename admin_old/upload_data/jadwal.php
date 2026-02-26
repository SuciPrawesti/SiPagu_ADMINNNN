<?php
/**
 * DATA JADWAL - SiPagu
 * Lokasi: admin/jadwal.php
 */

require_once __DIR__ . '../../auth.php';
require_once __DIR__ . '../../../config.php';

$page_title = "Data Jadwal";

// ambil data
$query = mysqli_query($koneksi, "
    SELECT j.*, u.nama_user
    FROM t_jadwal j
    LEFT JOIN t_user u ON j.id_user = u.id_user
    ORDER BY j.id_jdwl DESC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="main-content">
<section class="section">
    <div class="section-header">
        <h1>Data Jadwal</h1>
    </div>
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_SESSION['success_message']; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>    
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>    

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $_SESSION['error_message']; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>    
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>    

    <div class="section-body">
        <div class="card">
            <div class="card-header">
                <h4>Daftar Jadwal</h4>
                <div class="card-header-action">
                    <a href="<?= BASE_URL ?>admin/upload_jadwal.php"
                       class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Data
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="table-1">
                        <thead>
    <tr>
        <th>No</th>
        <th>Semester</th>
        <th>Kode Mata Kuliah</th>
        <th>Nama Mata Kuliah</th>
        <th>Staff</th>
        <th>Jumlah Mahasiswa</th>
    </tr>
        <?php
        $no = 1;
        $jadwal = mysqli_query($koneksi, "select * from t_jadwal");
        while ($jdw=mysqli_fetch_assoc($jadwal)){
        ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= $jdw['semester']?></td>
        <td><?= $jdw['kode_matkul']?></td>
        <td><?= $jdw['nama_matkul']?></td>
        <td><?= $jdw['id_user']?></td>
        <td><?= $jdw['jml_mhs']?></td>
        <?php
        }?>

                                <div class="btn-group">

                                    <!-- EDIT -->
                                    <a href="../CRUD/edit_data/edit_jadwal.php?id_jdwl=<?= $row['id_jdwl'] ?>"
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- HAPUS -->
                                    <form action="../CRUD/hapus_data/hapus_jadwal.php"
                                          method="POST"
                                          style="display:inline;">
                                        <input type="hidden" name="id_jdwl"
                                               value="<?= $row['id_jdwl'] ?>">
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Yakin hapus data ini?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                        
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
</div>

<?php
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>

<script src="<?= ASSETS_URL ?>js/page/modules-datatables.js"></script>
<script>
$(function () {
    $('#table-1').DataTable({
        pageLength: 10,
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Data tidak ditemukan",
            info: "Halaman _PAGE_ dari _PAGES_",
            paginate: {
                next: "Berikutnya",
                previous: "Sebelumnya"
            }
        }
    });
});
</script>