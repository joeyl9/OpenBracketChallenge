<?php
include("database.php");
include("functions.php");
check_admin_auth('limited'); // Any admin can manage broadcasts

// Handle Actions — all POST with CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    if (isset($_POST['add_broadcast'])) {
        $msg = $_POST['message'];
        $allowed_types = ['info', 'success', 'warning', 'error'];
        $type = in_array($_POST['type'], $allowed_types) ? $_POST['type'] : 'info';
        $stmt = $db->prepare("INSERT INTO broadcasts (message, type, active) VALUES (?, ?, 1)");
        $stmt->execute([$msg, $type]);
    }

    if (isset($_POST['toggle_id'])) {
        $stmt = $db->prepare("UPDATE broadcasts SET active = NOT active WHERE id = ?");
        $stmt->execute([(int)$_POST['toggle_id']]);
    }

    if (isset($_POST['delete_id'])) {
        $stmt = $db->prepare("DELETE FROM broadcasts WHERE id = ?");
        $stmt->execute([(int)$_POST['delete_id']]);
    }

    header("Location: broadcasts.php");
    exit;
}

include("header.php");
?>

<div id="main">
    <div class="full">
        <h2>📢 System Broadcasts</h2>

        <!-- Add Form -->
        <div class="dashboard-card" style="padding:20px; border:1px solid var(--border-color); margin-bottom:30px;">
            <form method="post" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; width:100%;">
                <?php csrf_field(); ?>
                <input type="text" name="message" placeholder="Broadcast Message (e.g. 'Second Chance is LIVE!')" required style="flex-grow:1; width:100%; min-width:300px; padding:12px; border-radius:4px; border:1px solid var(--border-color); background:rgba(255,255,255,0.05); color:var(--text-light); font-size:1rem;">
                <select name="type" style="padding:12px; border-radius:4px; border:1px solid var(--border-color); background:rgba(255,255,255,0.05); color:var(--text-light); font-size:1rem;">
                    <option value="info" style="background:#1e293b;">ℹ️ Info</option>
                    <option value="success" style="background:#1e293b;">✅ Success</option>
                    <option value="warning" style="background:#1e293b;">⚠️ Warning</option>
                    <option value="error" style="background:#1e293b;">🚨 Urgent</option>
                </select>
                <button type="submit" name="add_broadcast" style="padding:12px 24px; background:var(--accent-orange); color:var(--accent-text); border:none; border-radius:4px; cursor:pointer; font-weight:bold; font-size:1rem; white-space:nowrap; transition:all 0.2s;" onmouseover="this.style.background='var(--accent-orange-hover)'" onmouseout="this.style.background='var(--accent-orange)'">Send Alert</button>
            </form>
        </div>

        <!-- List -->
        <div class="table-container">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid var(--border-color); text-align:left;">
                    <th style="padding:10px;">Status</th>
                    <th style="padding:10px;">Message</th>
                    <th style="padding:10px;">Type</th>
                    <th style="padding:10px;">Time</th>
                    <th style="padding:10px; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rows = $db->query("SELECT * FROM broadcasts ORDER BY created_at DESC")->fetchAll();
                foreach($rows as $r):
                    $status = $r['active'] ? '<span style="color:#22c55e">● Active</span>' : '<span style="color:#64748b">● Inactive</span>';
                    $bg = $r['active'] ? 'background:rgba(255,255,255,0.05)' : '';
                ?>
                    <tr style="border-bottom:1px solid #334155; <?php echo $bg; ?>">
                        <td style="padding:15px 10px;"><?php echo $status; ?></td>
                        <td style="padding:15px 10px;"><?php echo h($r['message']); ?></td>
                        <td style="padding:15px 10px;"><?php echo h($r['type']); ?></td>
                        <td style="padding:15px 10px; color:var(--text-muted); white-space:nowrap;"><?php echo h($r['created_at']); ?></td>
                        <td style="padding:15px 10px; text-align:right; white-space:nowrap;">
                            <form method="post" style="display:inline;">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="toggle_id" value="<?php echo (int)$r['id']; ?>">
                                <button type="submit" style="background:none; border:none; color:#3b82f6; cursor:pointer; font-size:inherit; padding:0; margin-right:10px;">Toggle</button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this broadcast?');">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="delete_id" value="<?php echo (int)$r['id']; ?>">
                                <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:inherit; padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
        </div>
    </div>
</div>
<?php include('footer.php'); ?>
</body>
</html>


