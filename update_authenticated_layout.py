import re

def update_authenticated_layout():
    with open('resources/js/Layouts/AuthenticatedLayout.tsx', 'r') as f:
        content = f.read()

    # We need to add Sheet imports
    imports_to_add = """import { Sheet, SheetContent, SheetTrigger } from '@/Components/ui/sheet';
import { Menu } from 'lucide-react';
"""

    # Add the imports
    content = content.replace("import { PropsWithChildren, ReactNode, useState } from 'react';", "import { PropsWithChildren, ReactNode, useState } from 'react';\n" + imports_to_add)

    # In AuthenticatedLayout, the navigation logic is a bit different as it's top nav,
    # but we should make sure mobile views can access the sidebars if they have them.
    # Actually, AuthenticatedLayout uses a top navbar. Let's see how it looks.

    print("AuthenticatedLayout might not need the same sidebar logic, skipping for now")

update_authenticated_layout()
