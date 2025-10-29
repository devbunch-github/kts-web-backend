<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\Expenses;

class ExpenseService
{
    protected $expenseRepo;

    public function __construct(\App\Repositories\Contracts\ExpenseRepositoryInterface $expenseRepo)
    {
        $this->expenseRepo = $expenseRepo;
    }

    /** Upload one or more receipt files */
    public function uploadFiles($user, $req)
    {
        $bk_user = $user->bkUser ?? null;
        $account = $bk_user?->account ?? null;

        if (!$account || empty($req->formFile)) {
            return ['error' => true, 'msg' => 'Invalid request'];
        }

        $disk = config('filesystems.default', env('FILESYSTEM_DISK', 'public'));
        $existing_receipt_id = $req->input('existing_receipt_id', 0);
        $receipt_ids = [];

        foreach ($req->formFile as $file) {
            if (!str_contains($file, ',')) continue;

            @list($type, $file_data) = explode(';', $file);
            @list(, $file_data) = explode(',', $file_data);
            list(, $type) = explode('/', $type);

            $imageName = Str::random(60) . '.' . $type;
            $path = $account->Id . '/files/' . $imageName;
            $stored = Storage::disk($disk)->put($path, base64_decode($file_data));

            if ($stored) {
                $identifier = Uuid::uuid4()->toString();
                $id = DB::table('File')->insertGetId([
                    'Name' => $imageName,
                    'Identifier' => $identifier,
                    'DateCreated' => now(),
                    'CreatedById' => @$bk_user->Id,
                ]);
                $receipt_ids[] = $id;
            }
        }

        // Assign group receipt ID
        $group_id = $existing_receipt_id ?: ($receipt_ids[0] ?? null);
        if ($group_id) {
            DB::table('File')->whereIn('Id', $receipt_ids)->update(['receipt_id' => $group_id]);
        }

        $files = DB::table('File')
            ->where('receipt_id', $group_id)
            ->select('Id as id', 'Name as name')
            ->get()
            ->map(function ($f) use ($account, $disk) {
                $f->url = Storage::disk($disk)->url($account->Id . '/files/' . $f->name);
                return $f;
            });

        return ['id' => $group_id, 'files' => $files];
    }

    /** Delete file and update expense references */
    public function deleteFile($user, $fileId)
    {
        $bk_user = $user->bkUser ?? null;
        $account = $bk_user?->account ?? null;

        if (!$account) {
            return ['error' => true, 'msg' => 'No account found'];
        }

        $file = DB::table('File')
            ->where('Id', $fileId)
            ->where('CreatedById', @$bk_user->Id)
            ->first();

        if (!$file) {
            return ['error' => true, 'msg' => 'Invalid or unauthorized file'];
        }

        // Handle linked expenses
        $linked = DB::table('Expenses')->where('ReciptId', $file->Id)->get();
        if ($linked->isNotEmpty()) {
            $replacement = DB::table('File')
                ->where('receipt_id', $file->receipt_id)
                ->where('Id', '!=', $file->Id)
                ->first();

            $newReceipt = $replacement ? $replacement->Id : null;

            DB::table('Expenses')->where('ReciptId', $file->Id)
                ->update(['ReciptId' => $newReceipt]);

            if ($newReceipt) {
                DB::table('File')
                    ->where('receipt_id', $file->receipt_id)
                    ->where('Id', '!=', $file->Id)
                    ->update(['receipt_id' => $newReceipt]);
            } else {
                DB::table('File')
                    ->where('receipt_id', $file->receipt_id)
                    ->update(['receipt_id' => null]);
            }
        }

        // Delete file from storage
        $disk = config('filesystems.default', env('FILESYSTEM_DISK', 'public'));
        $filePath = $account->Id . '/files/' . $file->Name;
        if (Storage::disk($disk)->exists($filePath)) {
            Storage::disk($disk)->delete($filePath);
        }

        DB::table('File')->where('Id', $file->Id)->delete();
        return ['deleted_id' => $file->Id];
    }

    /** Export filtered expenses to PDF */
    public function exportPdf($user, $filters)
    {
        $bk_user = $user->bkUser ?? null;
        $account = $bk_user?->account ?? null;

        if (!$account) return null;

        $query = Expenses::query()
            ->with(['category'])
            ->where('AccountId', $account->Id)
            ->orderByDesc('Id'); // match list() sort order

        // Category filter (same as list)
        if (!empty($filters['category'])) {
            $query->where('CategoryId', $filters['category']);
        }

        // Date range — only apply if both provided
        if (!empty($filters['start']) && !empty($filters['end'])) {
            $start = Carbon::parse($filters['start'])->toDateString();
            $end   = Carbon::parse($filters['end'])->toDateString();

            $query->whereDate('PaidDateTime', '>=', $start)
                ->whereDate('PaidDateTime', '<=', $end);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('Supplier', 'like', "%{$term}%")
                ->orWhere('Notes', 'like', "%{$term}%");
            });
        }

        // Retrieve all records
        $expenses = $query->get();

        // Generate PDF view
        $pdf = Pdf::loadView('pdf.expenses', [
            'expenses' => $expenses,
            'filters'  => [
                'start' => $filters['start'] ? Carbon::parse($filters['start'])->format('d M Y') : null,
                'end'   => $filters['end'] ? Carbon::parse($filters['end'])->format('d M Y') : null,
            ],
            'account' => $account,
        ])->setPaper('a4', 'portrait');

        $fileName = 'Expense_Report_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($fileName);
    }

}
