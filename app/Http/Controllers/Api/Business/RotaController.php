<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessAdmin\Rota\StoreRegularShiftsRequest;
use App\Http\Resources\Business\Rota\RotaResource;
use App\Services\Business\RotaService;
use App\Repositories\Contracts\RotaRepositoryInterface;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use App\Models\EmployeeRota;

class RotaController extends Controller
{
    public function __construct(
        private RotaRepositoryInterface $rotas,
        private RotaService $service
    ) {}

    protected function currentAccountId(): int
    {
        return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
    }

    public function index(Request $request) {
        $accountId = $this->currentAccountId();
        $employeeId = $request->integer('employee_id') ?: null;
        $from = Carbon::parse($request->get('from', now()->startOfWeek()));
        $to = Carbon::parse($request->get('to', now()->endOfWeek()));
        $data = $this->rotas->listForRange($accountId,$employeeId,$from,$to);
        return response()->json(['data'=>$data]);
    }

    public function store(StoreRegularShiftsRequest $r) {
        $id = $this->service->createRegularShifts(
            $this->currentAccountId(),
            $r->employee_id,
            $r->start_date,$r->end_date,$r->every_n_weeks,
            $r->days,$r->note,auth()->id()
        );
        return response()->json(['success'=>true,'recurrence_id'=>$id]);
    }

    public function update(Request $r, $id)
    {
        $r->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i',
            'note'       => 'nullable|string',
        ]);

        $shift = EmployeeRota::where('AccountId', $this->currentAccountId())
            ->where('id', $id)
            ->firstOrFail();

        $shift->update([
            'start_time' => $r->start_time,
            'end_time'   => $r->end_time,
            'note'       => $r->note,
        ]);

        return response()->json(['success' => true, 'data' => $shift]);
    }


    public function destroy(Request $r)
    {
        $r->validate([
            'id' => 'nullable|integer',
            'recurrence_id' => 'nullable|uuid',
        ]);

        $accountId = $this->currentAccountId();
        $deleted = 0;

        if ($r->filled('id')) {
            // 🟢 Delete only one shift
            $deleted = EmployeeRota::where('AccountId', $accountId)
                ->where('id', $r->id)
                ->delete();
        } elseif ($r->filled('recurrence_id')) {
            // 🔁 Delete all in the same recurring series
            $deleted = $this->rotas->deleteByRecurrence($accountId, $r->recurrence_id);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Either id or recurrence_id must be provided.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }

}
