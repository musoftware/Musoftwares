import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import ServiceForm from '@/Components/Marketplace/ServiceForm';

export default function CreateService({ auth, categories, currencies, needsReview, adminUrl }: any) {
    return (
        <MarketplaceLayout>
            <ServiceForm
                auth={auth}
                service={null}
                categories={categories}
                currencies={currencies}
                mode="seller"
                backUrl={route('marketplace.home', { mode: 'seller' })}
                needsReview={needsReview}
                adminUrl={adminUrl}
            />
        </MarketplaceLayout>
    );
}
