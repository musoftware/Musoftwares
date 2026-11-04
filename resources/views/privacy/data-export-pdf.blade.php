<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('privacy_compliance.export_report_title') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1d1d1f;
            line-height: 1.5;
        }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 13px; margin-top: 18px; margin-bottom: 6px; border-bottom: 1px solid #d2d2d7; padding-bottom: 4px; }
        .meta { font-size: 10px; color: #515154; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #d2d2d7; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background-color: #f5f5f7; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Musoftwares — Personal Data Portability Report</h1>
    <div class="meta">
        Exported (Cairo Time): {{ $payload['exported_at_cairo'] ?? '' }}<br>
        Legal Basis: {{ $payload['legal_framework'] ?? 'Egyptian PDPL No. 151 of 2020' }}
    </div>

    <h2>1. Account Profile</h2>
    <table>
        <tbody>
            @foreach(($payload['account'] ?? []) as $key => $val)
                <tr>
                    <th style="width: 32%;">{{ $key }}</th>
                    <td>{{ is_scalar($val) || $val === null ? ($val ?? '-') : json_encode($val) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>2. Recorded Consent Audit Trail (user_consents_log)</h2>
    <table>
        <thead>
            <tr>
                <th>Consent Type</th>
                <th>Action</th>
                <th>Policy Version</th>
                <th>IP Address</th>
                <th>Timestamp (Cairo)</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($payload['consents_audit_log'] ?? []) as $entry)
                <tr>
                    <td>{{ $entry['consent_type'] ?? '-' }}</td>
                    <td>{{ $entry['action'] ?? '-' }}</td>
                    <td>{{ $entry['policy_version'] ?? '-' }}</td>
                    <td>{{ $entry['ip_address'] ?? '-' }}</td>
                    <td>{{ $entry['created_at_cairo'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No consent entries recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>3. Data Subject Requests History</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Status</th>
                <th>Requested (Cairo)</th>
                <th>Completed (Cairo)</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($payload['data_subject_requests'] ?? []) as $req)
                <tr>
                    <td>#{{ $req['id'] ?? '' }}</td>
                    <td>{{ $req['request_type'] ?? '-' }}</td>
                    <td>{{ $req['status'] ?? '-' }}</td>
                    <td>{{ $req['requested_at_cairo'] ?? '-' }}</td>
                    <td>{{ $req['completed_at_cairo'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No data subject requests recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>4. Invoices Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Status</th>
                <th>Paid</th>
                <th>Unpaid</th>
                <th>Created (Cairo)</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($payload['invoices_summary'] ?? []) as $inv)
                <tr>
                    <td>#{{ $inv['invoice_number'] ?? '' }}</td>
                    <td>{{ $inv['status'] ?? '-' }}</td>
                    <td>{{ $inv['paid'] ?? 0 }}</td>
                    <td>{{ $inv['unpaid'] ?? 0 }}</td>
                    <td>{{ $inv['created_at_cairo'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

