<?php
/**
 * Install DigitalPersona WebSDK
 * This script attempts to download and install the DigitalPersona WebSDK
 */
session_start();

$install_log = [];
$errors = [];

// Function to download a file
function downloadFile($url, $destination) {
    global $install_log;
    
    $install_log[] = "Downloading $url to $destination";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $data !== false) {
        if (file_put_contents($destination, $data)) {
            $install_log[] = "Successfully downloaded to $destination";
            return true;
        } else {
            $install_log[] = "Failed to save file to $destination";
            return false;
        }
    } else {
        $install_log[] = "Failed to download file. HTTP code: $http_code";
        return false;
    }
}

// Function to extract WebSDK from the DigitalPersona installation
function extractWebSDK() {
    global $install_log, $errors;
    
    // Check if DigitalPersona SDK is installed locally
    $possible_paths = [
        'C:/Program Files/DigitalPersona/SDK/Windows/WebSDK/lib/websdk.client.bundle.min.js',
        'C:/Program Files (x86)/DigitalPersona/SDK/Windows/WebSDK/lib/websdk.client.bundle.min.js',
        'DigitalPersona/U.are.U SDK/Windows/WebSDK/lib/websdk.client.bundle.min.js'
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $install_log[] = "Found WebSDK at $path";
            $destination = 'js/websdk.client.bundle.min.js';
            
            if (copy($path, $destination)) {
                $install_log[] = "Successfully copied WebSDK to $destination";
                return true;
            } else {
                $errors[] = "Failed to copy WebSDK from $path to $destination";
            }
        }
    }
    
    return false;
}

// Try to install the WebSDK
$websdk_installed = false;

// Try to extract from local installation first
$install_log[] = "Attempting to extract WebSDK from local installation...";
$websdk_installed = extractWebSDK();

// If that fails, try to download from CDN
if (!$websdk_installed) {
    $install_log[] = "Local installation not found. Attempting to download from CDN...";
    
    // Try to download from a CDN
    $cdn_urls = [
        'https://cdnjs.cloudflare.com/ajax/libs/digitalpersona/1.0.0/websdk.client.bundle.min.js',
        'https://cdn.jsdelivr.net/npm/digitalpersona@1.0.0/websdk.client.bundle.min.js'
    ];
    
    foreach ($cdn_urls as $url) {
        $destination = 'js/websdk.client.bundle.min.js';
        if (downloadFile($url, $destination)) {
            $websdk_installed = true;
            break;
        }
    }
}

// Create a simple test HTML file
$test_html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>WebSDK Test</title>
    <script src="js/websdk.client.bundle.min.js"></script>
</head>
<body>
    <h1>WebSDK Test</h1>
    <div id="status">Checking DigitalPersona WebSDK status...</div>
    
    <script>
        if (typeof Fingerprint !== 'undefined') {
            document.getElementById('status').innerHTML = '<p style="color: green;">DigitalPersona WebSDK is loaded successfully!</p>';
            console.log('DigitalPersona WebSDK loaded:', Fingerprint);
        } else {
            document.getElementById('status').innerHTML = '<p style="color: red;">DigitalPersona WebSDK is NOT loaded.</p>';
        }
    </script>
</body>
</html>
HTML;

file_put_contents('test_websdk.html', $test_html);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install DigitalPersona WebSDK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-download"></i> Install DigitalPersona WebSDK</h1>
                <p class="lead">This script attempts to install the DigitalPersona WebSDK required for fingerprint functionality.</p>
                
                <?php if ($websdk_installed): ?>
                    <div class="alert alert-success">
                        <h4><i class="fas fa-check-circle"></i> Installation Successful</h4>
                        <p>The DigitalPersona WebSDK has been installed successfully.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <h4><i class="fas fa-exclamation-triangle"></i> Installation Incomplete</h4>
                        <p>Could not automatically install the DigitalPersona WebSDK. Please follow the manual installation instructions below.</p>
                    </div>
                <?php endif; ?>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-terminal"></i> Installation Log</h5>
                    </div>
                    <div class="card-body">
                        <pre class="bg-dark text-light p-3" style="border-radius: 5px;"><?php foreach ($install_log as $log): ?><?= htmlspecialchars($log) ?>
<?php endforeach; ?><?php if (!empty($errors)): ?><?= implode("\n", array_map('htmlspecialchars', $errors)) ?><?php endif; ?></pre>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Manual Installation Instructions</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Download the DigitalPersona WebSDK from the official website or your DigitalPersona SDK installation</li>
                            <li>Locate the <code>websdk.client.bundle.min.js</code> file in the WebSDK package</li>
                            <li>Copy this file to the <code>js/</code> directory of your project</li>
                            <li>Verify the installation by visiting <a href="test_websdk.html">test_websdk.html</a></li>
                        </ol>
                        
                        <h6>Alternative CDN Installation:</h6>
                        <p>If you prefer to use a CDN, add this line to your HTML pages that require fingerprint functionality:</p>
                        <pre>&lt;script src="https://cdnjs.cloudflare.com/ajax/libs/digitalpersona/1.0.0/websdk.client.bundle.min.js"&gt;&lt;/script&gt;</pre>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Return to Home
                    </a>
                    <?php if ($websdk_installed): ?>
                        <a href="fingerprint_diagnostics.php" class="btn btn-primary">
                            <i class="fas fa-stethoscope"></i> Run Diagnostics
                        </a>
                    <?php endif; ?>
                    <a href="test_websdk.html" class="btn btn-info">
                        <i class="fas fa-vial"></i> Test WebSDK
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>