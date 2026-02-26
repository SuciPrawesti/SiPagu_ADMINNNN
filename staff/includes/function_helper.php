<?php
/**
 * HELPER FUNCTIONS - SiPagu
 * Lokasi: staff/includes/function_helper.php
 */

/**
 * Fungsi untuk menampilkan alert Bootstrap
 */
function showAlert($type, $message) {
    $icons = [
        'success' => 'check-circle',
        'danger'  => 'exclamation-triangle',
        'warning' => 'exclamation-circle',
        'info'    => 'info-circle'
    ];
    $icon = $icons[$type] ?? 'info-circle';
    return '
    <div class="alert alert-' . $type . ' alert-dismissible show fade">
        <div class="alert-body">
            <button class="close" data-dismiss="alert">
                <span>×</span>
            </button>
            <i class="fas fa-' . $icon . ' mr-2"></i>
            ' . $message . '
        </div>
    </div>';
}

/**
 * Format angka ke Rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
