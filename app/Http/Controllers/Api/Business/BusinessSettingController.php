<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessAdmin\SiteSettingRequest;
use App\Http\Resources\BusinessSettingResource;
use App\Services\BusinessSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;

class BusinessSettingController extends Controller
{
    public function __construct(protected BusinessSettingService $service) {}

    protected function currentAccountId( $accountId = null ): int
    {
        if($accountId == null){
            return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
        } else {
            return (int) $accountId;
        }
    }

    public function show(Request $request, string $type)
    {
        $accountId = $this->currentAccountId($request->account_id);
        $setting = $this->service->get($accountId, $type);

        // Return empty shell if nothing saved yet
        if (!$setting) {
            return response()->json([
                'data' => [
                    'type' => $type,
                    'data' => [],
                ]
            ]);
        }

        return new BusinessSettingResource($setting);
    }

    public function update(Request $request, string $type)
    {
        $accountId = $this->currentAccountId($request->account_id);

        if ($type === 'site') {
            /** @var SiteSettingRequest $request */
            $request = app(SiteSettingRequest::class);
            $validated = $request->validated();

            // Pull existing to clean up replaced files
            $existing = $this->service->get($accountId, 'site')?->data ?? [];

            $data = [
                'hero_text' => $validated['hero_text'] ?? ($existing['hero_text'] ?? null),
                'colors'    => array_merge([
                    'background' => '#f5e0db',
                    'text'       => '#3a3a3a',
                    'key'        => '#d39a7a',
                    'dark'       => '#6a3b1d',
                ], $validated['colors'] ?? ($existing['colors'] ?? [])),
                'logo_path'  => $existing['logo_path']  ?? null,
                'cover_path' => $existing['cover_path'] ?? null,
            ];

            // Uploads (public disk)
            $dir = "business/{$accountId}/site";
            if ($request->hasFile('logo')) {
                if (!empty($existing['logo_path'])) Storage::disk('public')->delete($existing['logo_path']);
                $data['logo_path'] = $request->file('logo')->store($dir, 'public');
            }
            if ($request->hasFile('cover_image')) {
                if (!empty($existing['cover_path'])) Storage::disk('public')->delete($existing['cover_path']);
                $data['cover_path'] = $request->file('cover_image')->store($dir, 'public');
            }

            $saved = $this->service->update($accountId, 'site', $data);
            return new BusinessSettingResource($saved);
        }

        // default passthrough for other types (working_hours, contact, policy, general)
        $payload = $request->all();
        $saved = $this->service->update($accountId, $type, $payload);
        return new BusinessSettingResource($saved);
    }
}
