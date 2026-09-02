<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'welcome',
                'name' => 'Welcome (Signup)',
                'subject' => 'Welcome to {{app_name}}',
                'body_html' => '<p>Hi {{name}},</p><p>Welcome to {{app_name}}! Your account has been created.</p><p><a href="{{dashboard_url}}">Go to Dashboard</a></p><p>Thanks,<br>{{app_name}} Team</p>',
                'body_plain' => "Hi {{name}},\n\nWelcome to {{app_name}}! Your account has been created.\n\nGo to Dashboard: {{dashboard_url}}\n\nThanks,\n{{app_name}} Team",
                'description' => 'Sent after user registration. Placeholders: {{name}}, {{app_name}}, {{dashboard_url}}',
            ],
            [
                'slug' => 'verify_email',
                'name' => 'Verify Email',
                'subject' => 'Verify your email – {{app_name}}',
                'body_html' => '<p>Hi {{name}},</p><p>Thanks for registering on {{app_name}}. Please verify your email with this code:</p><p style="font-size:24px;letter-spacing:4px;font-weight:bold;">{{otp}}</p><p>This code expires in {{expiry_minutes}} minutes.</p><p>If you did not create an account, you can ignore this email.</p><p>Thanks,<br>{{app_name}} Team</p>',
                'body_plain' => "Hi {{name}},\n\nThanks for registering on {{app_name}}. Your verification code is: {{otp}}\n\nThis code expires in {{expiry_minutes}} minutes.\n\nIf you did not create an account, ignore this email.\n\nThanks,\n{{app_name}} Team",
                'description' => 'Sent after customer registration when email verification is enabled. Placeholders: {{name}}, {{otp}}, {{expiry_minutes}}, {{app_name}}',
            ],
            [
                'slug' => 'otp_verification',
                'name' => 'OTP Verification (legacy)',
                'subject' => 'Your verification code – {{app_name}}',
                'body_html' => '<p>Hi {{name}},</p><p>Your verification code is: <strong>{{otp}}</strong></p><p>This code expires in {{expiry_minutes}} minutes.</p><p>If you did not request this, please ignore this email.</p><p>Thanks,<br>{{app_name}} Team</p>',
                'body_plain' => "Hi {{name}},\n\nYour verification code is: {{otp}}\n\nThis code expires in {{expiry_minutes}} minutes.\n\nThanks,\n{{app_name}} Team",
                'description' => 'Legacy OTP template. Prefer Verify Email (verify_email). Placeholders: {{name}}, {{otp}}, {{expiry_minutes}}, {{app_name}}',
            ],
            [
                'slug' => 'order_placed_seller',
                'name' => 'Order Placed (Seller)',
                'subject' => 'New order #{{order_number}} – {{app_name}}',
                'body_html' => '<p>Hi {{seller_name}},</p><p>A customer has placed an order that includes your products.</p><p><strong>Order #{{order_number}}</strong> – Total: {{order_total}} {{currency}}</p><p><a href="{{order_url}}">View order</a></p><p>Thanks for selling on {{app_name}}!</p>',
                'body_plain' => "Hi {{seller_name}},\n\nNew order #{{order_number}} – Total: {{order_total}} {{currency}}\n\nView order: {{order_url}}\n\nThanks,\n{{app_name}} Team",
                'description' => 'Sent to seller when a customer places an order. Placeholders: {{seller_name}}, {{order_number}}, {{order_total}}, {{currency}}, {{order_url}}, {{app_name}}',
            ],
            [
                'slug' => 'payout_requested',
                'name' => 'Payout Requested (Seller confirmation)',
                'subject' => 'Payout request submitted – {{app_name}}',
                'body_html' => '<p>Hi {{name}},</p><p>Your payout request has been submitted successfully.</p><p><strong>Payout #{{payout_number}}</strong> – Amount: {{amount}} PKR</p><p>Status: Pending admin approval. You will receive an email once it is approved.</p><p><a href="{{payouts_url}}">View payouts</a></p><p>Thanks,<br>{{app_name}} Team</p>',
                'body_plain' => "Hi {{name}},\n\nPayout #{{payout_number}} – {{amount}} PKR submitted. Pending approval.\n\nView: {{payouts_url}}\n\nThanks,\n{{app_name}} Team",
                'description' => 'Sent to seller when they request a payout. Placeholders: {{name}}, {{payout_number}}, {{amount}}, {{payouts_url}}, {{app_name}}',
            ],
            [
                'slug' => 'payout_approved',
                'name' => 'Payout Approved (Seller)',
                'subject' => 'Payout approved – #{{payout_number}} – {{app_name}}',
                'body_html' => '<p>Hi {{name}},</p><p>Your payout request has been approved by admin.</p><p><strong>Payout #{{payout_number}}</strong> – Amount: {{amount}} PKR</p><p>The amount has been deducted from your wallet. You will receive the transfer according to your payout method.</p><p><a href="{{transactions_url}}">View transaction history</a></p><p>Thanks,<br>{{app_name}} Team</p>',
                'body_plain' => "Hi {{name}},\n\nPayout #{{payout_number}} – {{amount}} PKR approved.\n\nView: {{transactions_url}}\n\nThanks,\n{{app_name}} Team",
                'description' => 'Sent to seller when admin approves payout. Placeholders: {{name}}, {{payout_number}}, {{amount}}, {{transactions_url}}, {{app_name}}',
            ],
            [
                'slug' => 'order_status',
                'name' => 'Order Status (Buyer)',
                'subject' => 'Order #{{order_number}} – {{status}}',
                'body_html' => '<p>Hi {{name}},</p><p>Your order <strong>#{{order_number}}</strong> status: {{status}}</p><p><a href="{{order_url}}">View order</a></p><p>Thanks,<br>{{app_name}} Team</p>',
                'body_plain' => "Hi {{name}},\n\nOrder #{{order_number}} status: {{status}}\n\nView: {{order_url}}\n\nThanks,\n{{app_name}} Team",
                'description' => 'Sent to buyer when order status changes. Placeholders: {{name}}, {{order_number}}, {{status}}, {{order_url}}, {{app_name}}',
            ],
        ];

        foreach ($templates as $t) {
            EmailTemplate::updateOrCreate(
                ['slug' => $t['slug']],
                $t
            );
        }
    }
}
