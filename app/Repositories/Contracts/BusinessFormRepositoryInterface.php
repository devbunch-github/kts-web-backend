<?php

namespace App\Repositories\Contracts;

use App\Models\BusinessForm;

interface BusinessFormRepositoryInterface
{
    public function paginate(string $accountId, int $perPage = 15);
    public function findForAccount(string $accountId, int $id): ?BusinessForm;
    public function create(array $data): BusinessForm;
    public function update(BusinessForm $form, array $data): BusinessForm;
    public function delete(BusinessForm $form): void;
    public function toggle(BusinessForm $form, bool $active): BusinessForm;

    public function syncServices(BusinessForm $form, array $serviceIds): void;
    public function upsertQuestions(BusinessForm $form, array $questions): void;
}
