import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import ServiceForm from '@/Components/Marketplace/ServiceForm';
import { __ } from '@/lib/i18n';

export default function Edit({ auth, service, categories, currencies }: any) {
    const title = service === null ? __('admin.marketplace_create_service') : __('general.edit_service');

    return (
        <AdminSidebarLayout user={auth?.user} title={title} header={title}>
            <ServiceForm
                auth={auth}
                service={service}
                categories={categories}
                currencies={currencies}
                mode="admin"
                backUrl={route('admin.marketplace.services.all')}
            />
        </AdminSidebarLayout>
    );
}
