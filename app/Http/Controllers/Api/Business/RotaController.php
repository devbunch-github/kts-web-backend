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

    public function destroy(Request $r) {
        $r->validate(['recurrence_id'=>'required|uuid']);
        $deleted = $this->rotas->deleteByRecurrence($this->currentAccountId(),$r->recurrence_id);
        return response()->json(['success'=>true,'deleted'=>$deleted]);
    }
}
