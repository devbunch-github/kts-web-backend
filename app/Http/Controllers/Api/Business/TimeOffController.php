<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessAdmin\Rota\StoreTimeOffRequest;
use App\Services\Business\RotaService;
use App\Repositories\Contracts\TimeOffRepositoryInterface;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use App\Models\EmployeeTimeOff;

class TimeOffController extends Controller
{
    public function __construct(
        private TimeOffRepositoryInterface $offs,
        private RotaService $service
    ) {}

    protected function currentAccountId(): int
    {
        return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
    }

    public function index(Request $r) {
        $acc = $this->currentAccountId();
        $emp = $r->integer('employee_id') ?: null;
        $from = Carbon::parse($r->get('from', now()->startOfWeek()));
        $to   = Carbon::parse($r->get('to', now()->endOfWeek()));
        $data = $this->offs->listForRange($acc,$emp,$from,$to);
        return response()->json(['data'=>$data]);
    }

    public function store(StoreTimeOffRequest $r) {
        $id = $this->service->createTimeOff(
            $this->currentAccountId(),$r->employee_id,
            $r->date,$r->start_time,$r->end_time,
            (bool)$r->boolean('repeat',false),$r->repeat_until,
            $r->note,auth()->id()
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

        $off = EmployeeTimeOff::where('AccountId', $this->currentAccountId())
            ->findOrFail($id);

        $off->update([
            'start_time' => $r->start_time,
            'end_time'   => $r->end_time,
            'note'       => $r->note,
        ]);

        return response()->json(['success' => true, 'data' => $off]);
    }

    public function destroy(Request $r)
    {
        $r->validate([
            'id' => 'nullable|integer',
            'recurrence_id' => 'nullable|uuid',
        ]);

        $query = $this->offs->queryForAccount($this->currentAccountId());

        if ($r->filled('id')) {
            $deleted = $query->where('id', $r->id)->delete();
        } elseif ($r->filled('recurrence_id')) {
            $deleted = $query->where('recurrence_id', $r->recurrence_id)->delete();
        } else {
            return response()->json(['success' => false, 'message' => 'No identifier provided.'], 422);
        }

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }

}
