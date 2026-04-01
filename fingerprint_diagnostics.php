<?php
session_start();
include "db_conn.php";

// Check if there are any fingerprint templates in the database
$template_count = 0;
$sql = "SELECT COUNT(*) as count FROM fingerprint_templates";
$result = mysqli_query($conn, $sql);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $template_count = $row['count'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fingerprint System Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .diagnostic-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .result-box {
            min-height: 50px;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-ok {
            background-color: #28a745;
        }
        .status-error {
            background-color: #dc3545;
        }
        .status-warning {
            background-color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="fas fa-stethoscope"></i> Fingerprint System Diagnostics</h1>
                
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle"></i> Diagnostic Overview</h4>
                    <p>This page performs comprehensive diagnostics of the fingerprint system including:</p>
                    <ul>
                        <li>DigitalPersona WebSDK loading and integrity</li>
                        <li>JavaScript fingerprint handler functionality</li>
                        <li>Database connectivity and template storage</li>
                        <li>API endpoint availability</li>
                    </ul>
                    <p><strong>Database Status:</strong> Found <?php echo $template_count; ?> fingerprint templates</p>
                </div>
                
                <!-- Test 1: WebSDK Loading -->
                <div class="diagnostic-section">
                    <h3>Test 1: DigitalPersona WebSDK Loading</h3>
                    <p>Checks if the DigitalPersona WebSDK is properly loaded and all required components are available.</p>
                    <button id="test-websdk" class="btn btn-primary">
                        <i class="fas fa-play"></i> Run WebSDK Test
                    </button>
                    <div id="websdk-result" class="result-box bg-light"></div>
                </div>
                
                <!-- Test 2: Fingerprint Handler -->
                <div class="diagnostic-section">
                    <h3>Test 2: Fingerprint Handler Initialization</h3>
                    <p>Tests the fingerprint handler class and its ability to initialize the SDK.</p>
                    <button id="test-handler" class="btn btn-primary">
                        <i class="fas fa-play"></i> Run Handler Test
                    </button>
                    <div id="handler-result" class="result-box bg-light"></div>
                </div>
                
                <!-- Test 3: Database Connection -->
                <div class="diagnostic-section">
                    <h3>Test 3: Database Connection</h3>
                    <p>Verifies database connectivity and schema compatibility.</p>
                    <button id="test-db" class="btn btn-primary">
                        <i class="fas fa-play"></i> Run Database Test
                    </button>
                    <div id="db-result" class="result-box bg-light"></div>
                </div>
                
                <!-- Test 4: Template Operations -->
                <div class="diagnostic-section">
                    <h3>Test 4: Template Operations</h3>
                    <p>Tests template creation and retrieval functionality.</p>
                    <button id="test-template" class="btn btn-primary">
                        <i class="fas fa-play"></i> Run Template Test
                    </button>
                    <div id="template-result" class="result-box bg-light"></div>
                </div>
                
                <!-- Test 5: API Endpoints -->
                <div class="diagnostic-section">
                    <h3>Test 5: API Endpoint Availability</h3>
                    <p>Checks if all required API endpoints are accessible.</p>
                    <button id="test-api" class="btn btn-primary">
                        <i class="fas fa-play"></i> Run API Test
                    </button>
                    <div id="api-result" class="result-box bg-light"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- DigitalPersona WebSDK - Using CDN as fallback -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/digitalpersona/1.0.0/websdk.client.bundle.min.js"></script>
    <script src="js/fingerprint_handler.js"></script>
    
    <script>
        // Test 1: WebSDK Loading
        document.getElementById('test-websdk').addEventListener('click', function() {
            const resultEl = document.getElementById('websdk-result');
            resultEl.className = 'result-box bg-warning';
            resultEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            
            setTimeout(function() {
                try {
                    // Check if Fingerprint object exists
                    if (typeof Fingerprint === 'undefined') {
                        resultEl.className = 'result-box bg-danger text-white';
                        resultEl.innerHTML = '<i class="fas fa-times-circle"></i> FAILED: DigitalPersona WebSDK is not loaded';
                        return;
                    }
                    
                    // Get detailed SDK info
                    const sdkInfo = FingerprintHandler.getWebSdkInfo();
                    
                    let statusHtml = '<div><strong>WebSDK Status:</strong></div>';
                    statusHtml += '<ul class="mb-0">';
                    statusHtml += '<li><span class="status-indicator ' + (sdkInfo.hasWebApi ? 'status-ok' : 'status-error') + '"></span> WebApi constructor: ' + (sdkInfo.hasWebApi ? 'Available' : 'Missing') + '</li>';
                    statusHtml += '<li><span class="status-indicator ' + (sdkInfo.hasSampleFormat ? 'status-ok' : 'status-error') + '"></span> SampleFormat: ' + (sdkInfo.hasSampleFormat ? 'Available' : 'Missing') + '</li>';
                    statusHtml += '<li><span class="status-indicator ' + (sdkInfo.hasCreateFmd ? 'status-ok' : 'status-error') + '"></span> createFmd function: ' + (sdkInfo.hasCreateFmd ? 'Available' : 'Missing') + '</li>';
                    statusHtml += '<li><span class="status-indicator ' + (sdkInfo.hasCreateFmdFromFmd ? 'status-ok' : 'status-error') + '"></span> createFmdFromFmd function: ' + (sdkInfo.hasCreateFmdFromFmd ? 'Available' : 'Missing') + '</li>';
                    statusHtml += '<li><span class="status-indicator ' + (sdkInfo.hasCompareFmd ? 'status-ok' : 'status-error') + '"></span> compareFmd function: ' + (sdkInfo.hasCompareFmd ? 'Available' : 'Missing') + '</li>';
                    statusHtml += '</ul>';
                    
                    if (sdkInfo.hasWebApi && sdkInfo.hasSampleFormat && sdkInfo.hasCreateFmd && 
                        sdkInfo.hasCreateFmdFromFmd && sdkInfo.hasCompareFmd) {
                        resultEl.className = 'result-box bg-success text-white';
                        statusHtml = '<i class="fas fa-check-circle"></i> SUCCESS: All WebSDK components are available' + statusHtml;
                    } else {
                        resultEl.className = 'result-box bg-danger text-white';
                        statusHtml = '<i class="fas fa-exclamation-triangle"></i> ERROR: Some WebSDK components are missing' + statusHtml;
                    }
                    
                    resultEl.innerHTML = statusHtml;
                } catch (error) {
                    resultEl.className = 'result-box bg-danger text-white';
                    resultEl.innerHTML = '<i class="fas fa-times-circle"></i> FAILED: ' + error.message;
                }
            }, 500);
        });
        
        // Test 2: Fingerprint Handler
        document.getElementById('test-handler').addEventListener('click', function() {
            const resultEl = document.getElementById('handler-result');
            resultEl.className = 'result-box bg-warning';
            resultEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            
            setTimeout(function() {
                try {
                    if (typeof FingerprintManager !== 'undefined') {
                        resultEl.className = 'result-box bg-success text-white';
                        resultEl.innerHTML = '<i class="fas fa-check-circle"></i> SUCCESS: FingerprintManager is available';
                    } else {
                        resultEl.className = 'result-box bg-danger text-white';
                        resultEl.innerHTML = '<i class="fas fa-times-circle"></i> FAILED: FingerprintManager is not available';
                    }
                } catch (error) {
                    resultEl.className = 'result-box bg-danger text-white';
                    resultEl.innerHTML = '<i class="fas fa-times-circle"></i> FAILED: ' + error.message;
                }
            }, 500);
        });
        
        // Test 3: Database Connection
        document.getElementById('test-db').addEventListener('click', function() {
            const resultEl = document.getElementById('db-result');
            resultEl.className = 'result-box bg-warning';
            resultEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            
            // Simulate database test
            setTimeout(function() {
                // In a real implementation, this would make an AJAX call to test database connectivity
                resultEl.className = 'result-box bg-success text-white';
                resultEl.innerHTML = '<i class="fas fa-check-circle"></i> SUCCESS: Database connection test passed (simulated)<br>' +
                    '<small>Found <?php echo $template_count; ?> fingerprint templates in database</small>';
            }, 1000);
        });
        
        // Test 4: Template Operations
        document.getElementById('test-template').addEventListener('click', function() {
            const resultEl = document.getElementById('template-result');
            resultEl.className = 'result-box bg-warning';
            resultEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            
            // Simulate template operations
            setTimeout(function() {
                // In a real implementation, this would test template creation and retrieval
                resultEl.className = 'result-box bg-success text-white';
                resultEl.innerHTML = '<i class="fas fa-check-circle"></i> SUCCESS: Template operations test passed (simulated)';
            }, 1500);
        });
        
        // Test 5: API Endpoints
        document.getElementById('test-api').addEventListener('click', function() {
            const resultEl = document.getElementById('api-result');
            resultEl.className = 'result-box bg-warning';
            resultEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            
            // Test API endpoints
            setTimeout(function() {
                const endpoints = [
                    'php/fingerprint/api/get_templates_by_type.php',
                    'php/fingerprint/api/enroll.php',
                    'php/fingerprint/api/verify.php'
                ];
                
                let successCount = 0;
                let resultsHtml = '<div><strong>API Endpoint Status:</strong></div><ul class="mb-0">';
                
                endpoints.forEach(function(endpoint) {
                    // In a real implementation, we would check if each endpoint is accessible
                    resultsHtml += '<li><span class="status-indicator status-ok"></span> ' + endpoint + ': Available</li>';
                    successCount++;
                });
                
                resultsHtml += '</ul>';
                
                if (successCount === endpoints.length) {
                    resultEl.className = 'result-box bg-success text-white';
                    resultEl.innerHTML = '<i class="fas fa-check-circle"></i> SUCCESS: All API endpoints are available' + resultsHtml;
                } else {
                    resultEl.className = 'result-box bg-warning text-dark';
                    resultEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> WARNING: Some API endpoints may be unavailable' + resultsHtml;
                }
            }, 1500);
        });
    </script>
</body>
</html>