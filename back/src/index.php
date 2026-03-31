<?php
require_once __DIR__ . '/config/Database.php';

$db = Database::getConnection();

$categories = [];
$products = [];
$orderItems = [];

$category_stmt = $db->query("SELECT * FROM categories");
if ($category_stmt->rowCount() > 0) {
  $categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$product_stmt = $db->query("SELECT * FROM products p WHERE p.amount > 0 AND p.status = 'active'");
if ($product_stmt->rowCount() > 0) {
  $products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$orders_stmt = $db->query("SELECT * FROM orders o WHERE o.status = 'open'");
$activeOrder = $orders_stmt->rowCount() > 0 ? $orders_stmt->fetch(PDO::FETCH_ASSOC) : null;

$order_item_stmt = $db->prepare("SELECT * FROM order_item o WHERE o.order_code = :active_order_code");
if ($activeOrder) {
  $order_item_stmt->execute([":active_order_code" => $activeOrder['code']]);
  if ($order_item_stmt->rowCount() > 0) {
    $orderItems = $order_item_stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

function findProductById($productId, $productsList)
{
  $results = array_filter($productsList, fn($product) => $product['code'] === $productId);
  return $product = array_values($results)[0]['name'];
}

function calcTotalOrderItemPrice(int $amount, float $price, float $totalTax)
{
  return ($amount * $price) + $totalTax;
}

?>

<?php if (isset($_GET['error'])): ?>
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
  <title>SE Store</title>
  <script defer src="../scripts/orders.js"></script>
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
    <h2 class="page-title">Home</h2>
    <main class="main-content">
      <div class="form-wrapper">
        <form action="actions/orders/createOrder.php" method="POST">
          <div class="product-selector">
            <select name="product-code" id="product-selector">
              <option value="" disabled selected>Select a product</option>
              <?php foreach ($products as $prod): ?>
                <option value="<?= $prod['code']; ?>"><?= $prod['name'] ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="product-fields">
            <input name="amount" id="product-amount" type="number" placeholder="Amount" />
            <input name="tax" value="" id="order-tax" type="text" disabled placeholder="Tax" />
            <input name="price" value="" id="product-unit-price" type="text" disabled placeholder="Price" />
          </div>
          <button class="submit-btn" id="submit-btn">Add to order</button>
        </form>
      </div>

      <hr />

      <section class="product-section">
        <div class="table-container">
          <table id="orders-table">
            <tr>
              <th>Code</th>
              <th>Product</th>
              <th>Amount</th>
              <th>Unit price</th>
              <th>Tax</th>
              <th>Total</th>
              <th>Actions</th>
            </tr>
            <tbody id="orders-content-table">
              <?php foreach ($orderItems as $item): ?>
                <tr>
                  <td><?= $item['business_code'] ?></td>
                  <td><?= findProductById($item['product_code'], $products) ?></td>
                  <td><?= $item['amount'] ?></td>
                  <td>$<?= number_format($item['price'], 2, ',', '.') ?></td>
                  <td>$<?= number_format($item['tax'], 2, ',', '.') ?></td>
                  <td>$<?= number_format(calcTotalOrderItemPrice($item['amount'], $item['price'], $item['tax']), 2, ',', '. ') ?></td>
                  <td><a class="delete-btn" href="actions/orders/deleteOrder.php?code=<?= $item['code']; ?>"
                      onclick="return confirm('Delete item?')">
                      Delete
                    </a></td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
          <div id="orders-empty-state"></div>
        </div>

        <div class="summary">
          <div class="summary-values">
            <div class="summary-info">
              <p id="total-order-tax">Tax: $<?= $activeOrder ? number_format($activeOrder['tax'], 2, ',', '.') : '0,00' ?></p>
            </div>
            <div class="summary-info">
              <p id="total-order-price">Total: $<?= $activeOrder ? number_format($activeOrder['total'], 2, ',', '.') : '0,00' ?></p>
            </div>
          </div>

          <form class="actions" method="POST">
            <button type="submit" onclick="return confirm('Cancel order?')" formaction="actions/orders/cancelOrder.php" class="cancel-btn" id="cancel-btn">Cancel</button>
            <button type="submit" onclick="return confirm('Finish order?')" formaction="actions/orders/finishOrder.php" id="finish-btn">Finish</button>
          </form>
        </div>
      </section>
    </main>
  </div>

  <script>
    const products = <?= json_encode($products); ?>;
    const categories = <?= json_encode($categories); ?>;
    const productSelector = document.getElementById("product-selector");
    const orderProductAmountInput = document.getElementById("product-amount");
    const orderTaxInput = document.getElementById("order-tax");
    const orderPriceInput = document.getElementById("product-unit-price");

    document.getElementById("product-selector").addEventListener("change", (e) => {
      const code = e.target.value;
      const product = products.find(p => p.code === Number(code));
      const productCategory = categories.find(c => c.code === product.category_code);

      document.getElementById('order-tax').value = `Tax: ${productCategory.tax}%`;
      document.getElementById('product-unit-price').value = `Price: $${product.price}`;
    });

    const productSelectorObserver = new MutationObserver((mutations) => {
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

    const orderProductAmountObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === "attributes" && mutation.attributeName === "type") {
          if (orderProductAmountInput.type !== "number") {
            orderProductAmountInput.type = "number";
          }
        }
      });
    });

    const taxInputObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === "attributes" && mutation.attributeName === "type") {
          if (orderTaxInput.type !== "text") {
            orderTaxInput.type = "text";
            orderTaxInput.value = "Tax";
          }
        }
        if (
          mutation.type === "attributes" &&
          mutation.attributeName === "disabled"
        ) {
          if (orderTaxInput.disabled !== true) {
            orderTaxInput.disabled = true;
          }
        }
      });
    });

    const orderPriceInputObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === "attributes" && mutation.attributeName === "type") {
          if (orderPriceInput.type !== "text") {
            orderPriceInput.type = "text";
            orderPriceInput.value = "Price";
            }
        }
        if (
          mutation.type === "attributes" &&
          mutation.attributeName === "disabled"
        ) {
          if (orderPriceInput.disabled !== true) {
            orderPriceInput.disabled = true;
          }
        }
      });
    });

    orderProductAmountObserver.observe(orderProductAmountInput, {
      attributes: true,
    });
    productSelectorObserver.observe(productSelector, {
      childList: true
    });
    taxInputObserver.observe(orderTaxInput, {
      attributes: true
    });
    orderPriceInputObserver.observe(orderPriceInput, {
      attributes: true,
    });
  </script>
</body>

</html>

<style
  .product-selector>
  select {
    width: 100%;
  }

  .orders-empty-state {
    margin: 1rem auto;
    padding-left: .5rem;
  }

  .product-fields {
    display: flex;
    width: 100%;
    flex-direction: row;
    gap: 0.5rem;
  }

  .product-selector>select {
    width: 100%;
  }

  .product-fields {
    display: flex;
    width: 100%;
    flex-direction: row;
  }

  .product-fields>input {
    width: 100%;
  }

  .summary {
    display: flex;
    gap: 2rem;
    flex-direction: column;
    align-items: end;
  }

  .summary-values {
    display: flex;
    flex-direction: column;
  }

  .summary-info {
    display: flex;
    flex-direction: row;
    gap: 0.5rem;
  }

  .actions {
    display: flex;
    gap: 0.5rem;
    width: fit-content;
    display: flex;
    flex-direction: row;
  }

  #finish-btn {
    background-color: #6a65ff;
    border: none;
    cursor: pointer;
  }

  #finish-btn:hover {
    background-color: #5854d6;
  }
</style>