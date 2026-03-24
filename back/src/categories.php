<?php
require_once __DIR__ . '/config/Database.php';

$db = Database::getConnection();

$categories = [];

$stmt = $db->query("SELECT * FROM categories");

if ($stmt->rowCount() > 0) {
  $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
  <title>Categories</title>
  <link rel="stylesheet" href="/style.css" />
  <script src="../scripts/categories.js" defer></script>
</head>

<body>
  <nav class="navbar">
    <a href="./index.html" class="main-title">Suite Store</a>
    <ul class="nav-list">
      <li class="nav-item"><a href="./products.html">Products</a></li>
      <li class="nav-item"><a href="./categories.html">Categories</a></li>
      <li class="nav-item"><a href="./history.html">History</a></li>
    </ul>
  </nav>

  <div class="container">
    <h2 class="page-title">Categories</h2>
    <main class="main-content">
      <div class="form-wrapper">
        <form action="actions/categories/createCategory.php" method="POST">
          <div class="form-fields">
            <input
              id="name"
              name="name"
              type="text"
              placeholder="Category name" />
            <input id="tax" name="tax" type="number" placeholder="Tax" />
          </div>
          <button class="submit-btn" id="submit-btn" type="submit">Add Category</button>
        </form>
      </div>

      <hr />

      <section class="product-section">
        <div class="table-container">
          <table>
            <tr>
              <th>Code</th>
              <th>Category</th>
              <th>Tax</th>
              <th>Actions</th>
            </tr>
            <tbody id="categories-table-body">
              <?php
              foreach ($categories as $cat):
              ?>
                <tr>
                  <td><?= $cat['code'] ?></td>
                  <td><?= $cat['name'] ?></td>
                  <td><?= number_format($cat['tax'], 2, ',', '.') ?>%</td>
                  <td>
                    <a class="delete-btn" href="actions/categories/deleteCategory.php?code=<?= $cat['code']; ?>"
                      onclick="return confirm('Delete category?')">
                      Delete
                    </a>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
          <div id="categories-empty-state"></div>
        </div>
      </section>
    </main>
  </div>

</body>

</html>

<style>
  .categories-empty-state {
    margin: 1rem auto;
    padding-left: .5rem;
  }

  .product-section {
    display: flex;
    width: 50%;
    height: 100%;
    flex-direction: column;
    justify-content: space-between;
    align-items: end;
  }

  .form-fields {
    display: flex;
    flex-direction: row;
    gap: 0.5rem;
  }

  .form-fields>input {
    margin-left: auto;
    margin-right: auto;
    width: 100%;
  }
</style>