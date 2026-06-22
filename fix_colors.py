import os
import re

target_dirs = [
    'resources/js/Pages/Admin',
    'resources/js/Components/Admin',
    'resources/js/Layouts/AdminSidebarLayout.tsx',
    'resources/js/Components/AdminNotesPanel.tsx'
]

# Mapping rules
# Decorative -> Black & White (slate)
decorative_colors = ['blue', 'indigo', 'purple', 'cyan', 'teal', 'sky', 'violet', 'fuchsia', 'pink']

# Semantic mappings
semantic_mappings = {
    'rose': 'red',
    'emerald': 'green',
    'amber': 'yellow',
    'orange': 'yellow'
}

def replace_color(match):
    prefix = match.group(1) # text, bg, border, ring, etc.
    color = match.group(2)
    weight = int(match.group(3))

    if color in decorative_colors:
        # Convert to slate
        if weight >= 800:
            new_weight = 900
        elif weight >= 600:
            new_weight = 900 if prefix in ['bg', 'text', 'border', 'ring'] else 800
        elif weight >= 500:
            new_weight = 900 if prefix in ['bg', 'border', 'ring'] else 700
        elif weight >= 400:
            new_weight = 500
        elif weight >= 200:
            new_weight = 200
        else:
            new_weight = 50

        # Special cases for better UI
        if prefix == 'text' and new_weight == 900 and weight < 800:
            new_weight = 900
        
        return f"{prefix}-slate-{new_weight}"
        
    elif color in semantic_mappings:
        new_color = semantic_mappings[color]
        # Keep the weight roughly the same, but standard is 600 for bg/text semantic
        if weight == 500 and prefix in ['bg', 'text']:
            new_weight = 600
        else:
            new_weight = weight
        return f"{prefix}-{new_color}-{new_weight}"
        
    return match.group(0)

pattern = re.compile(r'\b(text|bg|border|ring|fill|stroke|from|via|to)-(blue|indigo|purple|cyan|teal|sky|violet|fuchsia|pink|rose|emerald|amber|orange)-(\d{2,3})\b')

changed_files = 0
for target in target_dirs:
    if os.path.isfile(target):
        files_to_process = [target]
    elif os.path.isdir(target):
        files_to_process = []
        for root, _, files in os.walk(target):
            for file in files:
                if file.endswith(('.tsx', '.jsx', '.ts', '.js')):
                    files_to_process.append(os.path.join(root, file))
    else:
        continue

    for filepath in files_to_process:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        new_content, count = pattern.subn(replace_color, content)
        
        if count > 0:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            changed_files += 1
            print(f"Updated {count} colors in {filepath}")

print(f"\nTotal files changed: {changed_files}")
