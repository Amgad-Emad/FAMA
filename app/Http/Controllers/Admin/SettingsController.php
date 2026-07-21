<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContractFlow;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin platform-settings screen (Phase 3B UI). Reads/writes the admin-tunable
 * globals through SettingsService; changes are audited. `can:manage-settings`
 * gates the routes.
 */
class SettingsController extends AdminController
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(): View
    {
        return view('admin.settings', [
            'settings' => $this->settings->all(),
            'flows' => ContractFlow::query()->where('is_active', true)->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'default_currency' => ['nullable', 'string', 'size:3'],
            'default_contract_flow_id' => ['nullable', 'integer', 'exists:contract_flows,id'],
            'feature_flags' => ['nullable', 'array'],
        ]);

        $this->settings->setMany(array_filter($data, fn ($v) => $v !== null));

        activity('settings')
            ->causedBy($this->admin())
            ->withProperties($data)
            ->log('settings.updated');

        return response()->success($this->settings->all(), __('Settings saved.'));
    }
}
