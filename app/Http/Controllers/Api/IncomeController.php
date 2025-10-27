<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\IncomeRepository;
use App\Repositories\Eloquent\AccountingPeriodRepository;
use App\Repositories\Eloquent\AccountRepository;
use App\Repositories\Eloquent\CustomerRepository;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class IncomeController extends Controller
{
    public function __construct(
        protected IncomeRepository $incomes,
        protected AccountRepository $accounts,
        protected AccountingPeriodRepository $periods,
        protected CustomerRepository $customers,
    ) {}

    // ✅ Handle both authenticated and preregistered users
    protected function currentAccountId(): ?int
    {
        if (Auth::check()) {
            return Auth::user()?->bkUser?->account?->Id;
        }

        $userId = request()->header('X-User-Id') ?? request('user_id');
        if ($userId) {
            $user = User::find($userId);
            return $user?->bkUser?->account?->Id;
        }

        return null;
    }

    public function index(Request $request)
    {
        $data = $this->incomes->paginateForAccount(
            $this->currentAccountId(),
            $request->get('per_page', 10)
        );

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $accountId = $this->currentAccountId();
        $period = $this->periods->latestForAccount($accountId);

        $data = $request->all();

        // ✅ If "CustomerId" is a name string, treat it as CustomerName
        if (!empty($data['CustomerId']) && !is_numeric($data['CustomerId'])) {
            $data['CustomerName'] = $data['CustomerId'];
            $data['CustomerId'] = null;
        }

        if (empty($data['CustomerId'])) {
            $data['CustomerId'] = null;
        }

        // ✅ Validation
        $v = validator($data, [
            'CustomerId'      => ['nullable', 'integer', 'exists:customers,Id'],
            'CustomerName'    => ['nullable', 'string', 'max:191'],
            'CustomerEmail'   => ['nullable', 'email', 'max:191'],
            'CustomerPhone'   => ['nullable', 'string', 'max:32'],
            'CategoryId'      => ['nullable', 'exists:categories,Id'],
            'ServiceId'       => ['nullable', 'exists:services,Id'],
            'Amount'          => ['required', 'numeric'],
            'Description'     => ['nullable', 'string', 'max:500'],
            'Notes'           => ['nullable', 'string', 'max:2000'],
            'PaymentMethod'   => ['nullable', 'string', 'max:100'],
            'PaymentDateTime' => ['required', 'date'],
            'IsRefund'        => ['boolean'],
            'RefundAmount'    => ['nullable', 'numeric'],
        ])->validate();

        // ✅ Auto-create customer if only name provided
        $customerId = $v['CustomerId'] ?? null;

        if (!$customerId && !empty($v['CustomerName'])) {
            $customer = $this->customers->findOrCreate([
                'Name'         => $v['CustomerName'],
                'Email'        => $v['CustomerEmail'] ?? null,
                'MobileNumber' => $v['CustomerPhone'] ?? null,
            ], $accountId, Auth::id() ?? 0);

            $customerId = $customer->Id;
        }

        $payload = array_merge($v, [
            'CustomerId'         => $customerId,
            'AccountId'          => $accountId,
            'AccountingPeriodId' => $period?->Id,
            'CreatedBy'          => Auth::id() ?? 0,
        ]);

        if (!empty($v['PaymentDateTime'])) {
            try {
                $v['PaymentDateTime'] = \Carbon\Carbon::parse($v['PaymentDateTime'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Invalid date format for PaymentDateTime.'
                ], 422);
            }
        }

        $income = $this->incomes->create($payload);

        return response()->json([
            'message' => 'Income created successfully',
            'data' => $income,
        ], 201);
    }

    public function show(int $id)
    {
        return response()->json($this->incomes->find($id));
    }

    public function update(Request $request, int $id)
    {
        $income = $this->incomes->find($id);
        if (!$income) {
            return response()->json(['message' => 'Income not found'], 404);
        }

        $data = $request->all();

        // 🧩 Preserve old customer if not supplied
        if (!array_key_exists('CustomerId', $data) || empty($data['CustomerId'])) {
            $data['CustomerId'] = $income->CustomerId;
        }

        if (!empty($data['CustomerId']) && !is_numeric($data['CustomerId'])) {
            $data['CustomerName'] = $data['CustomerId'];
            $data['CustomerId'] = null;
        }

        $v = validator($data, [
            'CustomerId'      => ['nullable', 'integer', 'exists:customers,Id'],
            'CategoryId'      => ['nullable', 'exists:categories,Id'],
            'ServiceId'       => ['nullable', 'exists:services,Id'],
            'Amount'          => ['required', 'numeric'],
            'Description'     => ['nullable', 'string', 'max:500'],
            'Notes'           => ['nullable', 'string', 'max:2000'],
            'PaymentMethod'   => ['nullable', 'string', 'max:100'],
            'PaymentDateTime' => ['required', 'date'],
            'IsRefund'        => ['boolean'],
            'RefundAmount'    => ['nullable', 'numeric'],
        ])->validate();

        // Normalize PaymentDateTime
        if (!empty($v['PaymentDateTime'])) {
            try {
                $v['PaymentDateTime'] = \Carbon\Carbon::parse($v['PaymentDateTime'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return response()->json(['message' => 'Invalid date format for PaymentDateTime.'], 422);
            }
        }

        // Update record
        $income = $this->incomes->update($id, array_merge($v, [
            'UpdatedBy' => Auth::id() ?? 0,
        ]));

        return response()->json([
            'message' => 'Income updated successfully',
            'data' => $income,
        ]);
    }


    public function destroy(int $id)
    {
        $this->incomes->delete($id);
        return response()->json(['message' => 'Income deleted']);
    }

    public function exportPdf(Request $request)
    {
        $accountId = $this->currentAccountId();

        // Optional date filters
        $start = $request->query('start_date');
        $end   = $request->query('end_date');

        $q = \App\Models\Income::with(['customer', 'category', 'service'])
            ->where('AccountId', $accountId);

        if ($start) $q->whereDate('PaymentDateTime', '>=', $start);
        if ($end)   $q->whereDate('PaymentDateTime', '<=', $end);

        $incomes = $q->orderByDesc('PaymentDateTime')->get();

        // Generate PDF view
        $pdf = Pdf::loadView('pdf.income_report', compact('incomes', 'start', 'end'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('income_report_' . now()->format('Ymd_His') . '.pdf');
    }
}
