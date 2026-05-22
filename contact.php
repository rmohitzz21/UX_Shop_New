<?php require_once 'includes/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>">
  <title>Contact - UX Pacific Shop</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">
  <?php include 'includes/header.php'; ?>
  <main>
    <section class="shop-hero compact"><div><span class="marketplace-kicker">Support</span><h1>Contact UX Pacific</h1><p>For product, bundle, or order questions, send the team a message.</p></div></section>
    <section class="account-grid">
      <form class="profile-card" id="contact-form">
        <label>Name<input name="name" required></label>
        <label>Email<input name="email" type="email" required></label>
        <label>Subject<input name="subject" required></label>
        <label>Message<textarea name="message" rows="6" required></textarea></label>
        <button class="btn btn-primary" type="submit">Send Message</button>
      </form>
      <aside class="profile-card">
        <h2>Direct support</h2>
        <p>Phone: +91 9274061063</p>
        <p>Email: hello@uxpacific.com</p>
        <p>512, Majestic Building, Near Law Garden BRTS Stand, Ahmedabad</p>
      </aside>
    </section>
  </main>
  <?php include 'includes/footer.php'; ?>
</div>
<script src="script.js"></script>
<script>
document.getElementById('contact-form').addEventListener('submit', async (event) => {
  event.preventDefault();
  const response = await fetch('api/contact/send.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() }, body: JSON.stringify(Object.fromEntries(new FormData(event.target).entries())) });
  const data = await response.json();
  showToast(data.message || (data.status === 'success' ? 'Message sent.' : 'Could not send message.'), data.status === 'success' ? 'success' : 'error');
  if (data.status === 'success') event.target.reset();
});
</script>
</body>
</html>
