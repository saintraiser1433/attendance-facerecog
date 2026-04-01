<?php

require_once __DIR__ . '/../includes/sms_schema.php';
require_once __DIR__ . '/../includes/ph_phone.php';

sms_ensure_schema($conn);

$save_message = '';
$save_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sms_settings'])) {
    $gateway = trim($_POST['gateway_url'] ?? '');
    $user = trim($_POST['api_username'] ?? '');
    $pass = $_POST['api_password'] ?? '';

    if ($gateway === '') {
        $gateway = 'https://api.sms-gate.app/3rdparty/v1';
    }

    if ($pass === '') {
        $stmt = mysqli_prepare($conn, 'UPDATE sms_settings SET gateway_url = ?, api_username = ? WHERE id = 1');
        mysqli_stmt_bind_param($stmt, 'ss', $gateway, $user);
    } else {
        $stmt = mysqli_prepare($conn, 'UPDATE sms_settings SET gateway_url = ?, api_username = ?, api_password = ? WHERE id = 1');
        mysqli_stmt_bind_param($stmt, 'sss', $gateway, $user, $pass);
    }
    if (mysqli_stmt_execute($stmt)) {
        $save_message = 'SMS settings saved.';
    } else {
        $save_error = 'Could not save settings.';
    }
    mysqli_stmt_close($stmt);
}

$settings_row_res = mysqli_query($conn, 'SELECT gateway_url, api_username FROM sms_settings WHERE id = 1 LIMIT 1');
$row = $settings_row_res ? mysqli_fetch_assoc($settings_row_res) : null;
$gateway_url = $row['gateway_url'] ?? 'https://api.sms-gate.app/3rdparty/v1';
$api_username = $row['api_username'] ?? '';
?>

<div style="background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);max-width:720px;">
    <h2 style="margin-top:0;"><i class="fas fa-sms"></i> SMS Gateway (Android SMS Gateway)</h2>
    <p style="color:#555;">Configure Basic Auth credentials from the SMS Gateway app (cloud or private server). Default API base matches
        <a href="https://github.com/capcom6/android-sms-gateway" target="_blank" rel="noopener">android-sms-gateway</a> cloud.</p>

    <?php if ($save_message): ?>
        <div style="padding:12px;background:#d4edda;color:#155724;border-radius:4px;margin-bottom:16px;"><?php echo htmlspecialchars($save_message); ?></div>
    <?php endif; ?>
    <?php if ($save_error): ?>
        <div style="padding:12px;background:#f8d7da;color:#721c24;border-radius:4px;margin-bottom:16px;"><?php echo htmlspecialchars($save_error); ?></div>
    <?php endif; ?>

    <form method="post" action="" style="display:grid;gap:16px;">
        <div>
            <label for="gateway_url" style="display:block;font-weight:600;margin-bottom:6px;">Gateway base URL</label>
            <input type="url" class="form-control" name="gateway_url" id="gateway_url" style="width:100%;padding:10px;box-sizing:border-box;"
                   value="<?php echo htmlspecialchars($gateway_url); ?>"
                   placeholder="https://api.sms-gate.app/3rdparty/v1">
            <small style="color:#666;">Trailing slash optional; <code>/message</code> is appended by the client.</small>
        </div>
        <div>
            <label for="api_username" style="display:block;font-weight:600;margin-bottom:6px;">API username</label>
            <input type="text" name="api_username" id="api_username" style="width:100%;padding:10px;box-sizing:border-box;"
                   value="<?php echo htmlspecialchars($api_username); ?>" autocomplete="username">
        </div>
        <div>
            <label for="api_password" style="display:block;font-weight:600;margin-bottom:6px;">API password</label>
            <input type="password" name="api_password" id="api_password" style="width:100%;padding:10px;box-sizing:border-box;"
                   placeholder="Leave blank to keep current password" autocomplete="new-password">
        </div>
        <div>
            <button type="submit" name="save_sms_settings" value="1" style="padding:12px 24px;background:#3498db;color:#fff;border:none;border-radius:4px;cursor:pointer;font-weight:600;">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
    </form>

    <hr style="margin:28px 0;border:none;border-top:1px solid #eee;">

    <h3><i class="fas fa-paper-plane"></i> Test SMS</h3>
    <p style="color:#666;font-size:14px;">Philippine mobile numbers are normalized to <strong>+639</strong>… automatically.</p>
    <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label for="test_phone" style="display:block;font-weight:600;margin-bottom:6px;">Test number</label>
            <input type="text" id="test_phone" placeholder="9123456789 or +639123456789" style="width:100%;padding:10px;box-sizing:border-box;">
        </div>
        <button type="button" id="btn_test_sms" style="padding:12px 20px;background:#27ae60;color:#fff;border:none;border-radius:4px;cursor:pointer;font-weight:600;">
            Send test
        </button>
    </div>
    <pre id="test_sms_result" style="margin-top:12px;padding:12px;background:#f8f9fa;border-radius:4px;display:none;white-space:pre-wrap;font-size:13px;"></pre>
</div>

<script>
(function() {
    var btn = document.getElementById('btn_test_sms');
    var phone = document.getElementById('test_phone');
    var out = document.getElementById('test_sms_result');
    btn.addEventListener('click', function() {
        out.style.display = 'block';
        out.textContent = 'Sending…';
        var fd = new FormData();
        fd.append('phone', phone.value);
        var testUrl = new URL('../ajax/sms_test.php', window.location.href).href;
        fetch(testUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) {
                return r.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (ignore) {
                        throw new Error('Server did not return JSON (status ' + r.status + '). First bytes: ' + text.substring(0, 300));
                    }
                });
            })
            .then(function(data) {
                out.textContent = JSON.stringify(data, null, 2);
            })
            .catch(function(e) {
                out.textContent = String(e.message || e);
            });
    });
})();
</script>
