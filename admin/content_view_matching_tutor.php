<?php
require_once __DIR__ . '/../includes/tutor_reviews_schema.php';
tutor_reviews_ensure_schema($conn);

// Auto-complete active matchings when end date is past
mysqli_query($conn, "UPDATE tutor_student_matching 
                     SET status = 'Completed' 
                     WHERE status = 'Active' 
                       AND end_date IS NOT NULL 
                       AND end_date < CURDATE()");

$flash_ok = '';
$flash_err = '';

// Save tutor review for completed matching
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_review'])) {
    $matching_id = (int) ($_POST['matching_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if ($matching_id <= 0 || $rating < 1 || $rating > 10 || $comment === '') {
        $flash_err = 'Please provide a comment and a rating from 1 to 10.';
    } else {
        // Ensure this matching is completed (or end_date already past)
        $chk = mysqli_prepare($conn, "SELECT tutor_id, student_id, status FROM tutor_student_matching WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($chk, "i", $matching_id);
        mysqli_stmt_execute($chk);
        $chkRes = mysqli_stmt_get_result($chk);
        $mrow = $chkRes ? mysqli_fetch_assoc($chkRes) : null;
        mysqli_stmt_close($chk);

        if (!$mrow) {
            $flash_err = 'Matching not found.';
        } elseif ($mrow['status'] !== 'Completed') {
            $flash_err = 'You can only review a completed matching.';
        } else {
            // Insert review (unique per matching)
            $ins = mysqli_prepare($conn, "INSERT INTO tutor_reviews (matching_id, tutor_id, student_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($ins, "iiiis", $matching_id, $mrow['tutor_id'], $mrow['student_id'], $rating, $comment);
            if (mysqli_stmt_execute($ins)) {
                $flash_ok = 'Review saved.';
            } else {
                $flash_err = 'Could not save review (maybe already reviewed).';
            }
            mysqli_stmt_close($ins);
        }
    }
}

// Fetch tutor-student matching data
$sql = "SELECT tsm.*, 
        CONCAT(t.first_name, ' ', t.last_name) as tutor_name,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        t.tutor_id, s.student_id,
        tr.id AS review_id,
        tr.rating AS review_rating,
        tr.comment AS review_comment
        FROM tutor_student_matching tsm
        LEFT JOIN tutors t ON tsm.tutor_id = t.id
        LEFT JOIN students s ON tsm.student_id = s.id
        LEFT JOIN tutor_reviews tr ON tr.matching_id = tsm.id
        ORDER BY tsm.created_at DESC";
$result = mysqli_query($conn, $sql);

// Preload all tutor reviews (for carousel in "View Review" modal)
$all_reviews_map = [];
$all_reviews_sql = "SELECT tutor_id, rating, comment, created_at FROM tutor_reviews ORDER BY created_at DESC";
$all_reviews_res = mysqli_query($conn, $all_reviews_sql);
if ($all_reviews_res) {
    while ($rv = mysqli_fetch_assoc($all_reviews_res)) {
        $tid = (int) $rv['tutor_id'];
        if (!isset($all_reviews_map[$tid])) {
            $all_reviews_map[$tid] = [];
        }
        $all_reviews_map[$tid][] = [
            'rating' => (int) $rv['rating'],
            'comment' => (string) ($rv['comment'] ?? ''),
            'created_at' => (string) ($rv['created_at'] ?? ''),
        ];
    }
}
?>

<style>
    .card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    .card-title {
        font-size: 1.5em;
        font-weight: bold;
        color: #2c3e50;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    th {
        background: #34495e;
        color: #fff;
        font-weight: bold;
    }
    tr:hover {
        background: #f5f6fa;
    }
    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.85em;
        font-weight: bold;
    }
    .badge-active {
        background: #d4edda;
        color: #155724;
    }
    .badge-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    .badge-completed {
        background: #d1ecf1;
        color: #0c5460;
    }
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chalkboard-teacher"></i> Tutor-Student Matching</h2>
    </div>

    <?php if ($flash_ok): ?>
        <div style="padding:12px;background:#d4edda;color:#155724;border-radius:6px;margin:0 0 14px 0;">
            <?php echo htmlspecialchars($flash_ok); ?>
        </div>
    <?php endif; ?>
    <?php if ($flash_err): ?>
        <div style="padding:12px;background:#f8d7da;color:#721c24;border-radius:6px;margin:0 0 14px 0;">
            <?php echo htmlspecialchars($flash_err); ?>
        </div>
    <?php endif; ?>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Tutor</th>
                    <th>Student</th>
                    <th>Subject</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $status_class = '';
                        switch($row['status']) {
                            case 'Active':
                                $status_class = 'badge-active';
                                break;
                            case 'Inactive':
                                $status_class = 'badge-inactive';
                                break;
                            case 'Completed':
                                $status_class = 'badge-completed';
                                break;
                        }
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['tutor_name'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['student_name'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['subject']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['start_date']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['end_date'] ?? 'Ongoing') . '</td>';
                        echo '<td><span class="badge ' . $status_class . '">' . htmlspecialchars($row['status']) . '</span></td>';
                        echo '<td>';
                        if ($row['status'] === 'Completed') {
                            if (!empty($row['review_id'])) {
                                echo '<button type="button" class="btn btn-sm" style="background:#3498db;color:#fff;border:none;border-radius:4px;cursor:pointer;" ';
                                echo 'onclick="openReviewViewTutor(' . (int) $row['tutor_id'] . ')">';
                                echo '<i class="fas fa-eye"></i> Review</button>';
                            } else {
                                echo '<button type="button" class="btn btn-sm" style="background:#27ae60;color:#fff;border:none;border-radius:4px;cursor:pointer;" ';
                                echo 'onclick="openReviewModal(' . (int) $row['id'] . ')"><i class="fas fa-star"></i> Review</button>';
                            }
                        } else {
                            echo '<span style="color:#7f8c8d;font-size:12px;">—</span>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="7" style="text-align:center;">No tutor-student matching records found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:10px;max-width:560px;width:100%;padding:18px 18px 14px 18px;box-shadow:0 10px 30px rgba(0,0,0,0.25);">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;"><i class="fas fa-star"></i> Tutor review</h3>
            <button type="button" id="reviewClose" style="border:none;background:transparent;font-size:18px;cursor:pointer;">✕</button>
        </div>
        <form method="post" action="" id="reviewForm" style="margin-top:12px;display:grid;gap:12px;">
            <input type="hidden" name="matching_id" id="reviewMatchingId" value="">
            <div>
                <label style="display:block;font-weight:700;margin-bottom:6px;">Rate (1 to 10)</label>
                <select name="rating" id="reviewRating" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
                    <option value="" disabled selected>— select —</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label style="display:block;font-weight:700;margin-bottom:6px;">Comment</label>
                <textarea name="comment" id="reviewComment" required rows="4" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;resize:vertical;" placeholder="Write your feedback..."></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" id="reviewCancel" style="padding:10px 14px;background:#95a5a6;color:#fff;border:none;border-radius:6px;cursor:pointer;">Cancel</button>
                <button type="submit" name="save_review" value="1" style="padding:10px 14px;background:#27ae60;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:700;">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Review View Modal -->
<div id="reviewViewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:10px;max-width:560px;width:100%;padding:18px 18px 14px 18px;box-shadow:0 10px 30px rgba(0,0,0,0.25);">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;"><i class="fas fa-comment"></i> Saved review</h3>
            <button type="button" id="reviewViewClose" style="border:none;background:transparent;font-size:18px;cursor:pointer;">✕</button>
        </div>
        <div style="margin-top:12px;">
            <div style="font-weight:800;color:#2c3e50;margin-bottom:8px;">Reviewer comments</div>
            <div id="reviewCarousel" style="position:relative;min-height:90px;background:#f8f9fa;border:1px solid #eee;border-radius:8px;padding:12px;overflow:hidden;"></div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:14px;">
            <button type="button" id="reviewViewOk" style="padding:10px 14px;background:#3498db;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:700;">OK</button>
        </div>
    </div>
</div>

<script>
function openReviewModal(matchingId) {
    var modal = document.getElementById('reviewModal');
    document.getElementById('reviewMatchingId').value = matchingId;
    document.getElementById('reviewRating').value = '';
    document.getElementById('reviewComment').value = '';
    modal.style.display = 'flex';
}
function openReviewView(matchingId, rating, comment) {
    // Backward-compat wrapper (unused now); keep for safety.
    openReviewViewTutor(matchingId);
}
var tutorReviewsMap = <?php echo json_encode($all_reviews_map, JSON_UNESCAPED_UNICODE); ?>;
var reviewCarouselTimer = null;
function openReviewViewTutor(tutorId) {
    var modal = document.getElementById('reviewViewModal');
    var box = document.getElementById('reviewCarousel');
    var rows = tutorReviewsMap[String(tutorId)] || tutorReviewsMap[tutorId] || [];
    if (reviewCarouselTimer) {
        clearInterval(reviewCarouselTimer);
        reviewCarouselTimer = null;
    }
    if (!rows.length) {
        box.innerHTML = '<div style="color:#7f8c8d;">No comments available.</div>';
        modal.style.display = 'flex';
        return;
    }
    box.innerHTML = rows.map(function(r, i) {
        return '<div class="rv-slide' + (i === 0 ? ' active' : '') + '" style="position:absolute;inset:12px;opacity:' + (i === 0 ? '1' : '0') + ';transition:opacity .5s;">' +
               '<div style="font-weight:700;color:#2c3e50;">' + r.rating + '/10</div>' +
               '<div style="margin-top:6px;white-space:pre-wrap;">' + (r.comment || '') + '</div>' +
               '</div>';
    }).join('');
    if (rows.length > 1) {
        var idx = 0;
        reviewCarouselTimer = setInterval(function() {
            var slides = box.querySelectorAll('.rv-slide');
            if (!slides.length) return;
            slides[idx].style.opacity = '0';
            idx = (idx + 1) % slides.length;
            slides[idx].style.opacity = '1';
        }, 2600);
    }
    modal.style.display = 'flex';
}
(function() {
    function close(id) { document.getElementById(id).style.display = 'none'; }
    document.getElementById('reviewClose').onclick = function() { close('reviewModal'); };
    document.getElementById('reviewCancel').onclick = function() { close('reviewModal'); };
    document.getElementById('reviewModal').onclick = function(e) { if (e.target === this) close('reviewModal'); };

    document.getElementById('reviewViewClose').onclick = function() {
        if (reviewCarouselTimer) { clearInterval(reviewCarouselTimer); reviewCarouselTimer = null; }
        close('reviewViewModal');
    };
    document.getElementById('reviewViewOk').onclick = function() {
        if (reviewCarouselTimer) { clearInterval(reviewCarouselTimer); reviewCarouselTimer = null; }
        close('reviewViewModal');
    };
    document.getElementById('reviewViewModal').onclick = function(e) {
        if (e.target === this) {
            if (reviewCarouselTimer) { clearInterval(reviewCarouselTimer); reviewCarouselTimer = null; }
            close('reviewViewModal');
        }
    };
})();
</script>
