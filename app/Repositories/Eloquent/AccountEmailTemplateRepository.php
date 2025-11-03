<?php

namespace App\Repositories\Eloquent;

use App\Models\AccountEmailTemplate;
use App\Repositories\Contracts\AccountEmailTemplateRepositoryInterface;

class AccountEmailTemplateRepository implements AccountEmailTemplateRepositoryInterface
{
    public function allByAccount(int $accountId)
    {
        return AccountEmailTemplate::with('emailTemplate')
            ->where('account_id', $accountId)
            ->orderBy('id')
            ->get();
    }

    public function findForAccount(int $id, int $accountId)
    {
        return AccountEmailTemplate::with('emailTemplate')
            ->where('account_id', $accountId)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function updateForAccount(int $id, int $accountId, array $data)
    {
        $template = $this->findForAccount($id, $accountId);
        $template->update($data);
        return $template->fresh('emailTemplate');
    }
}
