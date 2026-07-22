<?php

namespace App\Domain\Settings\Http\Controllers;

use App\Domain\Settings\Http\Requests\UpdateSettingRequest;
use App\Domain\Settings\Models\Setting;
use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function show(): View
    {
        $setting = $this->forCurrentTenant();

        $this->authorize('view', $setting);

        return view('settings.show', ['setting' => $setting]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $setting = $this->forCurrentTenant();

        $this->authorize('update', $setting);

        $setting->update($request->validated());

        return redirect()->route('settings.show')->with('status', 'Paramètres mis à jour.');
    }

    private function forCurrentTenant(): Setting
    {
        return Setting::query()->firstOrCreate(['structure_id' => TenantContext::id()]);
    }
}
