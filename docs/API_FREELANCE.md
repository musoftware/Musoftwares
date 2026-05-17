# Freelance Marketplace API

The Freelance module manages automated skill matching, job postings, bidding proposals, smart contract management, and a marketplace point-based bidding system.

Base Endpoint: `/freelance/api`

## Jobs API

### List Active Job Postings
`GET /freelance/api/jobs`

**Query Parameters:**
- `skill_id`: integer (Filter by specific required skill)
- `budget_min`: decimal
- `budget_max`: decimal
- `type`: string (`fixed` | `hourly`)
- `status`: string (`open` | `in_progress` | `completed`)
- `sort`: string (`-created_at`, `budget_max`, `expires_at`)
- `page`: integer
- `per_page`: integer (`15` | `30`)

**Response:** (`200 OK`)
```json
{
  "data": [
    {
      "id": 10,
      "title": "Senior Laravel Monolith Architect",
      "client": {
        "id": 1,
        "name": "Acme Corp"
      },
      "budget_min": 1500.00,
      "budget_max": 3500.00,
      "amount_currency": "USD",
      "type": "fixed",
      "status": "open",
      "points_cost": 4,
      "skills": [
        {"id": 1, "name": "Laravel", "slug": "laravel"},
        {"id": 2, "name": "React", "slug": "react"}
      ],
      "proposals_count": 8,
      "expires_at": "2024-02-15T00:00:00Z",
      "created_at": "2024-01-15T10:00:00Z"
    }
  ],
  "meta": { "total": 120 }
}
```

### Get Job Details
`GET /freelance/api/jobs/{id}`

Returns complete job specification, required skills, and (if authenticated as the job owner) the list of submitted proposals.

### Create Job Posting
`POST /freelance/api/jobs`

**Note on Point Deductions:** Posting a job requires the client account to possess sufficient bidding points or wallet balance to cover the `points_cost` required by platform configuration.

**Request Body:**
```json
{
  "title": "React 18 SPA Migration for SaaS",
  "description": "We are seeking an expert developer to migrate our legacy dashboard...",
  "skills": [2, 5, 12],
  "budget_min": 1000.00,
  "budget_max": 2500.00,
  "amount_currency": "USD",
  "type": "fixed",
  "expires_in_days": 30
}
```

**Response:** (`201 Created`)
```json
{
  "data": {
    "id": 11,
    "title": "React 18 SPA Migration for SaaS",
    "status": "open",
    "points_cost": 5
  },
  "message": "Job posting published successfully. 5 points were deducted from your balance."
}
```

### Update Job Posting
`PATCH /freelance/api/jobs/{id}`

Allows updates to title, description, and budget. Only editable when `status` is `draft` or `open` with zero proposals submitted.

### Delete Job Posting
`DELETE /freelance/api/jobs/{id}`

Deletes a draft or unbid job posting. If points were deducted during publishing, they are automatically refunded to the user's point balance upon successful deletion.

---

## Proposals API

### Submit Proposal
`POST /freelance/api/proposals`

**Request Body:**
```json
{
  "job_id": 10,
  "price": 2800.00,
  "delivery_days": 14,
  "cover_letter": "I have successfully structured dozens of Laravel modular monoliths..."
}
```

**Response:** (`201 Created`)
```json
{
  "data": {
    "id": 50,
    "job_id": 10,
    "status": "pending",
    "points_deducted": 4
  },
  "message": "Proposal submitted successfully."
}
```

### List Proposals for a Job
`GET /freelance/api/jobs/{id}/proposals`

Accessible strictly by the client who created the job posting.

### Accept Proposal & Generate Contract
`PATCH /freelance/api/proposals/{id}/accept`

**Platform Actions Triggered:**
1. The proposal status is updated to `accepted`.
2. All other competing proposals on the job are marked as `rejected`.
3. A legally binding active record is written to `freelance_contracts`.
4. The client's wallet balance is checked for escrow availability and locked.
5. A real-time chat conversation channel is instantiated between the client and freelancer.

**Response:** (`200 OK`)
```json
{
  "data": {
    "contract_id": 12,
    "job_id": 10,
    "status": "active"
  },
  "message": "Proposal accepted. Escrow secured and contract activated."
}
```

### Reject Proposal
`PATCH /freelance/api/proposals/{id}/reject`

Marks the proposal as `rejected`.

---

## Contracts API

### List User Contracts
`GET /freelance/api/contracts`

Returns all active, delivered, or completed contracts where the authenticated user is either the client or freelancer.

### Get Contract Details
`GET /freelance/api/contracts/{id}`

### Submit Work Delivery (Freelancer Action)
`POST /freelance/api/contracts/{id}/deliver`

**Request Body:**
```json
{
  "description": "Completed all 15 endpoints and fully tested WebSocket synchronization.",
  "attachments": ["/storage/deliveries/zip_archive_991.zip"]
}
```

**Response:** (`200 OK` - Status updates to `delivered`).

### Complete Contract (Client Action)
`POST /freelance/api/contracts/{id}/complete`

Releases locked escrow funds from the client's wallet and credits the freelancer's wallet balance after deducting platform commission.

---

## Skills API

### List All Platform Skills
`GET /freelance/api/skills`

### Get Authenticated User Skills
`GET /freelance/api/user-skills`

### Add Skill to User Profile
`POST /freelance/api/user-skills`

**Request Body:**
```json
{
  "skill_id": 1,
  "level": "expert"
}
```

### Remove Skill from User Profile
`DELETE /freelance/api/user-skills/{skillId}`

---

## Points & Bidding Packages API

### Get Point Balance
`GET /freelance/api/points/balance`

**Response:** (`200 OK`)
```json
{
  "data": {
    "user_id": 8,
    "balance": 45,
    "reserved": 0
  }
}
```

### List Point Packages
`GET /freelance/api/points/packages`

### Purchase Point Package
`POST /freelance/api/points/purchase`

**Request Body:**
```json
{
  "package_id": 3
}
```

**Response:** (`200 OK`)
```json
{
  "data": {
    "new_point_balance": 145,
    "amount_debited_usd": 25.00
  },
  "message": "Point package successfully purchased and added to account balance."
}
```
