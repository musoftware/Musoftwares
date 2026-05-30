# Database Migration Rules

## Rules
1. Always define `down()` methods to drop tables/columns cleanly.
2. Use constrained foreign keys: `$table->foreignId('client_id')->constrained('erp_tenant_clients')->cascadeOnDelete();`
3. Never drop columns without confirming it won't break existing live data.
