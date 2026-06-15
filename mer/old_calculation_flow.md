# Old Service Payment Calculation Flow

This diagram illustrates how the `runPayServiceCalculation` function worked in the old code.

```mermaid
flowchart TD
    Start([Start Calculation]) --> FetchExchangeRate[Convert Service Amount to Invoice Currency\n(ex_cost)]
    FetchExchangeRate --> SetInitialTotalCost[total_cost = ex_cost]
    SetInitialTotalCost --> ApplySourceFees{Source Payment Gateway?}

    %% Source Fees
    ApplySourceFees -->|Wallet| SourceWallet[total_cost = total_cost / 0.99<br>+1% approx]
    ApplySourceFees -->|PayPal| SourcePayPal[total_cost = total_cost / 0.95<br>+5% approx]
    ApplySourceFees -->|Gumroad| SourceGumroad[total_cost = total_cost / 0.86<br>+14% approx]
    ApplySourceFees -->|Payoneer| SourcePayoneer[total_cost = total_cost / 0.97<br>+3% approx]
    ApplySourceFees -->|Other| PostSourceFees

    SourceWallet --> PostSourceFees
    SourcePayPal --> PostSourceFees
    SourceGumroad --> PostSourceFees
    SourcePayoneer --> PostSourceFees

    PostSourceFees --> ApplyDestFees{Destination Payment Gateway?}

    %% Destination Fees
    ApplyDestFees -->|CIB| DestCIB{Is Input Currency USD?}
    DestCIB -->|Yes| DestCIBUSD[total_cost = total_cost * 1.05 / 0.98]
    DestCIB -->|No| DestCIBEGP[total_cost = total_cost / 0.956 / 0.95]
    
    ApplyDestFees -->|CIB Swype| DestCIBSwype[total_cost = total_cost * 1.05 / 0.98\nCalculate 12 months installment at 2.67%\ntotal_cost = monthly * 12]
    
    ApplyDestFees -->|Alex| DestAlex[total_cost = total_cost / 0.956 / 0.94]
    
    ApplyDestFees -->|Redot| DestRedot[Fetch Gold World Price<br>Fetch USD Exchange Rate<br>Fetch Gold Price 21k]
    DestRedot --> DestRedotCalc[OVERWRITE total_cost = <br>service_amount * (price_21k / usdPrice) <br>/ 0.956 / 0.965]
    
    ApplyDestFees -->|Wallet| DestWallet[total_cost = total_cost / 0.99]
    
    ApplyDestFees -->|Other| CalcFinalCost

    DestCIBUSD --> CalcFinalCost
    DestCIBEGP --> CalcFinalCost
    DestCIBSwype --> CalcFinalCost
    DestAlex --> CalcFinalCost
    DestRedotCalc --> CalcFinalCost
    DestWallet --> CalcFinalCost

    CalcFinalCost[cost = total_cost] --> ApplyRevenueTier{Revenue Tier?}

    %% Revenue Tier
    ApplyRevenueTier -->|3| Rev3[total = cost / 0.75]
    ApplyRevenueTier -->|2| Rev2[total = cost / 0.825]
    ApplyRevenueTier -->|1| Rev1[total = cost / 0.8875]
    ApplyRevenueTier -->|0| Rev0[total = cost / 0.9525]
    ApplyRevenueTier -->|-1| RevMinus1[total = cost / 0.98875]
    ApplyRevenueTier -->|default| RevDefault[total = cost / 0.9825]

    Rev3 --> CalcUSD
    Rev2 --> CalcUSD
    Rev1 --> CalcUSD
    Rev0 --> CalcUSD
    RevMinus1 --> CalcUSD
    RevDefault --> CalcUSD

    CalcUSD[Convert final cost to USD<br>total_usd = cost_in_usd / 0.80] --> End([Return cost, total, total_usd])

```

## Identified Issues with the Old Flow:
1. **Redot Override**: If the destination is `redot`, the `total_cost` is recalculated directly from `service_amount`, completely wiping out any fees applied during the Source step (e.g., PayPal fees).
2. **Missing UI Mappings**: The frontend `Show.tsx` sends values like `bank_transfer` and `cash` which are completely ignored by this calculation.
3. **Currency ID Ambiguity**: The code assumes `currency == 2` means USD in the CIB check, but later uses `1` as the USD ID when calculating `total_usd`.
