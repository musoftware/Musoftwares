import React from 'react';
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarHeader,
  SidebarFooter
} from '@/Components/ui/sidebar';
import { LayoutDashboard, Users, Building2, DollarSign, Settings } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';

const items = [
  { title: "Dashboard", url: "/admin", icon: LayoutDashboard },
  { title: "Clients", url: "/admin/clients", icon: Users },
  { title: "Marketplace", url: "/admin/marketplace", icon: Building2 },
  { title: "Reports", url: "/admin/reports", icon: DollarSign },
  { title: "Settings", url: "/admin/settings", icon: Settings },
];

export function AppSidebar() {
  const { url } = usePage();

  return (
    <Sidebar>
      <SidebarHeader className="border-b border-border/50 p-4 bg-sidebar">
        <Link href="/" className="flex items-center gap-2 px-2">
            <ApplicationLogo className="w-6 h-6 text-indigo-600 fill-current" />
            <span className="font-semibold text-lg tracking-tight">Admin Panel</span>
        </Link>
      </SidebarHeader>
      <SidebarContent className="p-2">
        <SidebarGroup>
          <SidebarGroupLabel className="px-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Application</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              {items.map((item) => {
                const isActive = url === item.url || url.startsWith(item.url + '/');
                return (
                  <SidebarMenuItem key={item.title}>
                    <SidebarMenuButton asChild isActive={isActive} tooltip={item.title}>
                      <Link href={item.url} className="flex items-center gap-3 px-3 py-2">
                        <item.icon className="h-4 w-4" />
                        <span>{item.title}</span>
                      </Link>
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                );
              })}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>
      <SidebarFooter className="p-4">
        <div className="text-xs text-muted-foreground/70 text-center">
            Musoftware Admin &copy; {new Date().getFullYear()}
        </div>
      </SidebarFooter>
    </Sidebar>
  );
}
