<?php

namespace App\Services;

use App\Models\BusinessToDo;
use App\Repositories\Contracts\BusinessToDoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Exception;

class BusinessToDoService
{
    public function __construct(private BusinessToDoRepositoryInterface $repo) {}

    /** Helper to fetch tenant/account id from current user */
    protected function accountId(): int
    {
        return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
    }

    public function list(?bool $completed = null): LengthAwarePaginator
    {
        return $this->repo->list($this->accountId(), $completed);
    }

    public function create(array $data): BusinessToDo
    {
        $data['AccountId']   = $this->accountId();
        $data['CreatedById'] = (string) Auth::id();
        return $this->repo->create($data);
    }

    public function update(string $id, array $data): BusinessToDo
    {
        $data['ModifiedById'] = (string) Auth::id();
        return $this->repo->update($this->accountId(), $id, $data);
    }

    public function delete(string $id): void
    {
        $this->repo->delete($this->accountId(), $id);
    }

    public function toggle(string $id): BusinessToDo
    {
        return $this->repo->toggle($this->accountId(), $id);
    }

    public function find(string $id): ?BusinessToDo
    {
        return $this->repo->find($this->accountId(), $id);
    }
}
