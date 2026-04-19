<?php
/**
 * SPACEX Trading Academy — Secure File Upload API
 * Handles instructor video and photo uploads ensuring type safety and sandboxed paths.
 */

require_once __DIR__ . '/../includes/auth_middleware.php';

// Ensure standard requests don't time out during large video uploads
set_time_limit(600); // 10 minutes

$action = get_param('action', 'upload');

switch ($action) {
    case 'upload':
        handle_upload();
        break;
    default:
        ApiResponse::error('Invalid action', 400);
}

function handle_upload() {
    $user = require_admin(); // Only admins can upload files
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Method not allowed', 405);
    }
    
    if (!isset($_FILES['file'])) {
        ApiResponse::error('No file provided in the request', 400);
    }

    $file = $_FILES['file'];
    
    // Check for PHP upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        $errorMessage = $uploadErrors[$file['error']] ?? 'Unknown upload error.';
        ApiResponse::error($errorMessage, 400);
    }

    // Security Verification: Allow Specific MIME types
    $allowedMimes = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf'
    ];

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    if (!array_key_exists($mimeType, $allowedMimes)) {
        ApiResponse::forbidden('Invalid file type. Only MP4, WebM, JPEG, PNG, WebP, and PDF are allowed. Detected: ' . $mimeType);
    }

    // Set Max File Size Constraints
    // 500 MB for videos, 5 MB for images/documents
    $isImage = strpos($mimeType, 'image/') === 0;
    $maxSize = $isImage ? 5 * 1024 * 1024 : 500 * 1024 * 1024; 

    if ($file['size'] > $maxSize) {
        ApiResponse::forbidden('File size exceeds the limit (' . ($maxSize / 1024 / 1024) . ' MB allowed)');
    }

    // Determine target directory
    $baseUploadDir = strpos($mimeType, 'video/') === 0 ? '/videos/' : '/assets/uploads/';
    $targetDir = realpath(__DIR__ . '/../../') . $baseUploadDir;
    
    // Ensure the directory exists
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Generate strict filename avoiding path traversal
    $extension = $allowedMimes[$mimeType];
    $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $timestamp = time();
    $finalFilename = "{$safeName}_{$timestamp}.{$extension}";
    $targetPath = $targetDir . $finalFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $relativePath = trim($baseUploadDir, '/') . '/' . $finalFilename;

        ApiResponse::success([
            'message' => 'File uploaded successfully',
            'file_url' => $relativePath,
            'file_type' => $mimeType,
            'file_size' => $file['size']
        ], 'File Uploaded', 201);
    } else {
        ApiResponse::error('Failed to move uploaded file to target directory.', 500);
    }
}
