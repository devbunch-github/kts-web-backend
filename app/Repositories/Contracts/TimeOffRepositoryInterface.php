<?php

namespace App\Repositories\Contracts;

use Carbon\Carbon;

interface TimeOffRepositoryInterface {
    public function listForRange(int $accountId, ?int $employeeId, Carbon $from, Carbon $to): array;
    public function createMany(array $rows): void;
    public function deleteByRecurrence(int $accountId, string $recurrenceId): int;
}
