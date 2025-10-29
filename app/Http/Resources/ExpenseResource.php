<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ExpenseResource extends JsonResource
{
    public function toArray($request)
    {
        $files = [];
        if ($this->ReciptId) {
            $disk = config('filesystems.default', env('FILESYSTEM_DISK', 'public'));

            $mainFile = DB::table('File')->find($this->ReciptId);
            if ($mainFile) {
                $groupFiles = DB::table('File')
                    ->where('receipt_id', $mainFile->receipt_id ?? $mainFile->Id)
                    ->get();

                foreach ($groupFiles as $file) {
                    $files[] = [
                        'id' => $file->Id,
                        'name' => $file->Name,
                        'url' => Storage::disk($disk)->url($this->AccountId . '/files/' . $file->Name),
                    ];
                }
            }
        }

        return [
            'id' => $this->Id,
            'supplier' => $this->Supplier,
            'amount' => $this->Amount,
            'notes' => $this->Notes,
            'payment_method' => $this->PaymentMethod == 0 ? 'Cash' : 'Bank',
            'paid_date_time' => $this->PaidDateTime,
            'category_id' => $this->CategoryId,
            'recurring' => $this->recurring,
            'receipt_id' => $this->ReciptId,
            'files' => $files,
        ];
    }
}
