<?php
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/sidebar.php'; 

// Hakikisha admin pekee ndiye anaweza kuona logi hizi
if ($_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit();
}
?>

<style>
    .main-content { margin-left: 260px; padding: 90px 30px 30px 30px; color: #fff; }
    .log-table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden; }
    .log-table th, .log-table td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .log-table th { background: rgba(255,255,255,0.1); color: #CFA58C; }
    .log-table tr:hover { background: rgba(255,255,255,0.08); }
</style>

<div class="main-content">
    <h1>System Activity Logs 📋</h1>
    <p>Monitor all user actions within the system.</p>

    <div style="overflow-x:auto; margin-top: 20px;">
        <table class="log-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Activity</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Tunavuta logs na ku-JOIN na table ya users ili kupata jina la mtumiaji
                $query = "SELECT l.*, u.username 
                          FROM activity_logs l 
                          LEFT JOIN users u ON l.user_id = u.id 
                          ORDER BY l.created_at DESC";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                                <td>" . ($row['username'] ?? 'System') . "</td>
                                <td>{$row['activity']}</td>
                                <td>{$row['ip_address']}</td>
                                <td>{$row['created_at']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center;'>No logs found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>