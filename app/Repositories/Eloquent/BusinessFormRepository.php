<?php

namespace App\Repositories\Eloquent;

use App\Models\BusinessForm;
use App\Models\BusinessFormQuestion;
use App\Repositories\Contracts\BusinessFormRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BusinessFormRepository implements BusinessFormRepositoryInterface
{
    public function paginate(string $accountId, int $perPage = 15)
    {
        return BusinessForm::withCount('questions')
            ->where('AccountId', $accountId)
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function findForAccount(string $accountId, int $id): ?BusinessForm
    {
        return BusinessForm::with(['questions','services'])
            ->where('AccountId', $accountId)
            ->find($id);
    }

    public function create(array $data): BusinessForm
    {
        return BusinessForm::create($data);
    }

    public function update(BusinessForm $form, array $data): BusinessForm
    {
        $form->update($data);
        return $form->refresh();
    }

    public function delete(BusinessForm $form): void
    {
        $form->delete();
    }

    public function toggle(BusinessForm $form, bool $active): BusinessForm
    {
        $form->update(['is_active' => $active]);
        return $form->refresh();
    }

    public function syncServices(BusinessForm $form, array $serviceIds): void
    {
        $form->services()->sync($serviceIds);
    }

    /**
     * Upsert questions (create/update/delete based on payload).
     * Payload format:
     * [
     *   ['id'=>null,'type'=>'short_answer','label'=>'...','required'=>true,'sort_order'=>1,'options'=>[]],
     *   ...
     * ]
     */
    public function upsertQuestions(BusinessForm $form, array $questions): void
    {
        DB::transaction(function () use ($form, $questions) {
            $keepIds = [];
            foreach ($questions as $q) {
                $payload = [
                    'type'       => $q['type'],
                    'label'      => $q['label'],
                    'required'   => (bool)($q['required'] ?? false),
                    'sort_order' => (int)($q['sort_order'] ?? 0),
                    'options'    => $q['options'] ?? null,
                ];

                if (!empty($q['id'])) {
                    $question = BusinessFormQuestion::where('form_id',$form->id)->find($q['id']);
                    if ($question) {
                        $question->update($payload);
                        $keepIds[] = $question->id;
                    }
                } else {
                    $question = $form->questions()->create($payload);
                    $keepIds[] = $question->id;
                }
            }

            // delete removed
            if (count($keepIds)) {
                $form->questions()->whereNotIn('id', $keepIds)->delete();
            } else {
                $form->questions()->delete();
            }
        });
    }
}
