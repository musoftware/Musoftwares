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
import { LayoutDashboard, Users, Building2, DollarSign, Settings, ChevronRight, Briefcase, CreditCard, Link2, ListTodo, BarChart3, Wand2 } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { __ } from '@/lib/i18n';

type MenuItem = {
  title: string;
  url: string;
  icon: any;
  subItems?: { title: string; url: string; fullReload?: boolean }[];
};

const items: MenuItem[] = [
  { title: "Dashboard", url: "/admin/dashboard", icon: LayoutDashboard },
  {
    title: "User & Content",
    url: "/admin/users-content",
    icon: Users,
    subItems: [
        { title: "Users", url: "/admin/users" },

        { title: "Projects", url: "/admin/projects" },
        { title: "Plans", url: "/admin/plans" },
        { title: "Blog Articles", url: "/admin/blog-articles" },
    ]
  },
  {
    title: "Tasks",
    url: "/admin/tasks",
    icon: ListTodo,
        subItems: [
            { title: "Tasks List", url: "/admin/tasks/as_list" },
            { title: "Board Explorer", url: "/admin/tasks/board-explorer" },
            { title: "Task Calendar", url: "/admin/tasks/calendar" },
            { title: "Client Tasks", url: "/admin/tasks/client-tasks" },
            { title: "Employee Todos", url: "/admin/employee-todos" },
        ]
  },
  {
    title: "Invoices",
    url: "/admin/invoices", 
    icon: DollarSign,
    subItems: [
        { title: "Unpaid Invoices", url: "/admin/invoices/unpaid" },
        { title: "Archived Invoices", url: "/admin/invoices/archive" },
        { title: "All Invoices", url: "/admin/invoices" },
    ]
  },
  { 
    title: "Finance & Business", 
    url: "/admin/finance", 
    icon: DollarSign,
    subItems: [
        { title: "Costs", url: "/admin/business/costs" },
        { title: "Recurring Costs", url: "/admin/business/recurring/costs" },
        { title: "Income", url: "/admin/business/income" },
        { title: "Recurring Income", url: "/admin/business/recurring/income" },
        { title: "Recurring Salaries", url: "/admin/business/recurring/salaries" },
        { title: "Recurring Invoices", url: "/admin/business/recurring/invoices" },
        { title: "Reports", url: "/admin/business/reports" },
        { title: "Balance", url: "/admin/business/balance-report" },
        { title: "Payment Links", url: "/admin/payment-links" },
        { title: "Hours Calendar", url: "/admin/hours-calendar" },
        { title: "Transactions", url: "/admin/transactions?type=income" },
        { title: "Cost Transactions", url: "/admin/transactions?type=cost" },
        { title: "Currencies", url: "/admin/currencies" },
        { title: "Currency Exchanges", url: "/admin/currency-exchanges" },
    ]
  },
  { 
    title: "Operations", 
    url: "/admin/operations", 
    icon: Briefcase,
    subItems: [
        { title: "Bulk Notify", url: "/admin/notifications/broadcast" },
        { title: "Website Services", url: "/admin/website-services" },
        { title: "Guest Tickets", url: "/admin/guest-tickets" },
        { title: "Tickets", url: "/admin/tickets" },
        { title: "Busy Times", url: "/admin/busy-times" },
        { title: "Points Control", url: "/admin/points_controller" },
        { title: "Point Packages", url: "/admin/point-packages" },
        { title: "Charity", url: "/admin/charity-counter" },
        { title: "KYC Verification", url: "/admin/kyc" },
        { title: "Contracts", url: "/admin/contracts" },
        { title: "Contract Price List", url: "/admin/contract-price-items" },

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
        { title: "Landing Pages", url: "/admin/marketplace/service-landing-pages" },
    ]
  },
  { 
    title: "Seller & Payout", 
    url: "/admin/seller", 
    icon: CreditCard,
    subItems: [
        { title: "Payouts", url: "/admin/payouts" },
        { title: "Payment Methods", url: "/admin/payment-methods" },
        { title: "Withdraw Requests", url: "/admin/withdraw-requests" },
        { title: "Earning Analyze", url: "/admin/users/earning-analyze" },
        { title: "Private CoWork", url: "/admin/users/co-work" },
        { title: "Vouchers", url: "/admin/vouchers" },
        { title: "Coupons", url: "/admin/coupons" },
    ]
  },
  { 
    title: "Short Links", 
    url: "/admin/shortlinks", 
    icon: Link2,
  },
  { 
    title: "System & Settings", 
    url: "/admin/system", 
    icon: Settings,
    subItems: [
        { title: "Musoftware Clients", url: "/admin/musoftware-clients" },
        { title: "Serial Softwares", url: "/admin/serial-softwares" },
        { title: "Serial Devices", url: "/admin/serial-devices" },
        { title: "Settings", url: "/admin/settings" },
        { title: "Security & Rate Limits", url: "/admin/settings/security" },
    ]
  },
];

