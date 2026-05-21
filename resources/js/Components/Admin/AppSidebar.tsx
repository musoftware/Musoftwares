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
  SidebarMenuSub,
  SidebarMenuSubItem,
  SidebarMenuSubButton,
  SidebarHeader,
  SidebarFooter
} from '@/Components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { LayoutDashboard, Users, Building2, DollarSign, Settings, ChevronRight, Briefcase, CreditCard, FileText, Globe } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';

const items = [
  { title: "Dashboard", url: "/admin", icon: LayoutDashboard },
  { 
    title: "User & Content", 
    url: "/admin/users-content", 
    icon: Users,
    subItems: [
        { title: "Users", url: "/admin/clients" },
        { title: "Services Dashboard", url: "/admin/services" },
        { title: "Projects", url: "/admin/projects" },
        { title: "Plans", url: "/admin/plans" },
        { title: "Job Listings", url: "/admin/job-listings" },
        { title: "Blog Articles", url: "/admin/blog-articles" },
        { title: "Landing Pages", url: "/admin/service-landing-pages" },
    ]
  },
  { 
    title: "Finance & Business", 
    url: "/admin/finance", 
    icon: DollarSign,
    subItems: [
        { title: "Unpaid Invoices", url: "/admin/invoices/unpaid" },
        { title: "Archived Invoices", url: "/admin/invoices/archive" },
        { title: "All Invoices", url: "/admin/invoices" },
        { title: "Leads", url: "/admin/leads" },
        { title: "Contracts Manager", url: "/admin/contracts" },
        { title: "Create Quotation", url: "/admin/contracts/create" },
        { title: "Price Calculator", url: "/admin/project-price-calculator" },
        { title: "Sequences", url: "/admin/sequences" },
        { title: "Campaigns", url: "/admin/campaigns" },
        { title: "Costs", url: "/admin/costs" },
        { title: "Recurring Costs", url: "/admin/recurring_costs" },
        { title: "Income", url: "/admin/income" },
        { title: "Recurring Income", url: "/admin/recurring_income" },
        { title: "Hours Calendar", url: "/admin/hours-calendar" },
        { title: "Reports", url: "/admin/reports" },
    ]
  },
  { 
    title: "Operations", 
    url: "/admin/operations", 
    icon: Briefcase,
    subItems: [
        { title: "Tickets", url: "/admin/tickets" },
        { title: "Tasks List", url: "/admin/tasks/as_list" },
        { title: "Task Calendar", url: "/admin/tasks/calendar" },
        { title: "Busy Times", url: "/admin/busy-times" },
        { title: "Employee Todos", url: "/admin/employee-todos" },
        { title: "Job Center", url: "/admin/job-tasks" },
        { title: "Points Control", url: "/admin/points_controller" },
        { title: "Charity", url: "/admin/charity-counter" },
        { title: "KYC Verification", url: "/admin/kyc" },
        { title: "WhatsApp API", url: "/admin/whatsapp" },
        { title: "AI Messages", url: "/admin/whatsapp/ai-chats" },
        { title: "AI Playground", url: "/admin/whatsapp/ai-playground" },
    ]
  },
  { 
    title: "Marketplace", 
    url: "/admin/marketplace", 
    icon: Building2,
    subItems: [
        { title: "All Services", url: "/admin/marketplace/all-services" },
        { title: "Pending Services", url: "/admin/marketplace/pending-services" },
        { title: "Categories", url: "/admin/marketplace/categories" },
        { title: "Orders", url: "/admin/marketplace/orders" },
    ]
  },
  { 
    title: "Seller & Payout", 
    url: "/admin/seller", 
    icon: CreditCard,
    subItems: [
        { title: "Payment Methods", url: "/admin/payment-methods" },
        { title: "Withdraw Requests", url: "/admin/withdraw-requests" },
        { title: "Earning Analyze", url: "/admin/users/earning-analyze" },
        { title: "Private CoWork", url: "/admin/users/co-work" },
        { title: "Vouchers", url: "/admin/vouchers" },
        { title: "Coupons", url: "/admin/coupons" },
    ]
  },
  { 
    title: "System & Settings", 
    url: "/admin/system", 
    icon: Settings,
    subItems: [
        { title: "Musoftware Clients", url: "/admin/musoftware-clients" },
        { title: "Client Manager", url: "/admin/musoftware-client-manager" },
        { title: "Serial Softwares", url: "/admin/serial-softwares" },
        { title: "Serial Devices", url: "/admin/serial-devices" },
        { title: "Software Programs", url: "/admin/memberships/software" },
        { title: "Settings", url: "/admin/settings" },
    ]
  },
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
                const hasSubItems = item.subItems && item.subItems.length > 0;

                if (hasSubItems) {
                    return (
                        <Collapsible
                            key={item.title}
                            asChild
                            defaultOpen={isActive}
                            className="group/collapsible"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuButton tooltip={item.title}>
                                        <item.icon className="h-4 w-4" />
                                        <span>{item.title}</span>
                                        <ChevronRight className="ml-auto h-4 w-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {item.subItems.map((subItem) => {
                                            const isSubActive = url === subItem.url || url.startsWith(subItem.url + '/');
                                            return (
                                                <SidebarMenuSubItem key={subItem.title}>
                                                    <SidebarMenuSubButton asChild isActive={isSubActive}>
                                                        <Link href={subItem.url}>{subItem.title}</Link>
                                                    </SidebarMenuSubButton>
                                                </SidebarMenuSubItem>
                                            );
                                        })}
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    );
                }

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
