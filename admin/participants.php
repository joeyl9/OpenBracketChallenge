<?php
include("database.php");
include("functions.php");
validatecookie();
check_admin_auth('super');
include("header.php");

$msg = "";
$error = "";

// HANDLE ACTIONS
if(isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // DISABLE / ENABLE
    if($_GET['action'] == 'toggle') {
        $stmt = $db->prepare("SELECT disabled FROM brackets WHERE id = ?");
        $stmt->execute([$id]);
        $curr = $stmt->fetchColumn();
        $new = $curr ? 0 : 1;
        
        $upd = $db->prepare("UPDATE brackets SET disabled = ? WHERE id = ?");
        $upd->execute([$new, $id]);
        $msg = $new ? "User login disabled." : "User login enabled.";
    }
    
    // DELETE
    if($_GET['action'] == 'delete') {
        // Warning: This removes historic data
        // 1. Get info for log
        $stmt = $db->prepare("SELECT email, name FROM brackets WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user) {
            // 2. Delete Comments
            $db->prepare("DELETE FROM comments WHERE bracket = ?")->execute([$id]);
            
            // 3. Delete Bracket
            $db->prepare("DELETE FROM brackets WHERE id = ?")->execute([$id]);
            
            log_admin_action('DELETE_PARTICIPANT', "Deleted participant: {$user['name']} ({$user['email']})");
            $msg = "User and all historical data deleted.";
        } else {
            $error = "User not found.";
        }
    }
}

// FETCH USERS
$users = $db->query("SELECT id, person, name, email, paid, disabled, type FROM brackets ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<div id="main">
    <div class="full">
        <!-- Tab Navigation -->
        <div style="display:flex; border-bottom:1px solid rgba(255,255,255,0.1); margin-bottom:20px;">
            <a href="users.php" style="padding:10px 20px; text-decoration:none; color:var(--text-muted); border-bottom:2px solid transparent;">Administrators</a>
            <a href="participants.php" style="padding:10px 20px; text-decoration:none; border-bottom:2px solid var(--accent-orange); color:var(--text-light); font-weight:bold;">Participants (Brackets)</a>
        </div>

        <h2>Participant Management</h2>
        
        <?php if($msg) echo "<div class='success'>$msg</div>"; ?>
        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        
        <div class="dashboard-card" style="align-items: flex-start; text-align: left;">
            <div class="table-container">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(255,255,255,0.1);">
                            <th style="padding:15px; text-align:left; background:transparent !important;">ID</th>
                            <th style="padding:15px; text-align:left; background:transparent !important;">Name</th>
                            <th style="padding:15px; text-align:left; background:transparent !important;">Bracket</th>
                            <th style="padding:15px; text-align:left; background:transparent !important;">Email</th>
                            <th style="padding:15px; text-align:center; background:transparent !important;">Type</th>
                            <th style="padding:15px; text-align:center; background:transparent !important;">Status</th>
                            <th style="padding:15px; text-align:right; background:transparent !important;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                            <td style="padding:15px; color:#64748b;">#<?php echo $u['id']; ?></td>
                            <td style="padding:15px; font-weight:bold;"><?php echo htmlspecialchars($u['person']); ?></td>
                            <td style="padding:15px; color:var(--text-light);"><?php echo htmlspecialchars($u['name']); ?></td>
                            <td style="padding:15px; color:var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td style="padding:15px; text-align:center;">
                                <?php echo ($u['type'] == 'sweet16') ? '<span class="badge" style="background:#8b5cf6;">Sweet 16</span>' : '<span class="badge" style="background:#3b82f6;">Main</span>'; ?>
                            </td>
                            <td style="padding:15px; text-align:center;">
                                <?php if($u['disabled']): ?>
                                    <span style="color:#ef4444; font-weight:bold;"><i class="fa-solid fa-ban"></i> Disabled</span>
                                <?php else: ?>
                                    <span style="color:#22c55e;">Active</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:15px; text-align:right;">
                                <a href="participants.php?action=toggle&id=<?php echo $u['id']; ?>" class="btn-sm" style="background:<?php echo $u['disabled'] ? '#22c55e' : '#f59e0b'; ?>; color:white; padding:5px 10px; border-radius:4px; text-decoration:none; margin-right:5px;">
                                    <?php echo $u['disabled'] ? '<i class="fa-solid fa-check"></i> Enable' : '<i class="fa-solid fa-ban"></i> Disable'; ?>
                                </a>
                                <a href="#" onclick="showConfirm('Delete Participant?', 'WARNING: This will permanently delete this user and ALL historical data.<br>Brackets, scores, and comments will be lost.<br><br>This cannot be undone.', function(){ window.location.href='participants.php?action=delete&id=<?php echo $u['id']; ?>'; }); return false;" class="btn-sm" style="background:#ef4444; color:white; padding:5px 10px; border-radius:4px; text-decoration:none;">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>


