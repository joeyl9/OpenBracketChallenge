<?php
include("functions.php");
validatecookie();
check_admin_auth('super'); // Only Super Admins
include("header.php");

// Fetch Logs
// Join with admin_users to get the username. 
// If user was deleted, we still want the log, so LEFT JOIN.
$query = "SELECT l.*, u.username 
          FROM admin_logs l 
          LEFT JOIN admin_users u ON l.admin_id = u.id 
          ORDER BY l.timestamp DESC 
          LIMIT 200";

$logs = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

?>

<div id="main">
    <div class="full">
        <h2>Admin Audit Logs</h2>
        <p>Showing last 200 actions.</p>

        <div class="dashboard-grid" style="display:flex; justify-content:center;">
            <div class="dashboard-card" style="width:100%; max-width:1200px; align-items: flex-start; text-align: left;">
                <div class="table-container">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color);">
                                <th style="padding: 15px 10px; text-align: left; color:var(--text-muted);">Date/Time</th>
                                <th style="padding: 15px 10px; text-align: left; color:var(--text-muted);">User</th>
                                <th style="padding: 15px 10px; text-align: left; color:var(--text-muted);">Action</th>
                                <th style="padding: 15px 10px; text-align: left; color:var(--text-muted);">Details</th>
                                <th style="padding: 15px 10px; text-align: left; color:var(--text-muted);">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 10px; white-space: nowrap; font-size: 0.9rem; color: var(--text-muted);">
                                    <?php echo date("M j, g:i a", strtotime($log['timestamp'])); ?>
                                </td>
                                <td style="padding: 12px 10px; font-weight: bold; color: var(--accent-orange);">
                                    <?php echo htmlspecialchars($log['username'] ?? 'Unknown (ID:'.$log['admin_id'].')'); ?>
                                </td>
                                <td style="padding: 12px 10px;">
                                    <span style="background: var(--primary-blue); border:1px solid var(--border-color); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; color:var(--text-light);">
                                        <?php echo htmlspecialchars($log['action_type']); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 10px; color: var(--text-light);">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </td>
                                <td style="padding: 12px 10px; font-family: monospace; font-size: 0.85rem; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($log['ip_address']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('footer.php'); ?>
</body>
</html>


