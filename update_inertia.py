import os
import re

directories = [
    r'd:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\app\Http\Controllers',
    r'd:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\routes'
]

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

pattern = re.compile(r"(Inertia::render\(\s*['\"])(" + '|'.join(prefixes) + r")([^'\"]*['\"])")

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    new_content, count = pattern.subn(r'\1Client/\2\3', content)

    if count > 0:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {count} occurrences in {filepath}")

for d in directories:
    for root, dirs, files in os.walk(d):
        for file in files:
            if file.endswith('.php'):
                process_file(os.path.join(root, file))

print('Done.')
