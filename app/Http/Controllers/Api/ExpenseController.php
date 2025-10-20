<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\Expenses;

class ExpenseController extends Controller
{
    protected $expenses;

    public function __construct(ExpenseRepositoryInterface $expenses)
    {
        $this->expenses = $expenses;
    }

    public function index(Request $request)
    {
        $data = $this->expenses->list($request);
        return ExpenseResource::collection($data);
    }

    public function store(ExpenseRequest $request)
    {
        $expense = $this->expenses->store($request->validated(), Auth::user());
        return new ExpenseResource($expense);
    }

    public function show($id)
    {
        $expense = $this->expenses->find($id);
        return new ExpenseResource($expense);
    }

    public function update(ExpenseRequest $request, $id)
    {
        $expense = $this->expenses->update($id, $request->validated());
        return new ExpenseResource($expense);
    }

    public function destroy($id)
    {
        $this->expenses->delete($id);
        return response()->json(['message' => 'Expense deleted successfully']);
    }

    public function exportPdf(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;
        $category = $request->category_id;
        $search = $request->search;

        $query = Expenses::query()
            ->with(['category']);

        if ($start) $query->whereDate('PaidDateTime', '>=', $start);
        if ($end) $query->whereDate('PaidDateTime', '<=', $end);
        if ($category) $query->where('CategoryId', $category);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('Supplier', 'like', "%$search%")
                ->orWhere('Notes', 'like', "%$search%");
            });
        }

        $expenses = $query->orderByDesc('PaidDateTime')->get();

        $pdf = Pdf::loadView('pdf.expenses', [
            'expenses' => $expenses,
            'filters' => [
                'start' => $start ? Carbon::parse($start)->format('d M Y') : null,
                'end' => $end ? Carbon::parse($end)->format('d M Y') : null,
            ],
        ])->setPaper('a4', 'portrait');

        $fileName = 'Expense_Report_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($fileName);
    }
}
