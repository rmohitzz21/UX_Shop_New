<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$authPreloadSignedIn = !empty($_SESSION['user_id']);
?>
<script>
  (function () {
    try {
      var rawSession = localStorage.getItem('userSession');
      var user = rawSession ? JSON.parse(rawSession) : null;
      if (<?php echo $authPreloadSignedIn ? 'true' : 'false'; ?> || (user && user.id)) {
        document.documentElement.classList.add('auth-preloaded');
      }
    } catch (error) {}
  })();
</script>
<style>
  <?php if ($authPreloadSignedIn) : ?>
  .nav-cta[href="signin.php"],
  .header-signin-cta { display: none !important; }
  .nav-user,
  .user-menu.profile-menu { display: flex !important; }
  <?php endif; ?>
  html.auth-preloaded .nav-cta[href="signin.php"],
  html.auth-preloaded .header-signin-cta { display: none !important; }
  html.auth-preloaded .nav-user,
  html.auth-preloaded .user-menu.profile-menu { display: flex !important; }
</style>
