<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\EmailTemplate;
use App\Models\AccountEmailTemplate;

class SyncAccountEmailTemplates extends Command
{
    protected $signature = 'email:sync-templates';
    protected $description = 'Ensure all accounts have all default email templates.';

    public function handle()
    {
        $templates = EmailTemplate::all();
        $accounts = Account::all();

        $createdCount = 0;

        foreach ($accounts as $acc) {
            foreach ($templates as $tpl) {
                $record = AccountEmailTemplate::firstOrCreate(
                    ['account_id' => $acc->Id, 'email_template_id' => $tpl->id],
                    [
                        'subject' => $tpl->subject,
                        'body' => $tpl->body,
                        'status' => $tpl->default_status,
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $createdCount++;
                }
            }
        }

        $this->info("✅ Synced email templates for all accounts.");
        $this->info("Total new records created: {$createdCount}");

        return Command::SUCCESS;
    }
}
