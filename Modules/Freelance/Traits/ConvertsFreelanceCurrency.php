<?php

namespace Modules\Freelance\Traits;

use App\Models\CurrenciesExchange;
use App\Models\Currency;

trait ConvertsFreelanceCurrency
{
    protected $_freelanceCurrencyCache = [];

    protected function getFreelanceCurrencyModel($id)
    {
        if (!isset($this->_freelanceCurrencyCache[$id])) {
            $this->_freelanceCurrencyCache[$id] = Currency::find($id);
        }
        return $this->_freelanceCurrencyCache[$id];
    }

    /**
     * Convert a job's budget to the user's currency.
     */
    protected function convertJobCurrency($job, $userCurrencyId)
    {
        if (!$job || !$job->currency_id || !$userCurrencyId || $job->currency_id == $userCurrencyId) {
            return $job;
        }

        $job->budget = CurrenciesExchange::RateToday($job->budget, $job->currency_id, $userCurrencyId);
        $job->currency_id = $userCurrencyId;
        $job->setRelation('currency', $this->getFreelanceCurrencyModel($userCurrencyId));

        return $job;
    }

    /**
     * Convert a proposal's bid amount to the user's currency.
     */
    protected function convertProposalCurrency($proposal, $userCurrencyId)
    {
        if (!$proposal || !$proposal->currency_id || !$userCurrencyId || $proposal->currency_id == $userCurrencyId) {
            return $proposal;
        }

        $proposal->bid_amount = CurrenciesExchange::RateToday($proposal->bid_amount, $proposal->currency_id, $userCurrencyId);
        $proposal->currency_id = $userCurrencyId;
        $proposal->setRelation('currency', $this->getFreelanceCurrencyModel($userCurrencyId));

        // Convert the nested job if it exists
        if ($proposal->relationLoaded('job') && $proposal->job) {
            $this->convertJobCurrency($proposal->job, $userCurrencyId);
        }

        return $proposal;
    }

    /**
     * Convert a contract's amount to the user's currency.
     */
    protected function convertContractCurrency($contract, $userCurrencyId)
    {
        if (!$contract || !$contract->currency_id || !$userCurrencyId || $contract->currency_id == $userCurrencyId) {
            return $contract;
        }

        if (in_array($contract->status, ['active', 'completed', 'disputed'])) {
            $date = $contract->started_at ?? $contract->created_at;
            $contract->amount = CurrenciesExchange::RateByDate($date, $contract->amount, $contract->currency_id, $userCurrencyId);
        } else {
            $contract->amount = CurrenciesExchange::RateToday($contract->amount, $contract->currency_id, $userCurrencyId);
        }

        $contract->currency_id = $userCurrencyId;
        $contract->setRelation('currency', $this->getFreelanceCurrencyModel($userCurrencyId));

        // Convert the nested job if it exists
        if ($contract->relationLoaded('job') && $contract->job) {
            $this->convertJobCurrency($contract->job, $userCurrencyId);
        }

        return $contract;
    }
}
