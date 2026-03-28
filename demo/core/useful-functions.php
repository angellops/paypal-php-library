<?php
/**
 * Reads and returns the raw SVG content from a given file path.
 * * Useful for embedding SVG code directly into HTML to allow for 
 * CSS manipulation and reducing HTTP requests.
 *
 * @param string $path The absolute or relative server path to the SVG file.
 * @return string Returns the SVG XML content if found, or an empty string if the file does not exist.
 */
function inline_svg($path)
{
  if (file_exists($path)) {
    return file_get_contents($path);
  }
  return '';
}

/**
 * Get cart contents from session
 *
 * @return array
 */
function get_cart()
{
  return !empty($_SESSION['paylater_items']) ? $_SESSION['paylater_items'] : [];
}

/**
 * Add an item to the cart
 *
 * @param string|int $id    Product ID
 * @param string     $name  Product name
 * @param float      $price Product price
 * @param int        $qty   Quantity to add
 *
 * @return void
 */
function add_to_cart($id, $name, $price, $qty)
{
  if (!isset($_SESSION['paylater_items'])) {
    $_SESSION['paylater_items'] = [];
  }

  if (isset($_SESSION['paylater_items'][$id])) {
    $_SESSION['paylater_items'][$id]['qty'] += $qty;
  } else {
    $_SESSION['paylater_items'][$id] = [
      'name' => $name,
      'price' => (float) $price,
      'qty' => (int) $qty
    ];
  }
}

/**
 * Update cart item quantity
 *
 * @param string|int $id  Product ID
 * @param int        $qty New quantity
 *
 * @return void
 */
function update_cart_qty($id, $qty)
{
  if ($qty <= 0) {
    unset($_SESSION['paylater_items'][$id]);
  } else {
    $_SESSION['paylater_items'][$id]['qty'] = $qty;
  }
}

/**
 * Empty the entire cart
 *
 * @return void
 */
function empty_cart()
{
  unset($_SESSION['paylater_items']);
}

/**
 * Calculate cart total amount
 *
 * @return string Formatted total (2 decimal places)
 */
function cart_total()
{
  $total = 0;
  foreach (get_cart() as $item) {
    $total += $item['price'] * $item['qty'];
  }
  return number_format($total, 2, '.', '');
}

/**
 * Calculate cart subtotal amount
 *
 * @return string Formatted total (2 decimal places)
 */
function calculate_subtotal($items)
{
  $subtotal = 0;
  foreach ($items as $item) {
    $subtotal += $item['price'] * $item['qty'];
  }
  return number_format($subtotal, 2, '.', '');
}