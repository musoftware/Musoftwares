<?php

$erpFile = 'D:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/Musoftwares/lang/en/erp.php';
$generalFile = 'D:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/Musoftwares/lang/en/general.php';

$erpKeys = [
    'manager_approvals' => 'Manager Approvals',
    'pending_approvals' => 'Pending Approvals',
    'review_and_approve_team_requests' => 'Review and approve team requests to maintain operational control.',
    'leave_requests' => 'Leave Requests',
    'review_time_off_requests' => 'Review time-off requests from your team members.',
    'no_reason_provided' => 'No reason provided',
    'no_pending_leave_requests' => 'No pending leave requests.',
    'expense_and_withdrawal_requests' => 'Expense & Withdrawal Requests',
    'review_financial_requests' => 'Review financial requests from your department.',
    'client_member' => 'Client / Member',
    'handled_by_finance' => 'Handled by Finance Dept',
    'no_pending_financial_requests' => 'No pending financial requests.',
    'cannot_self_approve_escalated' => 'Cannot self-approve. Request has been escalated.',
    'leave_approved' => 'Leave request approved successfully.',
    'leave_rejected' => 'Leave request rejected.',
    'response_required_for_rejection' => 'A response is required when rejecting a request.',
    'review_leave_request' => 'Review Leave Request',
    'review_leave_request_description' => 'Review and provide a response to this leave request.',
    'team_member_leave_application' => 'Team Member Leave Application',
    'manager_response' => 'Manager Response',
    'manager_response_placeholder' => 'Enter your response or reason for rejection...'
];

$generalKeys = [
    'member' => 'Member',
    'type' => 'Type',
    'duration' => 'Duration',
    'reason' => 'Reason',
    'action' => 'Action',
    'unknown' => 'Unknown',
    'approve' => 'Approve',
    'reject' => 'Reject',
    'amount' => 'Amount',
    'date' => 'Date',
    'review' => 'Review',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'optional_for_approval' => 'Optional for approval'
];

function updateLangFile($file, $newKeys) {
    if (!file_exists($file)) return;
    $content = file_get_contents($file);
    
    // Simple approach: insert before the last "];"
    $pos = strrpos($content, '];');
    if ($pos !== false) {
        $insert = "";
        foreach ($newKeys as $key => $val) {
            if (strpos($content, "'$key'") === false && strpos($content, "\"$key\"") === false) {
                $escapedVal = str_replace("'", "\\'", $val);
                $insert .= "    '$key' => '$escapedVal',\n";
            }
        }
        if (!empty($insert)) {
            $content = substr_replace($content, $insert . "];", $pos, 2);
            file_put_contents($file, $content);
        }
    }
}

updateLangFile($erpFile, $erpKeys);
updateLangFile($generalFile, $generalKeys);

echo "Translations updated.\n";

?>
