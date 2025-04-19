<?php

namespace App\Commons\CodeMasters;

enum OrderStatus: int
{
  case PENDING = 0;
  case PROCESSING = 1;
  case SHIPPED = 2;
  case DELIVERED = 3;
  case CANCELLED = 4;

  public function label(): string
  {
    return match ($this) {
      self::PENDING => __('Pending'),
      self::PROCESSING => __('Processing'),
      self::SHIPPED => __('Shipped'),
      self::DELIVERED => __('Delivered'),
      self::CANCELLED => __('Cancelled'),
    };
  }
}