export function AppSidebar() {
  const { url, props } = usePage();
  const { auth } = props as any;
  const userRoles = auth?.user?.roles || [];
  
  const isAdmin = userRoles.includes('admin') || userRoles.includes('super_admin');
  const isAccountant = userRoles.includes('accountant') && !isAdmin;
  const isSupportAgent = userRoles.includes('support_agent') && !isAdmin;
  const isOnlyModerator = userRoles.includes('moderator') && !isAdmin;

  let visibleItems = items;

  if (isAccountant) {
      visibleItems = items.filter(item => 
          ['Invoices', 'Finance & Business', 'Seller & Payout'].includes(item.title)
      );
  } else if (isSupportAgent) {
      visibleItems = items.map(item => {
          if (item.title === 'Operations') {
              return {
                  ...item,
                  subItems: item.subItems?.filter(sub => sub.title === 'Tickets' || sub.title === 'Guest Tickets')
              };
          }
          return null;
      }).filter(Boolean) as typeof items;
  } else if (isOnlyModerator) {
      visibleItems = items.map(item => {
          if (item.title === 'Operations') {
              return {
                  ...item,
                  subItems: item.subItems?.filter(sub => sub.title === 'Tickets')
              };
          }
          return null;
      }).filter(Boolean) as typeof items;
  }

  return (
    <Sidebar>
      <SidebarHeader className="border-b border-border/50 p-4 bg-sidebar">
        <Link href="/" className="flex items-center gap-2 px-2">
            <ApplicationLogo className="w-6 h-6 text-slate-900 fill-current" />
            <span className="font-semibold text-lg tracking-tight">{__('general.admin_panel')}</span>
        </Link>
      </SidebarHeader>
      <SidebarContent className="p-2">
        <SidebarGroup>
          <SidebarGroupLabel className="px-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">{__('general.application')}</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              {visibleItems.map((item) => {
                const isActive = url === item.url || url.startsWith(item.url + '/');
                const hasSubItems = item.subItems && item.subItems.length > 0;
                const isGroupActive = isActive || (hasSubItems && item.subItems?.some(subItem => url === subItem.url || url.startsWith(subItem.url + '/')));

                if (hasSubItems) {
                    return (
                        <Collapsible
                            key={item.title}
                            defaultOpen={isGroupActive}
                            className="group/collapsible"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger
                                    render={
                                        <SidebarMenuButton tooltip={item.title} />
                                    }
                                >
                                    <item.icon className="h-4 w-4" />
                                    <span>{item.title}</span>
                                    <ChevronRight className="ms-auto h-4 w-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {item.subItems?.map((subItem) => {
                                            const isSubActive = url === subItem.url || url.startsWith(subItem.url + '/');
                                            return (
                                                <SidebarMenuSubItem key={subItem.title}>
                                                    <SidebarMenuSubButton
                                                        isActive={isSubActive}
                                                        render={
                                                            subItem.fullReload
                                                                ? <a href={subItem.url} />
                                                                : <Link href={subItem.url} />
                                                        }
                                                    >
                                                        {subItem.title}
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
                    <SidebarMenuButton
                      isActive={isActive}
                      tooltip={item.title}
                      render={<Link href={item.url} />}
                    >
                      <item.icon className="h-4 w-4" />
                      <span>{item.title}</span>
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
