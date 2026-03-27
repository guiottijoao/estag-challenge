<?php

class FinishOrderController
{

  private $db;

  function __construct($db)
  {
    $this->db = $db;
  }

  public function finish()
  {
    try {
      $order_select_stmt = $this->db->query("SELECT * FROM orders o WHERE o.status = 'open'");
      $openOrder = $order_select_stmt->fetch(PDO::FETCH_ASSOC);
      if ($openOrder) {
        error_log("Joined first if");
        $openOrderId = $openOrder['code'];
        $order_item_select_stmt = $this->db->query("SELECT * FROM order_item ot WHERE ot.order_code = '$openOrderId'");
        $orderItems = $order_item_select_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($orderItems) {
          error_log("Joined first if");
          $open_order_update_stmt = $this->db->prepare("UPDATE orders SET status = 'close'");
          $open_order_update_stmt->execute();
        }
      }
      return;
    } catch (Exception $e) {
      throw $e;
    }
  }
}
