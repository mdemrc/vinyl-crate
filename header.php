<header class="site-header">
    <div class="menu-row">
        <a href="index.php" class="brand"><span class="brand-disc"></span> Vinyl Crate</a>
        <nav>
            <a href="index.php">Browse</a>
            <a href="crate.php">My Crate</a>
            <a href="my_reviews.php">My Reviews</a>
            <?php if ((int)$_SESSION["is_admin"] === 1): ?>
                <a href="admin.php">Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="logout">Log out (<?php echo htmlspecialchars($_SESSION["username"]); ?>)</a>
        </nav>
    </div>
</header>
