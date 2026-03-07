<?php
include("functions.php");
validatecookie();
check_admin_auth('super'); // Only Super Admins
include("header.php");

$message = "";

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $db->prepare("INSERT INTO admin_users (username, password_hash, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hash, $role]);
            log_admin_action('ADD_USER', "Added admin user: $username ($role)");
            $message = "<div class='success'>User '$username' added successfully.</div>";
        } catch (PDOException $e) {
            $message = "<div class='error'>Error adding user: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='error'>Username and Password required.</div>";
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Prevent self-deletion
    if ($id == $_SESSION['admin_user']['id']) {
        $message = "<div class='error'>You cannot delete yourself!</div>";
    } else {
        // Get username for log
        $stmt = $db->prepare("SELECT username FROM admin_users WHERE id = ?");
        $stmt->execute([$id]);
        $target = $stmt->fetchColumn();

        if ($target) {
            $stmt = $db->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt->execute([$id]);
            log_admin_action('DELETE_USER', "Deleted admin user: $target (ID: $id)");
            $message = "<div class='success'>User '$target' deleted.</div>";
        }
    }
}

// Handle Unified Role Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    verify_csrf_token();

    $target_id = (int)$_POST['target_user_id'];
    $new_role = $_POST['new_role'];
    
    // Strict Allowlist (Exclude 'super' from UI assignment to prevent accidents)
    $allowed_roles = ['player', 'limited', 'pay']; 
    
    if (in_array($new_role, $allowed_roles) && $target_id > 0) {
        // Get old role for logging
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$target_id]);
        $old_role = $stmt->fetchColumn();
        
        if ($old_role !== false) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $target_id]);
            
            log_admin_action('UPDATE_ROLE', "Changed UserID $target_id role from '$old_role' to '$new_role'");
            $message = "<div class='success'>User role updated to <strong>$new_role</strong>.</div>";
        }
    } else {
        $message = "<div class='error'>Invalid role selected or user not found.</div>";
    }
}

