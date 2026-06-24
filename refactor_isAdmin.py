import os
import re

app_dir = r"d:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\app"

# We want to match ->hasAnyRole(['...']) or ->hasRole(['...']) 
# where the array is exactly some combination of admin, super_admin, Admin, superadmin.
# Or exactly those 4. Let's just match any combination of those strings in an array.
# For simplicity, let's match the exact strings we saw:
patterns = [
    r"->hasAnyRole\(\[\s*'admin',\s*'super_admin',\s*'Admin',\s*'superadmin'\s*\]\)",
    r"->hasRole\(\[\s*'super_admin',\s*'admin',\s*'superadmin',\s*'Admin'\s*\]\)",
    r"->hasAnyRole\(\[\s*'admin',\s*'Admin',\s*'super_admin',\s*'superadmin'\s*\]\)",
]

count = 0
for root, dirs, files in os.walk(app_dir):
    for file in files:
        if file.endswith(".php"):
            path = os.path.join(root, file)
            with open(path, "r", encoding="utf-8") as f:
                content = f.read()
            
            original_content = content
            for p in patterns:
                content = re.sub(p, "->isAdmin()", content)
            
            if content != original_content:
                with open(path, "w", encoding="utf-8") as f:
                    f.write(content)
                print(f"Updated {path}")
                count += 1

print(f"Updated {count} files.")
