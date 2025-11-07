<?php

namespace App\Repositories\Eloquent;

use App\Models\BusinessToDo;
use App\Repositories\Contracts\BusinessToDoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BusinessToDoRepository implements BusinessToDoRepositoryInterface
{
    public function list(string $accountId, ?bool $completed = null): LengthAwarePaginator
    {
        return BusinessToDo::query()
            ->where('AccountId', $accountId)
            ->when(!is_null($completed), fn($q) => $q->where('is_completed', $completed))
            ->orderBy('is_completed', 'asc')
            ->orderByRaw('CASE WHEN due_datetime IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('due_datetime', 'asc')
            ->paginate(20);
    }


    public function create(array $data): BusinessToDo
    {
        return BusinessToDo::create($data);
    }

    public function update(string $accountId, string $id, array $data): BusinessToDo
    {
        $todo = $this->find($accountId, $id);
        abort_unless($todo, 404, 'To-do not found.');
        $todo->fill($data)->save();
        return $todo->refresh();
    }

    public function delete(string $accountId, string $id): void
    {
        $todo = $this->find($accountId, $id);
        abort_unless($todo, 404, 'To-do not found.');
        $todo->delete();
    }

    public function toggle(string $accountId, string $id): BusinessToDo
    {
        $todo = $this->find($accountId, $id);
        abort_unless($todo, 404, 'To-do not found.');
        $todo->is_completed = !$todo->is_completed;
        $todo->save();
        return $todo->refresh();
    }

    public function find(string $accountId, string $id): ?BusinessToDo
    {
        return BusinessToDo::where('AccountId', $accountId)->where('id', $id)->first();
    }
}
