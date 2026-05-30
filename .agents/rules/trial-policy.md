# Rule: Trial Policy For Modules and Tools

## Problem Statement
When giving users free trials, the trial should only apply to core operational modules (like ERP, CRM, etc.), but MUST NEVER be applied to standalone "Tools" or services that have hard server costs, point costs, or external API costs. Failing to enforce this distinction could lead to abuse of paid tools for free.

## Rules & Guidelines

### 1. The 14-Day Free Trial Policy
- All newly registered users are eligible for a **14-day free trial**.
- The trial **only applies to core modules** (e.g., ERP, CRM, POS, Booking, etc.) defined in the system.

### 2. Never Apply Trials to Tools
- **NEVER** apply a trial, a free grace period, or a free subscription to **Tools**.
- Tools are premium utilities that must be paid for via their respective points or dedicated subscriptions. They are strictly excluded from the default platform free trial.

### 3. Backend Implementation (Registration)
- In the `RegisteredUserController` or any registration flow, when automatically assigning trial access, loop over the available modules (e.g. from `config('saas.modules')`) but **explicitly skip tools**.
- Example logic:
  ```php
  $modules = config('saas.modules', []);
  foreach ($modules as $slug => $price) {
      if ($slug === 'tool') continue; // NEVER trial tools

      \App\Models\UserSubscription::create([
          'user_id' => $user->id,
          'object' => $slug,
          'status' => 'active',
          'started_at' => now(),
          'expires_at' => now()->addDays(14),
          'auto_renew' => false,
      ]);
  }
  ```

### 4. UI Copy and Expectations
- Ensure the landing page (`Home.jsx`) and the subscription page (`PricingBuilder.tsx`) explicitly clarify this distinction.
- Copy must state: **"No credit card required for 14-day trial on modules (Tools excluded)."** or **"No credit card required for 14-day trial on ERP & modules (Not applicable for tools)."**
- Never promise "Free trial for everything" on the marketing pages to avoid setting the wrong expectations regarding the paid tools.
