<?php
// includes/header.php
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">ExpenseSplitter</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION['user_id'])): ?>
          <?php
          // load notifications for user
          $notifCount = 0;
          $notifications = [];
          if (class_exists('Notification')) {
            $nmodel = new Notification();
            $notifications = $nmodel->getUserNotifications($_SESSION['user_id'], 5);
            foreach ($notifications as $no) if (empty($no['is_read'])) $notifCount++;
          }
          ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              🔔 <?php if ($notifCount > 0): ?><span class="badge bg-danger"><?= $notifCount ?></span><?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown" style="min-width:300px;">
              <?php if (empty($notifications)): ?>
                <li class="dropdown-item small text-muted">No notifications</li>
              <?php else: ?>
                <?php foreach ($notifications as $no): ?>
                  <li class="dropdown-item">
                    <div><small class="text-muted"><?= htmlspecialchars($no['created_at']) ?></small></div>
                    <div><?= htmlspecialchars($no['message']) ?></div>
                  </li>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                <?php endforeach; ?>
              <?php endif; ?>
              <li><a class="dropdown-item text-center small" href="notifications.php">View all</a></li>
            </ul>
          </li>

          <li class="nav-item"><a class="nav-link" href="profile.php">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?></a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="index.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>