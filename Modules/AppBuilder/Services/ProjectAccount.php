<?php

namespace Modules\AppBuilder\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * A paying customer never loses their project: after payment it joins the account with the email they paid with,
 * or a new account is opened for them, and an email brings them back to the project. At delivery another email says
 * the program is ready. A mail problem is only logged; it never undoes a payment or a delivery.
 */
class ProjectAccount
{
    /** After the first payment: link the project to an account and send the customer the link. */
    public function afterPayment(BuilderProject $project): void
    {
        $email = Str::lower(trim((string) $project->client_email));
        $setPassword = null;
        if ($project->user_id === null && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $email)->first() ?? $this->openAccount($project, $email);
            $setPassword = $user->wasRecentlyCreated ? $this->passwordLink($user) : null;
            $project->update(['user_id' => $user->id]);
        }

        $body = __('builder.mail_paid_body', ['link' => route('builder.show', $project)], $this->language($project))
            .($setPassword ? "\n\n".__('builder.mail_new_account', ['link' => $setPassword], $this->language($project)) : '');
        $this->send($project, __('builder.mail_paid_subject', [], $this->language($project)), $body);
    }

    public function afterDelivery(BuilderProject $project): void
    {
        $this->send($project, __('builder.mail_ready_subject', [], $this->language($project)),
            __('builder.mail_ready_body', ['link' => route('builder.show', $project)], $this->language($project)));
    }

    private function openAccount(BuilderProject $project, string $email): User
    {
        return User::create([
            'name' => $project->client_name ?: Str::before($email, '@'),
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'mobile_1' => $project->client_whatsapp,
            'whatsapp_number' => $project->client_whatsapp,
        ]);
    }

    private function passwordLink(User $user): string
    {
        return route('password.reset', ['token' => Password::broker()->createToken($user), 'email' => $user->email]);
    }

    private function send(BuilderProject $project, string $subject, string $body): void
    {
        $to = $project->client_email ?: $project->user?->email;
        if (! $to) {
            return;
        }

        try {
            Mail::raw($body, fn ($mail) => $mail->to($to)->subject($subject));
        } catch (\Throwable $e) {
            Log::warning('App builder: customer mail failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);
        }
    }

    private function language(BuilderProject $project): string
    {
        return $project->customerLanguage();
    }
}
