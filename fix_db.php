<?php
try {
    DB::statement("ALTER TABLE erp_invoice_items ADD product_id bigint unsigned DEFAULT NULL;");
    echo "Added product_id column.\n";
} catch (\Exception $e) {
    echo "product_id column error: " . $e->getMessage() . "\n";
}

try {
    DB::statement("ALTER TABLE erp_invoice_items ADD CONSTRAINT erp_invoice_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES erp_products(id) ON DELETE SET NULL;");
    echo "Added foreign key for product_id.\n";
} catch (\Exception $e) {
    echo "product_id foreign key error: " . $e->getMessage() . "\n";
}

try {
    DB::statement("ALTER TABLE erp_invoice_items ADD uom varchar(255) DEFAULT NULL;");
    echo "Added uom column.\n";
} catch (\Exception $e) {
    echo "uom column error: " . $e->getMessage() . "\n";
}

try {
    DB::statement("ALTER TABLE erp_invoice_items MODIFY COLUMN type ENUM('simple', 'quantity', 'timer', 'product') NOT NULL;");
    echo "Modified type ENUM.\n";
} catch (\Exception $e) {
    echo "type ENUM error: " . $e->getMessage() . "\n";
}
