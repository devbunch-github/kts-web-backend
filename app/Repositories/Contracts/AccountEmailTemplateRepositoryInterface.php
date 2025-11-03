<?php

namespace App\Repositories\Contracts;

interface AccountEmailTemplateRepositoryInterface
{
    public function allByAccount(int $accountId);
    public function findForAccount(int $id, int $accountId);
    public function updateForAccount(int $id, int $accountId, array $data);
}
