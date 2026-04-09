<div style="background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
	<h2><i class="fas fa-fingerprint"></i> Staff Fingerprint Scan</h2>
	<p>Scan your fingerprint to mark attendance.</p>

	<div id="fp-status" style="background:#fff3cd;padding:12px;border-radius:4px;margin:15px 0;color:#856404;border:1px solid #ffeeba;">Scanner ready. Click the button to scan.</div>

	<button id="btn-scan-staff" class="btn" style="background:#3498db;color:#fff;padding:12px 22px;border:none;border-radius:4px;cursor:pointer;">
		<i class="fas fa-fingerprint"></i> Scan & Verify
	</button>

	<div id="fp-result-staff" style="margin-top:16px;"></div>
</div>
<script src="../js/websdk.client.bundle.min.js"></script>
<script src="../js/fingerprint_handler.js"></script>
<script>
// If local SDK files failed to load, show a clear message
if (typeof window.Fingerprint === 'undefined' || typeof window.Fingerprint.verify !== 'function') {
    document.getElementById('fp-status').innerHTML = 'Error: Fingerprint SDK not loaded. Please contact administrator.';
    document.getElementById('btn-scan-staff').disabled = true;
}

(function(){
	var btn = document.getElementById('btn-scan-staff');
	var statusEl = document.getElementById('fp-status');
	var resultEl = document.getElementById('fp-result-staff');
	btn.addEventListener('click', async function(){
		btn.disabled = true;
		statusEl.textContent = 'Scanning...';
		statusEl.style.background = '#d1ecf1';
		statusEl.style.color = '#0c5460';
		try{
			const res = await Fingerprint.verify('staff');
			if (res && res.ok && res.match){
				resultEl.innerHTML = '<div style="background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:12px;border-radius:4px;">Match found. Staff ID: '+res.user_id+' (Score: '+res.score+'). Attendance recorded.</div>';
			} else {
				resultEl.innerHTML = '<div style="background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:12px;border-radius:4px;">No match. Please try again.</div>';
			}
		}catch(e){
			console.error('Fingerprint verification error:', e);
			resultEl.innerHTML = '<div style="background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:12px;border-radius:4px;">Error: '+(e && e.message ? e.message : 'Scan failed')+'</div>';
		}
		finally{
			btn.disabled = false;
			statusEl.textContent = 'Scanner ready. Click the button to scan.';
			statusEl.style.background = '#fff3cd';
			statusEl.style.color = '#856404';
		}
	});
})();
</script>