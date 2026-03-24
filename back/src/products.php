<?php 
require_once __DIR__ . '/config/Database.php';

$db = Database::getConnection();

$products = [];
$categories = [];

$category_stmt = $db->query("SELECT * FROM categories");
if ($category_stmt->rowCount() > 0) {
  $categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$product_stmt = $db->query("SELECT * FROM products");
if ($product_stmt->rowCount() > 0) {
  $products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo var_dump($products);
?>

<?php if (isset($_GET['error'])):  ?>
  <script>
    alert("<?php echo htmlspecialchars($_GET['error']); ?>");
    window.history.replaceState({}, document.title, window.location.pathname);
  </script>
<?php endif; ?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="/style.css" />
    <title>Products</title>
    <script defer src="../scripts/products.js"></script>
  </head>
  <body>
    <nav class="navbar">
      <a href="./index.php" class="main-title">Suite Store</a>
      <ul class="nav-list">
        <li class="nav-item"><a href="./products.php">Products</a></li>
        <li class="nav-item"><a href="./categories.php">Categories</a></li>
        <li class="nav-item"><a href="./history.php">History</a></li>
      </ul>
    </nav>

    <div class="container">
      <h2 class="page-title">Products</h2>
      <main class="main-content">
        <div class="form-wrapper">
          <form action="actions/products/createProduct.php" method="POST">
            <input type="text" name="name" placeholder="Product name" id="product-name" />
            <div class="fields-wrapper">
              <input type="number" name="amount" placeholder="Amount" id="product-amount" />
              <input type="number" name="price" placeholder="Price" id="product-price" />
              <select name="category-code" id="category-selector">
                  <?php foreach($categories as $cat): ?>
                  <option value="<? $cat['code']; ?>"><?= $cat['name']; ?></option>
                  <?php endforeach ?>
                </select>
            </div>
            <button type="submit" class="submit-btn" id="submit-btn">Add Product</button>
          </form>
        </div>

        <hr />

        <section class="product-section">
          <div class="table-container">
            <table>
              <tr>
                <th>Code</th>
                <th>Product</th>
                <th>Amount</th>
                <th>Unit price</th>
                <th>Category</th>
                <th>Actions</th>
              </tr>
              <tbody id="products-table-body">
                
              </tbody>
            </table>
            <div id="products-empty-state"></div>
          </div>
        </section>
      </main>
    </div>

  </body>
</html>

<style>
  .products-empty-state {
    margin: 1rem auto;
    padding-left: .5rem;
  }
  .fields-wrapper {
    display: flex;
    width: 100%;
    gap: 0.5rem;
  }
  .fields-wrapper > input {
    width: 100%;
  }
</style>