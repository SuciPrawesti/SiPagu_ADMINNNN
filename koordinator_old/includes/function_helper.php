<?php
/**
 * HELPER FUNCTIONS - SiPagu (KOORDINATOR)
 * Lokasi: koordinator/includes/function_helper.php
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

/**
 * Get status badge
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'draft' => 'secondary'
    ];
    
    $status = strtolower($status);
    $color = $badges[$status] ?? 'secondary';
    
    return '<span class="badge badge-' . $color . '">' . ucfirst($status) . '</span>';
}
?>