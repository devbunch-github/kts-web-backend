<?php

namespace App\Repositories\Contracts;

interface EmployeeContract
{
    // ── Generic CRUD (fallback or admin usage)
    public function list();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);

    // ── Account-specific operations
    public function listByAccount(int $accountId);
    public function findByAccount(int $accountId, int $id);
    public function createForAccount(int $accountId, array $data);
    public function updateForAccount(int $accountId, int $id, array $data);
    public function softDeleteByAccount(int $accountId, int $id);
}
