<?php
require_once __DIR__ . '/config/Database.php';

$db = Database::getConnection();

$products = [];
$categories = [];

$category_stmt = $db->query("SELECT * FROM categories c WHERE c.status = 'active'");
if ($category_stmt->rowCount() > 0) {
  $categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$product_stmt = $db->query("SELECT * FROM products p WHERE p.status = 'active'");
if ($product_stmt->rowCount() > 0) {
  $products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
}

function findCategoryById($categoryId, $categoriesList)
{
  $results = array_filter($categoriesList, fn($category) => $category['code'] === $categoryId);
  return $category = array_values($results)[0]['name'];
}
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'fk'): ?>
  <script>
    alert("Can't delete, this item has associated registers.")
  </script>

<?php elseif (isset($_GET['error'])):  ?>
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
            <input type="number" name="price" step="0.01" min="0.1" max="10000000000" placeholder="Price" id="product-price" />
            <select name="category-code" id="category-selector">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['code']; ?>"><?= $cat['name']; ?></option>
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
              <?php foreach ($products as $prod): ?>
                <tr>
                  <td><?= $prod['code'] ?></td>
                  <td><?= $prod['name'] ?></td>
                  <td><?= number_format($prod['amount'], 0, ',', '.') ?></td>
                  <td>$<?= number_format($prod['price'], 2, ',', '.') ?></td>
                  <td><?= findCategoryById($prod['category_code'], $categories) ?></td>
                  <td><a class="delete-btn" href="actions/products/deleteProduct.php?code=<?= $prod['code']; ?>"
                      onclick="return confirm('Delete product?')">
                      Delete
                    </a></td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
          <div id="products-empty-state"></div>
        </div>
      </section>
    </main>
  </div>

  <script>
    const categorySelector = document.getElementById("category-selector");
    const productNameInput = document.getElementById("product-name");
    const productAmountInput = document.getElementById("product-amount");
    const productPriceInput = document.getElementById("product-price");

    const productNameObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === "attributes" && mutation.attributeName === "type") {
          if (productNameInput.type !== "text") {
            productNameInput.type = "text";
            productNameInput.value = "";
          }
        }
      });
    });
    
    const productAmountObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === "attributes" && mutation.attributeName === "type") {
          if (productAmountInput.type !== "number") {
            productAmountInput.type = "number";
            productAmountInput.value = "";
          }
        }
      });
    });
    
    const productPriceObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === "attributes" && mutation.attributeName === "type") {
          if (productPriceInput.type !== "number") {
            productPriceInput.type = "number";
            productPricetInput.value = "";
          }
        }
      });
    });
    
    const categorySelectorObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.addedNodes.length > 0) {
          mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1) {
              node.remove();
            }
          });
        }
      });
    });

    categorySelectorObserver.observe(categorySelector, {
      childList: true
    });
    productPriceObserver.observe(productPriceInput, {
      attributes: true
    });
    productAmountObserver.observe(productAmountInput, {
      attributes: true
    });
    productNameObserver.observe(productNameInput, {
      attributes: true
    });
  </script>
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

  .fields-wrapper>input {
    width: 100%;
  }
</style>