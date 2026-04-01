<div style="background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
    <h2><i class="fas fa-file-export"></i> Export Reports</h2>
    <p>Export reports in various formats (PDF, Excel, CSV).</p>
    
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-top:30px;">
        <!-- Student Attendance Export -->
        <div style="border:1px solid #dee2e6;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;color:#2c3e50;"><i class="fas fa-user-graduate"></i> Student Attendance</h3>
            <p>Export student attendance records for a specific date range.</p>
            
            <form method="GET" style="margin-top:15px;">
                <input type="hidden" name="page" value="export_reports">
                <input type="hidden" name="export_type" value="attendance">
                
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;">From Date</label>
                    <input type="date" name="date_from" value="<?php echo date('Y-m-01'); ?>" 
                           style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                </div>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;">To Date</label>
                    <input type="date" name="date_to" value="<?php echo date('Y-m-d'); ?>" 
                           style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                </div>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;">Export Format</label>
                    <select name="format" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                        <option value="excel">Excel (.xls)</option>
                        <option value="pdf">PDF (.pdf)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                </div>
                
                <button type="submit" style="width:100%;padding:10px;background:#27ae60;color:white;border:none;border-radius:4px;cursor:pointer;">
                    <i class="fas fa-file-export"></i> Export Student Attendance
                </button>
            </form>
        </div>
        
        <!-- Tutor Matching Export -->
        <div style="border:1px solid #dee2e6;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;color:#2c3e50;"><i class="fas fa-chalkboard-teacher"></i> Tutor Matching</h3>
            <p>Export tutor-student matching records and assignments.</p>
            
            <form method="GET" style="margin-top:15px;">
                <input type="hidden" name="page" value="export_reports">
                <input type="hidden" name="export_type" value="tutor_matching">
                
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;">Export Format</label>
                    <select name="format" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                        <option value="excel">Excel (.xls)</option>
                        <option value="pdf">PDF (.pdf)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                </div>
                
                <button type="submit" style="width:100%;padding:10px;background:#9b59b6;color:white;border:none;border-radius:4px;cursor:pointer;">
                    <i class="fas fa-file-export"></i> Export Tutor Matching
                </button>
            </form>
        </div>
        
        <!-- Staff Attendance Export -->
        <div style="border:1px solid #dee2e6;border-radius:8px;padding:20px;">
            <h3 style="margin-top:0;color:#2c3e50;"><i class="fas fa-user-tie"></i> Staff Attendance</h3>
            <p>Export staff attendance records for a specific date range.</p>
            
            <form method="GET" style="margin-top:15px;">
                <input type="hidden" name="page" value="export_reports">
                <input type="hidden" name="export_type" value="staff_attendance">
                
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;">From Date</label>
                    <input type="date" name="date_from" value="<?php echo date('Y-m-01'); ?>" 
                           style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                </div>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;">To Date</label>
                    <input type="date" name="date_to" value="<?php echo date('Y-m-d'); ?>" 
                           style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                </div>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;">Export Format</label>
                    <select name="format" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                        <option value="excel">Excel (.xls)</option>
                        <option value="pdf">PDF (.pdf)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                </div>
                
                <button type="submit" style="width:100%;padding:10px;background:#e67e22;color:white;border:none;border-radius:4px;cursor:pointer;">
                    <i class="fas fa-file-export"></i> Export Staff Attendance
                </button>
            </form>
        </div>
    </div>
    
    <div style="margin-top:30px;padding:20px;background:#e3f2fd;border-radius:8px;">
        <h4><i class="fas fa-info-circle"></i> Export Information</h4>
        <p>Select the type of report you want to export, specify the date range (if applicable), and choose your preferred format.</p>
        <ul>
            <li><strong>Excel</strong> - Best for data analysis and manipulation</li>
            <li><strong>PDF</strong> - Best for printing and sharing</li>
            <li><strong>CSV</strong> - Best for importing into other systems</li>
        </ul>
    </div>
</div>

<?php
// Handle export requests
if (isset($_GET['export_type'])) {
    $export_type = $_GET['export_type'];
    $format = $_GET['format'] ?? 'excel';
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    
    // Redirect to generate_reports with export parameters
    $redirect_url = "?page=generate_reports&generate=1&report_type={$export_type}&format={$format}";
    if (isset($_GET['date_from'])) {
        $redirect_url .= "&date_from={$date_from}";
    }
    if (isset($_GET['date_to'])) {
        $redirect_url .= "&date_to={$date_to}";
    }
    
    echo "<script>window.location.href = '{$redirect_url}';</script>";
    exit;
}
?>