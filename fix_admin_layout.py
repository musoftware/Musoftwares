import re

def fix_admin_layout():
    with open('resources/js/Layouts/AdminLayout.tsx', 'r') as f:
        content = f.read()

    # The layout is currently flex min-h-screen bg-gray-50
    # Let's change the wrapper to min-h-screen bg-gray-50 (no flex)
    content = content.replace('<div className="flex min-h-screen bg-gray-50">', '<div className="min-h-screen bg-gray-50">')

    # Now let's change the sidebar
    old_sidebar = '<aside className={`hidden flex-col border-r border-gray-200 bg-white md:flex transition-all duration-300 ${isCollapsed ? \'w-16\' : \'w-[260px]\'}`}>'
    new_sidebar = '<aside className={`fixed left-0 top-0 z-40 hidden h-screen flex-col border-r border-gray-200 bg-white md:flex transition-all duration-300 ${isCollapsed ? \'w-16\' : \'w-[260px]\'}`}>'
    content = content.replace(old_sidebar, new_sidebar)

    # Now change the main content
    old_main = '<main className="flex min-w-0 flex-1 flex-col overflow-hidden">'
    new_main = '<main className={`flex min-h-screen flex-col transition-all duration-300 ${isCollapsed ? \'md:ml-16\' : \'md:ml-[260px]\'}`}>'
    content = content.replace(old_main, new_main)

    with open('resources/js/Layouts/AdminLayout.tsx', 'w') as f:
        f.write(content)

fix_admin_layout()
