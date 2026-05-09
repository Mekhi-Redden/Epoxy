<footer class="footer">
  <div class="footer-inner">
    <div class="footer-col">
      <h4>EPOXY</h4>
      <p>A marketplace for original digital and physical artwork.</p>
    </div>
    <div class="footer-col">
      <h4>Explore</h4>
      <a href="gallery.php">Gallery</a>
      <a href="search.php">Search</a>
      <a href="prices.php">Browse by Price</a>
    </div>
    <div class="footer-col">
      <h4>Account</h4>
      <a href="profile.php">My Profile</a>
      <a href="purchase_history.php">Purchases</a>
      <a href="apply_seller.php">Sell Your Art</a>
    </div>
    <div class="footer-col">
      <h4>Details</h4>
      
      <a href="tos.php">Terms of Service</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© <?= date('Y') ?> EPOXY Art Marketplace. All rights reserved.</p>
  </div>
</footer>

<script>
function toggleTheme() {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
}
// Restore theme on load
if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark');

// Disable right-click on artwork images
document.addEventListener('contextmenu', e => {
    if (e.target.tagName === 'IMG' && e.target.closest('.img-wrap')) e.preventDefault();
});
</script>
</body>
</html>