<?php

namespace Database\Seeders;

use App\Models\WhatsappTemplate;
use Illuminate\Database\Seeder;

class WhatsappTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'order_placed_customer',
                'name' => 'Order Placed (Customer)',
                'event_key' => 'wachat_msg_order_placed_customer',
                'body' => 'Tijaar: Your order {{order_number}} has been placed successfully. Total: {{order_total}} PKR. Thank you!',
                'description' => 'Sent to buyer after placing an order. Placeholders: {{order_number}}, {{order_total}}, {{customer_name}}, {{app_name}}',
            ],
            [
                'slug' => 'order_placed_seller',
                'name' => 'Order Placed (Seller)',
                'event_key' => 'wachat_msg_order_placed_seller',
                'body' => 'Tijaar: New order {{order_number}} received. Total: {{order_total}} PKR. Please review and approve.',
                'description' => 'Sent to seller when a new order includes their products. Placeholders: {{order_number}}, {{order_total}}, {{seller_name}}, {{app_name}}',
            ],
            [
                'slug' => 'payment_success',
                'name' => 'Payment Success (Customer)',
                'event_key' => 'wachat_msg_payment_success',
                'body' => 'Tijaar: Payment received for order {{order_number}}. Amount: {{order_total}} PKR. Thank you!',
                'description' => 'Sent to buyer when payment succeeds. Placeholders: {{order_number}}, {{order_total}}, {{customer_name}}, {{app_name}}',
            ],
            [
                'slug' => 'order_approved',
                'name' => 'Order Approved (Customer)',
                'event_key' => 'wachat_msg_order_approved',
                'body' => 'Tijaar: Good news! Your order {{order_number}} has been accepted by the seller and will be shipped soon.',
                'description' => 'Sent to buyer when seller approves the order. Placeholders: {{order_number}}, {{customer_name}}, {{app_name}}',
            ],
            [
                'slug' => 'order_shipped',
                'name' => 'Order Shipped (Customer)',
                'event_key' => 'wachat_msg_order_shipped',
                'body' => 'Tijaar: Your order {{order_number}} has been shipped{{carrier_part}}{{tracking_part}}.',
                'description' => 'Sent to buyer when order ships. Placeholders: {{order_number}}, {{tracking_number}}, {{carrier}}, {{carrier_part}}, {{tracking_part}}, {{app_name}}',
            ],
            [
                'slug' => 'order_delivered_seller',
                'name' => 'Order Delivered (Seller)',
                'event_key' => 'wachat_msg_order_delivered_seller',
                'body' => 'Tijaar: Order {{order_number}} has been delivered to the customer.',
                'description' => 'Sent to seller when order is delivered. Placeholders: {{order_number}}, {{seller_name}}, {{app_name}}',
            ],
            [
                'slug' => 'whatsapp_otp',
                'name' => 'WhatsApp OTP Verification',
                'event_key' => null,
                'body' => 'Tijaar: Your WhatsApp verification code is {{otp}}. It expires in {{expiry_minutes}} minutes. Do not share this code.',
                'description' => 'OTP for WhatsApp number verification. Placeholders: {{otp}}, {{expiry_minutes}}, {{name}}, {{app_name}}',
            ],
        ];

        foreach ($templates as $t) {
            WhatsappTemplate::updateOrCreate(
                ['slug' => $t['slug']],
                array_merge($t, ['is_active' => true])
            );
        }
    }
}
