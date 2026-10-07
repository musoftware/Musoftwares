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
import { LayoutDashboard, Users, Building2, DollarSign, Settings, ChevronRight, Briefcase, CreditCard, Link2, ListTodo, BarChart3, Wand2, Mail, BookOpen, LayoutTemplate } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { __ } from '@/lib/i18n';

type MenuItem = {
  /** Translation key for the menu label. */
  title: string;
  url: string;
  icon: any;
  subItems?: { title: string; url: string; fullReload?: boolean; badgeCountKey?: string }[];
};

const items: MenuItem[] = [
  { title: "admin.sidebar_dashboard", url: "/admin/dashboard", icon: LayoutDashboard },
  {
    title: "admin.sidebar_user_content",
    url: "/admin/users-content",
    icon: Users,
    subItems: [
        { title: "admin.sidebar_users", url: "/admin/users" },
        { title: "admin.sidebar_projects", url: "/admin/projects" },
        { title: "admin.sidebar_plans", url: "/admin/plans" },
        { title: "admin.sidebar_blog_articles", url: "/admin/blog-articles" },
        { title: "admin.sidebar_prompt_gallery", url: "/admin/prompts" },
    ]
  },
  {
    title: "admin.sidebar_tasks",
    url: "/admin/tasks",
    icon: ListTodo,
        subItems: [
            { title: "admin.sidebar_pending_tasks", url: "/admin/tasks/pending", badgeCountKey: "pending_tasks" },
            { title: "admin.sidebar_tasks_list", url: "/admin/tasks/as_list" },
            { title: "admin.sidebar_board_explorer", url: "/admin/tasks/board-explorer" },
            { title: "admin.sidebar_task_calendar", url: "/admin/tasks/calendar" },
            { title: "admin.sidebar_client_tasks", url: "/admin/tasks/client-tasks" },
            { title: "admin.sidebar_employee_todos", url: "/admin/employee-todos" },
            { title: "admin.sidebar_tickets", url: "/admin/tickets", badgeCountKey: "open_tickets" },
        ]
  },
  {
    title: "admin.sidebar_invoices",
    url: "/admin/invoices", 
    icon: DollarSign,
    subItems: [
        { title: "admin.sidebar_clients_dues_board", url: "/admin/invoices/dues" },
        { title: "admin.sidebar_unpaid_invoices", url: "/admin/invoices/unpaid" },
        { title: "admin.sidebar_suspended_invoices", url: "/admin/invoices/suspended" },
        { title: "admin.sidebar_archived_invoices", url: "/admin/invoices/archive" },
        { title: "admin.sidebar_all_invoices", url: "/admin/invoices" },
    ]
  },
  { 
    title: "admin.sidebar_finance_business", 
    url: "/admin/finance", 
    icon: DollarSign,
    subItems: [
        { title: "admin.sidebar_costs", url: "/admin/business/costs" },
        { title: "admin.sidebar_recurring_costs", url: "/admin/business/recurring/costs" },
        { title: "admin.sidebar_income", url: "/admin/business/income" },
        { title: "admin.sidebar_recurring_income", url: "/admin/business/recurring/income" },
        { title: "admin.sidebar_recurring_salaries", url: "/admin/business/recurring/salaries" },
        { title: "admin.sidebar_recurring_invoices", url: "/admin/business/recurring/invoices" },
        { title: "admin.sidebar_reports", url: "/admin/business/reports" },
        { title: "admin.sidebar_balance", url: "/admin/business/balance-report" },
        { title: "admin.sidebar_payment_links", url: "/admin/payment-links" },
        { title: "admin.sidebar_hours_calendar", url: "/admin/hours-calendar" },
        { title: "admin.sidebar_transactions", url: "/admin/transactions?type=income" },
        { title: "admin.sidebar_cost_transactions", url: "/admin/transactions?type=cost" },
        { title: "admin.sidebar_currencies", url: "/admin/currencies" },
        { title: "admin.sidebar_currency_exchanges", url: "/admin/currency-exchanges" },
    ]
  },
  { 
    title: "admin.sidebar_operations", 
    url: "/admin/operations", 
    icon: Briefcase,
    subItems: [
        { title: "admin.sidebar_bulk_notify", url: "/admin/notifications/broadcast" },
        { title: "admin.sidebar_website_services", url: "/admin/website-services" },
        { title: "admin.sidebar_micro_services", url: "/admin/micro-services" },
        { title: "admin.sidebar_guest_tickets", url: "/admin/guest-tickets" },
        { title: "admin.sidebar_tickets", url: "/admin/tickets" },
        { title: "admin.sidebar_busy_times", url: "/admin/busy-times" },
        { title: "admin.sidebar_points_control", url: "/admin/points_controller" },
        { title: "admin.sidebar_point_packages", url: "/admin/point-packages" },
        { title: "admin.sidebar_charity", url: "/admin/charity-counter" },
        { title: "admin.sidebar_kyc_verification", url: "/admin/kyc" },
        { title: "admin.sidebar_contracts", url: "/admin/contracts" },
        { title: "admin.sidebar_contract_price_list", url: "/admin/contract-price-items" },
        { title: "admin.sidebar_project_cost_estimator", url: "/estimator" },
        { title: "admin.sidebar_commissions", url: "/admin/commissions" },
    ]
  },
  { 
    title: "admin.sidebar_marketplace", 
    url: "/admin/marketplace", 
    icon: Building2,
    subItems: [
        { title: "admin.sidebar_software_tools_store", url: "/admin/store-tools" },
        { title: "admin.sidebar_digital_books", url: "/admin/digital-products" },
        { title: "admin.sidebar_upload_book_pdf", url: "/admin/digital-products/create" },
        { title: "admin.sidebar_book_categories", url: "/admin/digital-products/categories" },
        { title: "admin.sidebar_quotations", url: "/admin/marketplace/quotations" },
        { title: "admin.sidebar_service_playbooks", url: "/admin/marketplace/service-playbooks" },
        { title: "admin.sidebar_all_services", url: "/admin/marketplace/all-services" },
        { title: "admin.sidebar_pending_services", url: "/admin/marketplace/pending-services" },
        { title: "admin.sidebar_categories", url: "/admin/marketplace/categories" },
        { title: "admin.sidebar_orders", url: "/admin/marketplace/orders" },
        { title: "admin.sidebar_landing_pages", url: "/admin/marketplace/service-landing-pages" },
    ]
  },
  { 
    title: "admin.sidebar_seller_payout", 
    url: "/admin/seller", 
    icon: CreditCard,
    subItems: [
        { title: "admin.sidebar_payouts", url: "/admin/payouts" },
        { title: "admin.sidebar_payment_methods", url: "/admin/payment-methods" },
        { title: "admin.sidebar_withdraw_requests", url: "/admin/withdraw-requests" },
        { title: "admin.sidebar_earning_analyze", url: "/admin/users/earning-analyze" },
        { title: "admin.sidebar_private_cowork", url: "/admin/users/co-work" },
        { title: "admin.sidebar_vouchers", url: "/admin/vouchers" },
        { title: "admin.sidebar_coupons", url: "/admin/coupons" },
    ]
  },
  { 
    title: "admin.sidebar_short_links", 
    url: "/admin/shortlinks", 
    icon: Link2,
  },
  { 
    title: "admin.sidebar_email_templates", 
    url: "/admin/email-templates", 
    icon: LayoutTemplate,
  },
  { 
    title: "admin.sidebar_outgoing_emails", 
    url: "/admin/outgoing-emails", 
    icon: Mail,
  },
  { 
    title: "admin.sidebar_system_settings", 
    url: "/admin/system", 
    icon: Settings,
    subItems: [
        { title: "admin.sidebar_software_tools_store", url: "/admin/store-tools" },
        { title: "admin.sidebar_user_assignments", url: "/admin/serial-user-devices" },
        { title: "admin.sidebar_serial_softwares", url: "/admin/serial-softwares" },
        { title: "admin.sidebar_serial_devices", url: "/admin/serial-devices" },
        { title: "admin.sidebar_quick_activate", url: "/admin/serial-devices-quick-activate" },
        
        { title: "admin.sidebar_partner_gateway_b2b_api", url: "/admin/partner-gateway" },
        { title: "admin.sidebar_settings", url: "/admin/settings" },
        { title: "admin.sidebar_security_rate_limits", url: "/admin/settings/security" },
    ]
  },
];

