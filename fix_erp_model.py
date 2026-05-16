import re
import os

def fix_tenant_aware_model():
    filepath = 'Modules/ERP/app/Models/TenantAwareModel.php'
    if not os.path.exists(filepath):
        print(f"File {filepath} does not exist.")
        # Try to find it
        return

    print(f"File {filepath} exists.")
fix_tenant_aware_model()
