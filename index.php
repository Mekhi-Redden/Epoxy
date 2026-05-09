<?php include("includes/header.php"); ?>

<!-- HERO SECTION -->
<div class="section">
  <h1>Welcome to EPOXY</h1>
  <p>Discover, upload, and sell amazing artwork.</p>
</div>

<!-- SEARCH SECTION -->
<div class="section">
  <h2>Search Artwork</h2>

  <form method="GET" action="search.php">
    <input name="q" placeholder="Search by title or tag">
    <button>Search</button>
  </form>
</div>

<!-- FEATURED ARTWORK -->
<div class="section">
  <h2>Latest Artwork</h2>

  <div class="portfolio">

  <?php
  // Get latest 6 images
 $res = $conn->query("SELECT * FROM images ORDER BY id DESC LIMIT 6");

while($row = $res->fetch_assoc()){
    echo "
    <div class='img-wrap'>
        <img src='images/{$row['filename']}'>

        <div class='overlay'>
            <h3>{$row['title']}</h3>
            <p>\${$row['price']}</p>

            <a href='add_to_cart.php?id={$row['id']}'>
                <button>Add to Cart</button>
            </a>

            <a href='favorite.php?id={$row['id']}'>
                <button>❤️ Like</button>
            </a>
        </div>
    </div>
    ";
}
  ?>

  </div>
</div>



</body>
</html>
<?php include("includes/footer.php"); ?>