const ACCOUNTANT_GROUP_URLS = ['/admin/invoices', '/admin/finance', '/admin/seller'];
const OPERATIONS_URL = '/admin/operations';
const SUPPORT_AGENT_URLS = ['/admin/tickets', '/admin/guest-tickets'];
const MODERATOR_URLS = ['/admin/tickets'];

export function AppSidebar() {
  const { url, props } = usePage();
  const { auth, admin_counts } = props as any;
  const userRoles = auth?.user?.roles || [];
  
  const isAdmin = userRoles.includes('admin') || userRoles.includes('super_admin');
  const isAccountant = userRoles.includes('accountant') && !isAdmin;
  const isSupportAgent = userRoles.includes('support_agent') && !isAdmin;
  const isOnlyModerator = userRoles.includes('moderator') && !isAdmin;

  let visibleItems = items;

  if (isAccountant) {
      visibleItems = items.filter(item => 
          ACCOUNTANT_GROUP_URLS.includes(item.url)
      );
  } else if (isSupportAgent) {
      visibleItems = items.map(item => {
          if (item.url === OPERATIONS_URL) {
              return {
                  ...item,
                  subItems: item.subItems?.filter(sub => SUPPORT_AGENT_URLS.includes(sub.url))
              };
          }
          return null;
      }).filter(Boolean) as typeof items;
  } else if (isOnlyModerator) {
      visibleItems = items.map(item => {
          if (item.url === OPERATIONS_URL) {
              return {
                  ...item,
                  subItems: item.subItems?.filter(sub => MODERATOR_URLS.includes(sub.url))
              };
          }
          return null;
      }).filter(Boolean) as typeof items;
  }

  return (
    <Sidebar>
      <SidebarHeader className="border-b border-border/50 p-4 bg-sidebar">
        <a href="/" className="flex items-center gap-2 px-2">
            <ApplicationLogo className="w-6 h-6 text-slate-900 fill-current" />
            <span className="font-semibold text-lg tracking-tight">{__('general.admin_panel')}</span>
        </a>
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
                                        <SidebarMenuButton tooltip={__(item.title)} />
                                    }
                                >
                                    <item.icon className="h-4 w-4" />
                                    <span>{__(item.title)}</span>
                                    <ChevronRight className="ms-auto h-4 w-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {item.subItems?.map((subItem) => {
                                            const isSubActive = url === subItem.url || url.startsWith(subItem.url + '/');
                                            const count = subItem.badgeCountKey ? admin_counts?.[subItem.badgeCountKey] : null;
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
                                                        <span className="flex items-center justify-between w-full">
                                                            <span>{__(subItem.title)}</span>
                                                            {typeof count === 'number' && count > 0 && (
                                                                <span className="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-semibold rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900 leading-none">
                                                                    {count}
                                                                </span>
                                                            )}
                                                        </span>
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
                      tooltip={__(item.title)}
                      render={<Link href={item.url} />}
                    >
                      <item.icon className="h-4 w-4" />
                      <span>{__(item.title)}</span>
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
            {__('admin.sidebar_footer_copyright', { year: new Date().getFullYear() })}
        </div>
      </SidebarFooter>
    </Sidebar>
  );
}
