<?php
/**
 * UPLOAD TEMPLATE - SiPagu
 * Template reusable untuk semua halaman upload
 * Lokasi: admin/includes/upload_template.php
 */

// Cek apakah sudah di-include
if (!defined('UPLOAD_TEMPLATE_INCLUDED')) {
    define('UPLOAD_TEMPLATE_INCLUDED', true);
    
    /**
     * Render halaman upload
     * 
     * @param array $config Konfigurasi halaman
     * @return string HTML output
     */
    function renderUploadPage($config) {
        // Default configuration
        $defaults = [
            'title' => 'Upload Data',
            'description' => 'Upload data dari file Excel atau input manual',
            'table_name' => '',
            'template_file' => '',
            'instructions' => [],
            'form_action' => '',
            'form_fields' => [],
            'mode' => 'both', // 'upload', 'manual', or 'both'
            'preview_data' => null
        ];
        
        $config = array_merge($defaults, $config);
        
        ob_start();
        ?>
        
        <div class="upload-page">
            <div class="upload-container">
                
                <!-- Page Header -->
                <div class="mb-4">
                    <h2 class="h3 font-weight-normal text-dark mb-2"><?= htmlspecialchars($config['title']) ?></h2>
                    <p class="text-muted mb-0"><?= htmlspecialchars($config['description']) ?></p>
                </div>
                
                <?php if (!empty($config['instructions'])): ?>
                <!-- Instructions -->
                <div class="instructions-box mb-4">
                    <h4 class="instructions-title">
                        <i class="fas fa-info-circle"></i>
                        Petunjuk Penggunaan
                    </h4>
                    <ul class="instructions-list">
                        <?php foreach ($config['instructions'] as $instruction): ?>
                            <li><?= htmlspecialchars($instruction) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($config['template_file'])): ?>
                <!-- Template Download -->
                <div class="template-card mb-4">
                    <div class="template-header">
                        <h5 class="template-title">Download Template</h5>
                        <a href="templates/<?= htmlspecialchars($config['template_file']) ?>" 
                           class="template-download-btn" 
                           data-template="<?= pathinfo($config['template_file'], PATHINFO_FILENAME) ?>">
                            <i class="fas fa-download"></i>
                            Download Template
                        </a>
                    </div>
                    <p class="text-muted mb-0 small">
                        Gunakan template ini untuk memastikan format file Excel sesuai dengan sistem.
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if ($config['mode'] === 'both'): ?>
                <!-- Mode Toggle -->
                <div class="mode-toggle mb-4">
                    <button type="button" class="mode-btn active" data-mode="upload">
                        <i class="fas fa-file-upload mr-2"></i>
                        Upload Excel
                    </button>
                    <button type="button" class="mode-btn" data-mode="manual">
                        <i class="fas fa-keyboard mr-2"></i>
                        Input Manual
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Upload Excel Section -->
                <?php if (in_array($config['mode'], ['upload', 'both'])): ?>
                <div class="upload-card mb-4 <?= $config['mode'] === 'both' ? '' : 'hidden' ?>" 
                     id="uploadSection">
                    <div class="upload-card-header">
                        <h3 class="upload-card-title">
                            <i class="fas fa-file-excel"></i>
                            Upload File Excel
                        </h3>
                    </div>
                    <div class="upload-card-body">
                        <form action="<?= htmlspecialchars($config['form_action']) ?>" 
                              method="POST" 
                              enctype="multipart/form-data" 
                              data-validate>
                            
                            <!-- Drag & Drop Area -->
                            <div class="upload-drop-area">
                                <input type="file" 
                                       name="filexls" 
                                       class="upload-file-input" 
                                       accept=".xls,.xlsx">
                                <div class="upload-drop-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="upload-drop-text">
                                    <h4>Pilih File Excel</h4>
                                    <p>Seret file ke sini atau klik untuk memilih file<br>
                                       Format yang didukung: .xls, .xlsx (maks. 10MB)</p>
                                </div>
                            </div>
                            
                            <!-- File Info -->
                            <div class="file-info">
                                <div class="file-info-header">
                                    <div class="file-name">filename.xlsx</div>
                                    <div class="file-size">0 KB</div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>
                            
                            <!-- Additional Options -->
                            <div class="upload-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Semester</label>
                                        <select name="semester" class="form-control" data-validate="text">
                                            <option value="">Pilih Semester</option>
                                            <option value="20241">2024 Ganjil (20241)</option>
                                            <option value="20242">2024 Genap (20242)</option>
                                            <option value="20251">2025 Ganjil (20251)</option>
                                            <option value="20252">2025 Genap (20252)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">
                                            <input type="checkbox" name="overwrite" value="1">
                                            Timpa data yang sudah ada
                                        </label>
                                        <span class="form-text">
                                            Jika dicentang, data dengan ID yang sama akan ditimpa.
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Submit Buttons -->
                                <div class="btn-group">
                                    <button type="submit" name="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i>
                                        Upload dan Proses
                                    </button>
                                    <button type="button" class="btn btn-outline btn-reset" data-form="uploadForm">
                                        <i class="fas fa-redo"></i>
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Manual Input Section -->
                <?php if (in_array($config['mode'], ['manual', 'both'])): ?>
                <div class="upload-card mb-4 <?= $config['mode'] === 'manual' ? '' : 'hidden' ?>" 
                     id="manualSection">
                    <div class="upload-card-header">
                        <h3 class="upload-card-title">
                            <i class="fas fa-edit"></i>
                            Input Data Manual
                        </h3>
                    </div>
                    <div class="upload-card-body">
                        <form action="<?= htmlspecialchars($config['form_action']) ?>" 
                              method="POST" 
                              data-validate 
                              id="manualForm">
                            
                            <div class="upload-form">
                                <div class="form-row">
                                    <?php foreach ($config['form_fields'] as $field): ?>
                                    <div class="form-group">
                                        <label class="form-label">
                                            <?= htmlspecialchars($field['label']) ?>
                                            <?php if (!empty($field['required'])): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                        </label>
                                        
                                        <?php if ($field['type'] === 'select'): ?>
                                            <select name="<?= $field['name'] ?>" 
                                                    class="form-control" 
                                                    data-validate="<?= $field['validate'] ?? '' ?>"
                                                    <?= !empty($field['required']) ? 'required' : '' ?>>
                                                <option value="">Pilih <?= $field['label'] ?></option>
                                                <?php if (!empty($field['options'])): ?>
                                                    <?php foreach ($field['options'] as $value => $label): ?>
                                                        <option value="<?= $value ?>"><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        <?php elseif ($field['type'] === 'textarea'): ?>
                                            <textarea name="<?= $field['name'] ?>" 
                                                      class="form-control" 
                                                      placeholder="<?= $field['placeholder'] ?? '' ?>"
                                                      data-validate="<?= $field['validate'] ?? '' ?>"
                                                      <?= !empty($field['required']) ? 'required' : '' ?>
                                                      rows="<?= $field['rows'] ?? 3 ?>"></textarea>
                                        <?php else: ?>
                                            <input type="<?= $field['type'] ?>" 
                                                   name="<?= $field['name'] ?>" 
                                                   class="form-control" 
                                                   placeholder="<?= $field['placeholder'] ?? '' ?>"
                                                   data-validate="<?= $field['validate'] ?? '' ?>"
                                                   <?= !empty($field['required']) ? 'required' : '' ?>>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($field['help'])): ?>
                                            <span class="form-text"><?= $field['help'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Submit Buttons -->
                                <div class="btn-group">
                                    <button type="submit" name="submit_manual" class="btn btn-success">
                                        <i class="fas fa-save"></i>
                                        Simpan Data
                                    </button>
                                    <button type="button" class="btn btn-outline btn-reset" data-form="manualForm">
                                        <i class="fas fa-redo"></i>
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($config['preview_data'])): ?>
                <!-- Preview Data -->
                <div class="upload-card">
                    <div class="upload-card-header">
                        <h3 class="upload-card-title">
                            <i class="fas fa-table"></i>
                            Data Honor Dosen Terbaru
                        </h3>
                    </div>
                    <div class="upload-card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <?php 
                                        // Get column names from first row
                                        if (!empty($config['preview_data'])) {
                                            $firstRow = reset($config['preview_data']);
                                            foreach ($firstRow as $key => $value): ?>
                                                <th><?= htmlspecialchars($key) ?></th>
                                            <?php endforeach;
                                        } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($config['preview_data'] as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= htmlspecialchars($value) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
}
?>