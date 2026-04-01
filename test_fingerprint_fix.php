<?php
session_start();
include "db_conn.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fingerprint Test - Fix Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-fingerprint"></i> Fingerprint Test</h3>
                    </div>
                    <div class="card-body">
                        <h4>DigitalPersona WebSDK Test</h4>
                        <p>This page tests if the DigitalPersona WebSDK is properly loaded.</p>
                        
                        <div id="sdk-status" class="alert alert-info">
                            Checking DigitalPersona WebSDK status...
                        </div>
                        
                        <div id="test-results" class="mt-4"></div>
                        
                        <button id="test-btn" class="btn btn-primary mt-3" style="display:none;">
                            <i class="fas fa-vial"></i> Test Fingerprint Functionality
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include the required scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DigitalPersona WebSDK -->
    <script src="js/websdk.client.bundle.min.js"></script>
    <script src="js/fingerprint_handler.js"></script>
    <script>
    // Fallback to CDN if local file is not available
    if (typeof Fingerprint === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/digitalpersona/1.0.0/websdk.client.bundle.min.js';
        script.onload = function() {
            console.log('Loaded WebSDK from CDN');
        };
        script.onerror = function() {
            console.error('Failed to load WebSDK from CDN');
            // Show error message
            const statusEl = document.getElementById('sdk-status');
            if (statusEl) {
                statusEl.className = 'alert alert-danger';
                statusEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> DigitalPersona WebSDK is NOT loaded. Failed to load from CDN.';
            }
            const testBtn = document.getElementById('test-btn');
            if (testBtn) {
                testBtn.style.display = 'none';
            }
        };
        document.head.appendChild(script);
    }
    </script>
    
    <script>
    // Check if DigitalPersona SDK is loaded
    document.addEventListener('DOMContentLoaded', function() {
        const statusEl = document.getElementById('sdk-status');
        const testBtn = document.getElementById('test-btn');
        const resultsEl = document.getElementById('test-results');
        
        if (typeof Fingerprint !== 'undefined') {
            statusEl.className = 'alert alert-success';
            statusEl.innerHTML = '<i class="fas fa-check-circle"></i> DigitalPersona WebSDK is loaded successfully!';
            testBtn.style.display = 'inline-block';
        } else {
            statusEl.className = 'alert alert-danger';
            statusEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> DigitalPersona WebSDK is NOT loaded. Please check the file path.';
        }
        
        // Test button functionality
        testBtn.addEventListener('click', async function() {
            testBtn.disabled = true;
            testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            resultsEl.innerHTML = '<div class="alert alert-info">Initializing fingerprint manager...</div>';
            
            try {
                // Test initialization
                const initResult = await FingerprintManager.initialize();
                resultsEl.innerHTML += '<div class="alert alert-' + (initResult.success ? 'success' : 'danger') + '">' +
                    '<strong>Initialization:</strong> ' + (initResult.success ? 'Success' : 'Failed') + 
                    (initResult.error ? ' - ' + initResult.error : '') + '</div>';
                
                if (initResult.success) {
                    resultsEl.innerHTML += '<div class="alert alert-success">' +
                        '<strong>Device:</strong> ' + initResult.device + '</div>';
                }
            } catch (error) {
                resultsEl.innerHTML += '<div class="alert alert-danger">' +
                    '<strong>Error:</strong> ' + error.message + '</div>';
            } finally {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="fas fa-vial"></i> Test Fingerprint Functionality';
            }
        });
    });
    </script>
</body>
</html>