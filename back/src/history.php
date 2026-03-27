<?php
require_once __DIR__ . '/config/Database.php';

$db = Database::getConnection();

$orders = [];
$orderItems = [];

$orders_stmt = $db->query("SELECT * FROM orders o WHERE o.status = 'closed'");
if ($orders_stmt->rowCount() > 0) {
  $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$order_items_stmt = $db->query("SELECT o.code AS order_code,
p.name, oi.amount, p.price, oi.tax
FROM products p
INNER JOIN order_item oi
ON p.code = oi.product_code
INNER JOIN orders o
ON o.code = oi.order_code
WHERE o.status = 'closed'
AND o.code = oi.order_code");

if ($order_items_stmt->rowCount() > 0) {
  $orderItems = $order_items_stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>History</title>
  <link rel="stylesheet" href="/style.css" />
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
    <h2 class="page-title">History</h2>
    <main class="main-content" id="history-content">
      <div class="table-container">
        <table>
          <tr>
            <th>Code</th>
            <th>Tax</th>
            <th>Total</th>
            <th>Product details</th>
          </tr>
          <tbody id="history-table-body">
            <?php foreach ($orders as $order): ?>
              <tr>
                <td><?= $order['code'] ?></td>
                <td>$<?= number_format($order['tax'], 2, ',', '.') ?></td>
                <td>$<?= number_format($order['total'], 2, ',', '.') ?></td>
                <td><a class="view-btn" id="view-btn">
                    View
                  </a></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>

      <dialog id="order-products-modal">
        <nav>
          <h3 class="order-details-title">Order itens</h3>
          <div id="close-modal-btn">
            <p>X</p>
          </div>
        </nav>
        <table class="order-products-table">
          <tr>
            <th>Name</th>
            <th>Amount</th>
            <th>Unit Price</th>
            <th>Total tax</th>
          </tr>
          <tbody id="order-products-table-content">
            <?php foreach ($orderItems as $item): ?>
              <tr>
                <td><?= $item['name'] ?></td>
                <td><?= $item['amount'] ?></td>
                <td>$<?= number_format($item['price'], 2, ',', '.') ?></td>
                <td><?= number_format($item['tax'], 2, ',', '.') ?>%</td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </dialog>
    </main>
  </div>
  <script>
    const closeModalBtn = document.getElementById("close-modal-btn");
    const viewBtn = document.getElementById("view-btn");
    const orderProductsModal = document.getElementById("order-products-modal");

    viewBtn.addEventListener("click", () => {
      orderProductsModal.showModal();
    })
    closeModalBtn.addEventListener("click", () => {
      orderProductsModal.close();
    })
  </script>
</body>

</html>

<style>
  .delete-btn,
  .view-btn {
    background-color: #6a65ff;
    text-decoration: none;
    color: black;
    border: none;
    transition: ease-in-out 100ms;
    padding: .5rem;
  }

  .view-btn:hover {
    background-color: #514cdb;
  }

  #history-content {
    display: flex;
    flex-direction: column;
  }

  a {
    text-decoration: none;
    color: black;
  }

  button {
    background-color: #c5c5c5;
    border: none;
    transition: ease-in-out 100ms;
  }

  button:hover {
    background-color: #a7a7a7;
  }

  #order-products-modal {
    position: absolute;
    padding: 1rem;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border: 1px solid rgb(146, 146, 146);
    border-radius: 0.5rem;
    box-shadow:
      rgba(81, 81, 136, 0.25) 0px 13px 27px -5px,
      rgba(161, 161, 161, 0.3) 0px 8px 16px -8px;
  }

  #order-products-modal>nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: .75rem;
  }

  #order-products-modal>nav>div {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: .25rem;
    transition: 100ms ease-in-out;
    border-radius: .5rem;
  }

  #order-products-modal>nav>div:hover {
    background-color: #a7a7a7;
  }

  #order-products-modal>nav>div>p {
    cursor: pointer;
    font-weight: bold;
  }

  .order-details-title {
    font-weight: 600;
    text-decoration: underline;
  }

  .order-products-table {
    margin: 0;
  }

  td,
  th {
    padding: 1rem;
  }
</style>