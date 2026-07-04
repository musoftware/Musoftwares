import React from 'react';
import TransactionUserCard from './Components/TransactionUserCard';
import { TransactionsPage } from '@/Components/TransactionsPage';

export default function Cost({ transactions, filters, filteredUser }) {
    return (
        <TransactionsPage
            type="cost"
            titleKey="erp.cost_transactions"
            headerKey="erp.transactions"
            descriptionKey="erp.view_all_cost_transactions"
            emptyTitleKey="erp.no_transactions_found"
            emptyDescriptionKey="general.try_adjusting_your_search_filters"
            primaryCreateType="receive"
            primaryCreateLabelKey="general.receive"
            amountColorize="red"
            transactions={transactions}
            filters={filters}
            filteredUser={filteredUser}
        >
            {() => filteredUser && <TransactionUserCard user={filteredUser} />}
        </TransactionsPage>
    );
}