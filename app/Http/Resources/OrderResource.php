<?php

namespace App\Http\Resources;

use App\Commons\CodeMasters\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_status' => $this->order_status,
            'order_status_label' => OrderStatus::from($this->order_status)->label(),
            'note' => $this->note,
            'product_summary' => $this->order_details->sum(function ($item) {
                return $item->product_price;
            }),
            'order_total' => $this->order_details->sum(function ($item) {
                return $item->product_sales_quantity;
            }),
            'order_details' => $this->order_details->map(function ($detail) {
                return [
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product_name,
                    'quantity' => $detail->product_sales_quantity,
                    'price' => $detail->product_price,
                    'image' => $detail->product->image ?? null,
                ];
            }),
            'created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }
}
