<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use App\Models\Expenses;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Services\ExpenseService;

class ExpenseController extends Controller
{
    protected $expenses;
    protected $expenseService;

    /**
     * Inject both Repository and Service dependencies.
     */
    public function __construct(
        ExpenseRepositoryInterface $expenses,
        ExpenseService $expenseService
    ) {
        $this->expenses = $expenses;
        $this->expenseService = $expenseService;
    }

    /**
     * List paginated expenses with applied filters.
     */
    public function index(Request $request)
    {
        $data = $this->expenses->list($request);
        return ExpenseResource::collection($data);
    }

    /**
     * Store a new expense entry.
     */
    public function store(ExpenseRequest $request)
    {
        $data = $request->validated();

        // Handle receipt upload if any
        if ($request->hasFile('receipt_file')) {
            $file = $request->file('receipt_file');
            $path = $file->store('receipts', 'public');
            $data['receipt_id'] = $path;
        }

        $expense = $this->expenses->store($data, Auth::user());
        return new ExpenseResource($expense);
    }

    /**
     * Show specific expense details.
     */
    public function show($id)
    {
        $expense = $this->expenses->find($id);
        return new ExpenseResource($expense);
    }

    /**
     * Update existing expense details.
     */
    public function update(ExpenseRequest $request, $id)
    {
        $expense = $this->expenses->update($id, $request->validated());
        return new ExpenseResource($expense);
    }

    /**
     * Delete a specific expense.
     */
    public function destroy($id)
    {
        $this->expenses->delete($id);
        return response()->json(['message' => 'Expense deleted successfully']);
    }

    /**
     * Upload one or multiple receipt files via ExpenseService.
     */
    public function uploadFiles(Request $req)
    {
        $result = $this->expenseService->uploadFiles(Auth::user(), $req);

        if (isset($result['error']) && $result['error']) {
            return response()->json(['msg' => $result['msg']], 422);
        }

        return response()->json([
            'msg' => 'Files uploaded successfully',
            'id' => $result['id'],
            'files' => $result['files']
        ], 200);
    }

    /**
     * Delete a file and update linked expenses if needed.
     */
    public function deleteFile(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:File,Id',
        ]);

        $result = $this->expenseService->deleteFile(Auth::user(), $request->id);

        if (isset($result['error']) && $result['error']) {
            return response()->json(['msg' => $result['msg']], 422);
        }

        return response()->json([
            'success' => true,
            'msg' => 'File deleted successfully',
            'deleted_id' => $result['deleted_id']
        ], 200);
    }

    /**
     * Export filtered expense records as downloadable PDF.
     */
    public function exportPdf(Request $request)
    {
        $pdf = $this->expenseService->exportPdf(Auth::user(), [
            'start' => $request->start_date,
            'end' => $request->end_date,
            'category' => $request->category_id,
            'search' => $request->search,
        ]);

        if (!$pdf) {
            return response()->json(['message' => 'No account found for this user'], 403);
        }

        return $pdf;
    }
}
