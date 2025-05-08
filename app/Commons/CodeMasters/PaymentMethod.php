<?php

namespace App\Commons\CodeMasters;

enum PaymentMethod: int
{
  case CREDIT_CARD = 0;
  case COD = 1; // Cash on Delivery
  case MOMO = 2;
  case BANK_TRANSFER = 3;

  public function label(): string
  {
    return match ($this) {
      self::CREDIT_CARD => __('Thẻ tín dụng'),
      self::COD => __('Thanh toán khi nhận hàng'),
      self::MOMO => __('Momo'),
      self::BANK_TRANSFER => __('Chuyển khoản ngân hàng'),
    };
  }
}
