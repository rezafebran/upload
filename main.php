<?php
// Konfigurasi
$upload_dir = "uploads/";
$max_files = 30;
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// Buat direktori upload jika belum ada
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
$error = '';

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES["files"])) {
        $total_files = count($_FILES["files"]["name"]);
        
        // Check if exceeding max files
        $existing_files = glob($upload_dir . "*");
        if ((count($existing_files) + $total_files) > $max_files) {
            $error = "Maksimal $max_files file. Saat ini sudah " . count($existing_files) . " file.";
        } else {
            for ($i = 0; $i < $total_files; $i++) {
                $file_name = $_FILES["files"]["name"][$i];
                $file_tmp = $_FILES["files"]["tmp_name"][$i];
                $file_type = $_FILES["files"]["type"][$i];
                $file_size = $_FILES["files"]["size"][$i];
                
                // Validasi
                if (!in_array($file_type, $allowed_types)) {
                    $error = "Tipe file tidak diizinkan. Hanya jpg, png, dan gif.";
                    continue;
                }
                
                if ($file_size > $max_file_size) {
                    $error = "Ukuran file terlalu besar. Maksimal 5MB.";
                    continue;
                }
                
                // Custom filename jika ada
                $custom_name = $_POST["filename"] ?? "";
                if (!empty($custom_name)) {
                    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_name = $custom_name . "_" . time() . "." . $ext;
                } else {
                    $new_name = time() . "_" . $file_name;
                }
                
                // Upload file
                if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
                    $message = "File berhasil diupload!";
                } else {
                    $error = "Gagal mengupload file.";
                }
            }
        }
    }
}

// Handle file deletion
if (isset($_POST['delete'])) {
    $file_to_delete = $upload_dir . $_POST['delete'];
    if (file_exists($file_to_delete)) {
        unlink($file_to_delete);
        $message = "File berhasil dihapus!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload & Download Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f0f2f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .container {
                grid-template-columns: 1fr 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .input-group {
            margin-bottom: 15px;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .upload-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .upload-option {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-option:hover {
            background: #f8f9fa;
            border-color: #aaa;
        }

        .upload-option i {
            font-size: 2rem;
            color: #666;
            margin-bottom: 10px;
        }

        .file-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-download {
            color: #0066cc;
        }

        .btn-delete {
            color: #dc3545;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        #camera-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
        }

        #camera-preview {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 70vh;
        }

        .camera-buttons {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .camera-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            color: white;
            cursor: pointer;
        }

        .capture-btn {
            background: #0066cc;
        }

        .cancel-btn {
            background: #dc3545;
        }

        #canvas {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Upload Section -->
        <div class="card">
            <div class="card-title">
                Upload File (<?php echo count(glob($upload_dir . "*")); ?>/<?php echo $max_files; ?>)
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="input-group">
                    <input type="text" name="filename" placeholder="Prefix nama file (opsional)">
                    <input type="file" name="files[]" id="file-input" multiple accept="image/*" style="display: none;">
                </div>
                
                <div class="upload-options">
                    <div class="upload-option" onclick="document.getElementById('file-input').click()">
                        <i class="fas fa-folder-open"></i>
                        <p>Pilih File</p>
                        <small>Dari Storage</small>
                    </div>
                    
                    <div class="upload-option" onclick="startCamera()">
                        <i class="fas fa-camera"></i>
                        <p>Ambil Foto</p>
                        <small>Dari Kamera</small>
                    </div>
                </div>
            </form>
        </div>

        <!-- Download Section -->
        <div class="card">
            <div class="card-title">Download File</div>
            <div class="file-list">
                <?php
                $files = glob($upload_dir . "*");
                if (empty($files)): ?>
                    <p style="text-align: center; color: #666;">Belum ada file yang diupload</p>
                <?php else:
                    foreach($files as $file):
                        $file_name = basename($file);
                ?>
                    <div class="file-item">
                        <div class="file-info">
                            <i class="fas fa-file-image"></i>
                            <span><?php echo $file_name; ?></span>
                        </div>
                        <div class="file-actions">
                            <a href="<?php echo $upload_dir . $file_name; ?>" download class="btn btn-download">
                                <i class="fas fa-download"></i>
                            </a>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="delete" value="<?php echo $file_name; ?>" class="btn btn-delete">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach;
                endif; ?>
            </div>
        </div>
    </div>

    <!-- Camera UI -->
    <div id="camera-container">
        <video id="camera-preview" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
        <div class="camera-buttons">
            <button class="camera-btn capture-btn" onclick="capturePhoto()">Ambil Foto</button>
            <button class="camera-btn cancel-btn" onclick="stopCamera()">Batal</button>
        </div>
    </div>

    <script>
        let stream = null;

        // Start camera
        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' },
                    audio: false
                });
                const video = document.getElementById('camera-preview');
                video.srcObject = stream;
                document.getElementById('camera-container').style.display = 'block';
            } catch (err) {
                alert('Tidak dapat mengakses kamera. Pastikan memberikan izin kamera.');
            }
        }

        // Stop camera
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            document.getElementById('camera-container').style.display = 'none';
        }

        // Capture photo
        function capturePhoto() {
            const video = document.getElementById('camera-preview');
            const canvas = document.getElementById('canvas');
            
            // Set canvas size to match video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw video frame to canvas
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert to file and submit
            canvas.toBlob(blob => {
                const formData = new FormData();
                const fileName = 'camera_' + Date.now() + '.jpg';
                
                formData.append('files[]', blob, fileName);
                
                // Get filename if set
                const filenameInput = document.querySelector('input[name="filename"]');
                if (filenameInput.value) {
                    formData.append('filename', filenameInput.value);
                }
                
                // Submit using fetch
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    stopCamera();
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal mengupload foto. Silakan coba lagi.');
                });
            }, 'image/jpeg', 0.8);
        }

        // Auto submit form when files selected
        document.getElementById('file-input').onchange = function() {
            this.form.submit();
        };
    </script>
</body>
</html>