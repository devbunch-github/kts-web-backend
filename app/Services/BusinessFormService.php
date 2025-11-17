<?php

namespace App\Services;

use App\Models\BusinessForm;
use App\Repositories\Contracts\BusinessFormRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class BusinessFormService
{
    public function __construct(
        private BusinessFormRepositoryInterface $repo
    ) {}

    public function list(string $accountId, int $perPage = 15)
    {
        return $this->repo->paginate($accountId, $perPage);
    }

    public function create(string $accountId, array $payload): BusinessForm
    {
        $form = $this->repo->create([
            'AccountId'  => $accountId,
            'title'      => $payload['title'],
            'frequency'  => $payload['frequency'] ?? 'every_booking',
            'is_active'  => (bool)($payload['is_active'] ?? true),
            'created_by' => Auth::id(),
        ]);

        $this->repo->syncServices($form, $payload['service_ids'] ?? []);
        $this->repo->upsertQuestions($form, $payload['questions'] ?? []);

        return $form->load(['questions','services']);
    }

    public function update(string $accountId, int $id, array $payload): BusinessForm
    {
        $form = $this->repo->findForAccount($accountId, $id);
        abort_if(!$form, 404, 'Form not found.');

        $this->repo->update($form, [
            'title'      => $payload['title'],
            'frequency'  => $payload['frequency'] ?? 'every_booking',
            'is_active'  => (bool)($payload['is_active'] ?? true),
            'updated_by' => Auth::id(),
        ]);

        $this->repo->syncServices($form, $payload['service_ids'] ?? []);
        $this->repo->upsertQuestions($form, $payload['questions'] ?? []);

        return $form->load(['questions','services']);
    }

    public function toggle(string $accountId, int $id, bool $active): BusinessForm
    {
        $form = $this->repo->findForAccount($accountId, $id);
        abort_if(!$form, 404, 'Form not found.');
        return $this->repo->toggle($form, $active);
    }

    public function delete(string $accountId, int $id): void
    {
        $form = $this->repo->findForAccount($accountId, $id);
        abort_if(!$form, 404, 'Form not found.');
        $this->repo->delete($form);
    }
}
