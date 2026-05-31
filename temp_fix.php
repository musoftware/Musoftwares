DB::statement('ALTER TABLE sms_payment_gateway_transactions MODIFY status VARCHAR(50) DEFAULT ''pending'''); echo 'Done';
