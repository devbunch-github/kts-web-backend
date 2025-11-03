<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\AccountEmailTemplate;
use App\Models\EmailTemplate;

class AccountObserver
{
    public function created(Account $account): void
    {
        foreach (EmailTemplate::all() as $template) {
            AccountEmailTemplate::firstOrCreate(
                ['account_id' => $account->Id, 'email_template_id' => $template->id],
                [
                    'subject' => $template->subject,
                    'body' => $template->body,
                    'status' => $template->default_status,
                ]
            );
        }
    }
}
