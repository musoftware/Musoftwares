import os
import re

def fix_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Fix: __('key', 'fallback') -> (__('key') ?? 'fallback')
    new_content = re.sub(r"__\((['\"][^'\"]+['\"])\s*,\s*(['\"][^'\"]+['\"])\)", r"(__(\1) ?? \2)", content)

    if content != new_content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f'Fixed {filepath}')

for root, _, files in os.walk('resources/js'):
    for file in files:
        if file.endswith('.tsx') or file.endswith('.ts'):
            fix_file(os.path.join(root, file))
