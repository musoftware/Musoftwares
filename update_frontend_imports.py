import os
import re

directory = r'd:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\resources\js'

prefixes = [
    'Dashboard',
    'Financial',
    'Activity',
    'Billing',
    'Chat',
    'Messages',
    'Notifications',
    'Profile',
    'Settings',
    'Subscriptions',
    'Support',
    'Vouchers'
]

pattern_alias = re.compile(r"(@/Pages/)(" + '|'.join(prefixes) + r")\b")

# Also handle relative imports if they go up to Pages and back down
# e.g., from a file in Guest, ../Dashboard -> ../Client/Dashboard
# This is trickier. Let's start with alias imports.

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    new_content, count = pattern_alias.subn(r'\1Client/\2', content)

    if count > 0:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {count} occurrences in {filepath}")

for root, dirs, files in os.walk(directory):
    for file in files:
        if file.endswith(('.js', '.jsx', '.ts', '.tsx')):
            process_file(os.path.join(root, file))

print('Done frontend imports.')
