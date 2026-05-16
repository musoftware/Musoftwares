import re

def update_client_layout():
    with open('resources/js/Layouts/ClientLayout.tsx', 'r') as f:
        content = f.read()

    # We need to add Sheet imports and state
    imports_to_add = """import { Sheet, SheetContent, SheetTrigger } from '@/Components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { Menu, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
"""

    # Add the imports
    content = content.replace("import { PropsWithChildren } from 'react';", "import { PropsWithChildren } from 'react';\n" + imports_to_add)

    # Add state to ClientLayout
    content = content.replace("export default function ClientLayout({", "export default function ClientLayout({\n    user,\n    hasErpSubscription = false,\n    walletBalance = 0,\n    children,\n}: ClientLayoutProps) {\n    const [isCollapsed, setIsCollapsed] = useState(false);\n    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);\n")
    # Clean up the original signature that was split
    content = re.sub(r'export default function ClientLayout\(\{\s+user,\s+hasErpSubscription = false,\s+walletBalance = 0,\s+children,\s+\}: ClientLayoutProps\) \{\s+const \[isCollapsed', r'export default function ClientLayout({\n    user,\n    hasErpSubscription = false,\n    walletBalance = 0,\n    children,\n}: ClientLayoutProps) {\n    const [isCollapsed', content)
    # the replace command already did what we needed, let's just make sure we don't have duplicate destructuring

    # This might be tricky, let's just do a specific string replacement

    with open('resources/js/Layouts/ClientLayout.tsx', 'r') as f:
        content = f.read()

    content = content.replace("export default function ClientLayout({\n    user,\n    hasErpSubscription = false,\n    walletBalance = 0,\n    children,\n}: ClientLayoutProps) {", "export default function ClientLayout({\n    user,\n    hasErpSubscription = false,\n    walletBalance = 0,\n    children,\n}: ClientLayoutProps) {\n    const [isCollapsed, setIsCollapsed] = useState(false);\n    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);\n")

    # Add imports
    if "import { Sheet" not in content:
        content = content.replace("import { PropsWithChildren } from 'react';", "import { PropsWithChildren } from 'react';\n" + imports_to_add)

    # Replace the sidebar
    old_sidebar = """<aside className="flex hidden w-64 flex-col border-r border-gray-200 bg-white md:flex">
                <div className="flex h-16 items-center border-b border-gray-200 px-6">
                    <span className="text-xl font-bold text-gray-900">
                        Client Portal
                    </span>
                </div>
                <nav className="flex-1 space-y-2 px-4 py-6">
                    {modules.map((item) => (
                        <Link
                            key={item.name}
                            href={route(item.route)}
                            className="flex items-center rounded-md bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900"
                        >
                            <item.icon className="mr-3 h-5 w-5" />
                            {item.name}
                        </Link>
                    ))}
                </nav>
            </aside>"""

    new_sidebar = """{/* Desktop Sidebar */}
            <aside className={`hidden flex-col border-r border-gray-200 bg-white md:flex transition-all duration-300 ${isCollapsed ? 'w-16' : 'w-[260px]'}`}>
                <div className="flex h-16 items-center border-b border-gray-200 px-4 flex-shrink-0">
                    {!isCollapsed && <span className="text-xl font-bold text-gray-900 overflow-hidden whitespace-nowrap">Client Portal</span>}
                </div>
                <nav className="flex-1 space-y-2 px-2 py-6 overflow-y-auto overflow-x-hidden">
                    <TooltipProvider delayDuration={0}>
                        {modules.map((item) => (
                            <Tooltip key={item.name}>
                                <TooltipTrigger asChild>
                                    <Link
                                        href={route(item.route)}
                                        className={`flex items-center rounded-md bg-gray-100 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900 ${isCollapsed ? 'justify-center px-0' : 'px-4'}`}
                                    >
                                        <item.icon className={`h-5 w-5 flex-shrink-0 ${isCollapsed ? '' : 'mr-3'}`} />
                                        {!isCollapsed && <span className="whitespace-nowrap">{item.name}</span>}
                                    </Link>
                                </TooltipTrigger>
                                {isCollapsed && (
                                    <TooltipContent side="right">
                                        {item.name}
                                    </TooltipContent>
                                )}
                            </Tooltip>
                        ))}
                    </TooltipProvider>
                </nav>
                <div className="border-t border-gray-200 p-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="w-full flex justify-center"
                        onClick={() => setIsCollapsed(!isCollapsed)}
                    >
                        {isCollapsed ? <ChevronRight className="h-5 w-5" /> : <ChevronLeft className="h-5 w-5" />}
                    </Button>
                </div>
            </aside>"""

    content = content.replace(old_sidebar, new_sidebar)

    # Add mobile menu trigger to Top Bar
    topbar_start = """<header className="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6">
                    <div className="flex items-center">"""

    topbar_new = """<header className="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 md:px-6">
                    <div className="flex items-center">
                        <Sheet open={isMobileMenuOpen} onOpenChange={setIsMobileMenuOpen}>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon" className="md:hidden mr-2">
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="w-[260px] p-0">
                                <div className="flex h-16 items-center border-b border-gray-200 px-6">
                                    <span className="text-xl font-bold text-gray-900">Client Portal</span>
                                </div>
                                <nav className="flex-1 space-y-2 px-4 py-6">
                                    {modules.map((item) => (
                                        <Link
                                            key={item.name}
                                            href={route(item.route)}
                                            onClick={() => setIsMobileMenuOpen(false)}
                                            className="flex items-center rounded-md bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900"
                                        >
                                            <item.icon className="mr-3 h-5 w-5" />
                                            {item.name}
                                        </Link>
                                    ))}
                                </nav>
                            </SheetContent>
                        </Sheet>"""

    content = content.replace(topbar_start, topbar_new)

    with open('resources/js/Layouts/ClientLayout.tsx', 'w') as f:
        f.write(content)

    print("ClientLayout updated")

update_client_layout()
