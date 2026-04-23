<?php

namespace App\Exceptions\Store;

use Exception;

/**
 * Thrown when a product or variant doesn't have enough stock under lock.
 *
 * Surfaced by StoreOrderService::createOrder when the TOCTOU guard rejects
 * the order before any stock is decremented.
 */
class OutOfStockException extends Exception
{
}