// Fetch Legacy Users
$users = $db->query("SELECT * FROM admin_users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Promoted Unified Users
$promoted_users = $db->query("SELECT id, name, email, role FROM users WHERE role IN ('super', 'limited', 'pay') ORDER BY role DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle Search
$search_results = [];
$search_term = "";
if (isset($_GET['search_user']) && !empty($_GET['search_term'])) {
    $search_term = trim($_GET['search_term']);
    $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE name LIKE ? OR email LIKE ? LIMIT 20");
    $stmt->execute(["%$search_term%", "%$search_term%"]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div id="main">
    <div class="full">
        <!-- Tab Navigation -->
        <div style="display:flex; border-bottom:1px solid rgba(255,255,255,0.1); margin-bottom:20px;">
            <a href="users.php" style="padding:10px 20px; text-decoration:none; border-bottom:2px solid var(--accent-orange); color:var(--text-light); font-weight:bold;">Administrators</a>
            <a href="participants.php" style="padding:10px 20px; text-decoration:none; color:var(--text-muted); border-bottom:2px solid transparent;">Participants (Brackets)</a>
        </div>

        <h2>Admin User Management</h2>
        <div style="margin-bottom: 20px;">
            <a href="index.php" class="btn-outline">&larr; Back to Dashboard</a>
        </div>
        <?php echo $message; ?>

        <div class="dashboard-grid">
            <!-- Add User Form -->
            <div class="dashboard-card" style="grid-column: span 1; align-items: flex-start; text-align: left;">
                <h3>Add New Admin</h3>
                <form method="post" action="users.php" style="width: 100%;">
                    <div style="margin-bottom: 10px;">
                        <label>Username:</label><br>
                        <input type="text" name="username" required style="width: 100%; box-sizing:border-box; padding: 10px; border-radius:4px; border:1px solid #444; background:rgba(0,0,0,0.2); color:#fff; margin-top:5px;">
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label>Password:</label><br>
                        <input type="password" name="password" required style="width: 100%; box-sizing:border-box; padding: 10px; border-radius:4px; border:1px solid #444; background:rgba(0,0,0,0.2); color:#fff; margin-top:5px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>Role:</label><br>
                        <select name="role" style="width: 100%; box-sizing:border-box; padding: 10px; border-radius:4px; border:1px solid #444; background:rgba(0,0,0,0.2); color:#fff; margin-top:5px;">
                            <option value="limited">Limited Admin (Standard)</option>
                            <option value="super">Super Admin (Full Access)</option>
                            <option value="pay">Pay Editor (Payments Only)</option>
                        </select>
                    </div>
                    <input type="submit" name="add_user" value="Create User" style="padding: 10px 20px; cursor: pointer; width:100%;">
                </form>
            </div>

            <!-- (Legacy) Existing Users -->
            <div class="dashboard-card" style="grid-column: span 2; align-items: flex-start; text-align: left;">
                <h3>Legacy / Break-Glass Administrators</h3>
                <div style="margin-bottom:15px; color:#fca5a5; font-size:0.9em; background:rgba(255,0,0,0.1); padding:10px; border:1px solid #ef4444; border-radius:4px;">
                    <strong>Note:</strong> These are legacy backup accounts. For day-to-day admin, please promote regular users using the "Unified User Role Management" section below.
                </div>
                <div class="table-container">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,255,255,0.1);">
                                <th style="padding: 15px; text-align: left; background:transparent !important;">Username</th>
                                <th style="padding: 15px; text-align: left; background:transparent !important;">Role</th>
                                <th style="padding: 15px; text-align: center; background:transparent !important;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 15px; font-weight: bold;"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td style="padding: 15px;">
                                    <?php 
                                        switch($u['role']) {
                                            case 'super': echo '<span style="color: #ef4444;">Super Admin</span>'; break;
                                            case 'pay': echo '<span style="color: #22c55e;">Pay Editor</span>'; break;
                                            default: echo 'Limited Admin';
                                        }
                                    ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <?php if ($u['id'] != $_SESSION['admin_user']['id']): ?>
                                        <a href="#" onclick="showConfirm('Delete Admin?', 'Are you sure you want to delete this administrator?', function(){ window.location.href='users.php?delete=<?php echo $u['id']; ?>'; }); return false;" class="btn-sm" style="background:#ef4444; color:white; padding:5px 10px; border-radius:4px; text-decoration:none;">Delete</a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">(You)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Unified User Role Management -->
            <div class="dashboard-card" style="grid-column: span 3; align-items: flex-start; text-align: left; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <h3 style="color:var(--accent-orange);">Unified User Role Management</h3>
                <p style="color:var(--text-muted); margin-bottom: 20px;">Search for any user to assign them an admin role.</p>
                
                <!-- Search Form -->
                <form method="get" action="users.php" style="margin-bottom: 20px; display:flex; gap:10px;">
                    <input type="text" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search by name or email..." style="padding:10px; border-radius:4px; border:1px solid #444; background:rgba(0,0,0,0.2); color:#fff; width:300px;">
                    <input type="submit" name="search_user" value="Search" style="background:var(--primary-blue); border:1px solid var(--accent-orange); color:var(--accent-orange);">
                </form>

                <!-- Search Results -->
                <?php if (!empty($search_results)): ?>
                <div class="table-container" style="margin-bottom: 30px;">
                    <h4>Search Results</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,255,255,0.1);">
                                <th style="padding: 10px; text-align: left; background:transparent !important;">Name</th>
                                <th style="padding: 10px; text-align: left; background:transparent !important;">Email</th>
                                <th style="padding: 10px; text-align: left; background:transparent !important;">Current Role</th>
                                <th style="padding: 10px; text-align: left; background:transparent !important;">Assign Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($search_results as $su): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 10px;"><?php echo htmlspecialchars($su['name']); ?></td>
                                <td style="padding: 10px; color:var(--text-muted);"><?php echo htmlspecialchars($su['email']); ?></td>
                                <td style="padding: 10px;">
                                    <?php 
                                        if($su['role'] === 'super') echo '<span class="badge" style="background:#ef4444; color:white; padding:2px 6px; border-radius:4px;">Super</span>';
                                        elseif($su['role'] === 'limited') echo '<span class="badge" style="background:#3b82f6; color:white; padding:2px 6px; border-radius:4px;">Limited</span>';
                                        elseif($su['role'] === 'pay') echo '<span class="badge" style="background:#22c55e; color:white; padding:2px 6px; border-radius:4px;">Pay</span>';
                                        else echo 'Player';
                                    ?>
                                </td>
                                <td style="padding: 10px;">
                                    <form method="post" action="users.php" style="display:flex; gap:10px; align-items:center;">
                                        <?php if(function_exists('csrf_field')) csrf_field(); ?>
                                        <input type="hidden" name="target_user_id" value="<?php echo $su['id']; ?>">
                                        <select name="new_role" style="padding:5px; border-radius:4px; background:#222; color:#fff; border:1px solid #555;">
                                            <option value="player" <?php if($su['role']=='player') echo 'selected'; ?>>Player (None)</option>
                                            <option value="limited" <?php if($su['role']=='limited') echo 'selected'; ?>>Limited Admin</option>
                                            <option value="pay" <?php if($su['role']=='pay') echo 'selected'; ?>>Pay Editor</option>
                                        </select>
                                        <input type="submit" name="update_role" value="Save" class="btn-sm" style="padding:5px 10px; font-size:0.8em;">
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php elseif (isset($_GET['search_user'])): ?>
                    <p style="color: #fca5a5;">No users found matching "<?php echo htmlspecialchars($search_term); ?>".</p>
                <?php endif; ?>

                <!-- Current Promoted Users -->
                <div class="table-container">
                    <h4>Current Promoted Users</h4>
                    <?php if (empty($promoted_users)): ?>
                        <p style="color:var(--text-muted);">No regular users have been promoted to admin roles.</p>
                    <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,255,255,0.1);">
                                <th style="padding: 10px; text-align: left; background:transparent !important;">Name</th>
                                <th style="padding: 10px; text-align: left; background:transparent !important;">Email</th>
                                <th style="padding: 10px; text-align: left; background:transparent !important;">Role</th>
                                <th style="padding: 10px; text-align: center; background:transparent !important;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($promoted_users as $pu): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 10px;"><?php echo htmlspecialchars($pu['name']); ?></td>
                                <td style="padding: 10px; color:var(--text-muted);"><?php echo htmlspecialchars($pu['email']); ?></td>
                                <td style="padding: 10px;">
                                    <?php 
                                        $role_color = ($pu['role'] === 'super') ? '#ef4444' : ( ($pu['role'] === 'pay') ? '#22c55e' : '#3b82f6' );
                                        echo "<span style='color:$role_color; font-weight:bold;'>" . ucfirst($pu['role']) . "</span>";
                                    ?>
                                </td>
                                <td style="padding: 10px; text-align: center;">
                                    <form method="post" action="users.php" onsubmit="return confirm('Demote this user back to Player?');">
                                        <?php if(function_exists('csrf_field')) csrf_field(); ?>
                                        <input type="hidden" name="target_user_id" value="<?php echo $pu['id']; ?>">
                                        <input type="hidden" name="new_role" value="player">
                                        <button type="submit" name="update_role" style="background:#ef4444; border:none; color:white; padding:5px 10px; border-radius:4px; font-size:0.8em; cursor:pointer;">Demote</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Role Definitions -->
            <div class="dashboard-card" style="grid-column: span 3; align-items: flex-start; text-align: left;">
                <h3>Role Definitions</h3>
                <div style="font-size:0.9rem; color:var(--text-muted); line-height:1.6;">
                    <strong style="color:var(--text-light); display:block; margin-bottom:5px;">Super Admin</strong>
                    Full control. Can manage all settings, edit users, delete participants, and update site structure.
                    
                    <strong style="color:var(--text-light); display:block; margin-top:10px; margin-bottom:5px;">Limited Admin</strong>
                    Standard day-to-day management. Can edit brackets and view data. Cannot delete other administrators.
                    
                    <strong style="color:var(--text-light); display:block; margin-top:10px; margin-bottom:5px;">Pay Editor</strong>
                    Restricted access. Can only update payment status for participants. Ideal for a treasurer.
                </div>
            </div>

        </div>
    </div>
</div>
<?php include('footer.php'); ?>
</body>
</html>


