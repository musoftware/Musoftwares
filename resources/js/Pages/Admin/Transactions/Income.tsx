import React from 'react';
import TransactionUserCard from './Components/TransactionUserCard';
import { TransactionsPage } from '@/Components/TransactionsPage';

export default function Income({ transactions, filters, filteredUser }) {
    return (
        <TransactionsPage
            type="income"
            titleKey="erp.income_transactions"
            headerKey="erp.transactions"
            descriptionKey="erp.view_all_income_and_related"
            emptyTitleKey="erp.no_transactions_found"
            emptyDescriptionKey="general.try_adjusting_your_search_filters"
            primaryCreateType="receive"
            primaryCreateLabelKey="general.receive"
            amountColorize="green"
            transactions={transactions}
            filters={filters}
            filteredUser={filteredUser}
        >
            {() => filteredUser && <TransactionUserCard user={filteredUser} />}
        </TransactionsPage>
    );
}