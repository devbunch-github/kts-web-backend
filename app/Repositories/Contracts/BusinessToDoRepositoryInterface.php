<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\BusinessToDo;

interface BusinessToDoRepositoryInterface
{
    /** List current account's items (optionally by completion) */
    public function list(string $accountId, ?bool $completed = null): LengthAwarePaginator;

    /** Create item in current account */
    public function create(array $data): BusinessToDo;

    /** Update item (scoped) */
    public function update(string $accountId, string $id, array $data): BusinessToDo;

    /** Delete item (scoped) */
    public function delete(string $accountId, string $id): void;

    /** Toggle completion (scoped) */
    public function toggle(string $accountId, string $id): BusinessToDo;

    /** Find one (scoped) */
    public function find(string $accountId, string $id): ?BusinessToDo;
}
