<?php

namespace App\Services;

use App\Repositories\Contracts\AccountEmailTemplateRepositoryInterface;

class AccountEmailTemplateService
{
    public function __construct(
        protected AccountEmailTemplateRepositoryInterface $repo
    ) {}

    public function list(int $accountId)
    {
        return $this->repo->allByAccount($accountId);
    }

    public function find(int $id, int $accountId)
    {
        return $this->repo->findForAccount($id, $accountId);
    }

    public function update(int $id, array $data, int $accountId)
    {
        return $this->repo->updateForAccount($id, $accountId, $data);
    }
}
