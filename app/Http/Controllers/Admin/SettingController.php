<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use App\Support\SiteFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The site's key/value settings — hotline numbers, address, social links, the
 * headline statistics.
 *
 * Keys are fixed: every one of them is read by name from a template through
 * setting(), so the panel edits the values and never the key set.
 */
class SettingController extends Controller
{
    use HandlesTranslatableContent;

    public function edit(): View
    {
        return view('admin.settings.edit', [
            // The `features` group is the Site controls page's business — it is
            // a set of switches, not text somebody types.
            'groups' => Setting::query()
                ->where('group', '!=', SiteFeatures::GROUP)
                ->orderBy('group')->orderBy('key')->get()->groupBy('group'),
        ]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        $submitted = $request->validated();
        $settings = Setting::whereIn('key', array_keys($submitted['values']))->get()->keyBy('key');

        foreach ($submitted['values'] as $key => $value) {
            $setting = $settings->get($key);

            if (! $setting) {
                continue;
            }

            $payload = ['value' => $value];

            if (in_array($key, Setting::TRANSLATABLE_KEYS, true)) {
                foreach (translation_locales() as $locale) {
                    $payload['translations'][$locale] = [
                        'value' => data_get($submitted, "translations.{$locale}.{$key}"),
                    ];
                }
            }

            // Saving busts settings.all.{locale} for every locale.
            $this->fillTranslatable($setting, $payload)->save();
        }

        return back()->with('status', __('admin.settings.saved'));
    }
}
