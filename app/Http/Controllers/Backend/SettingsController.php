<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Helpers\SettingsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::byUser(Auth::id())->get()->keyBy('setting_key');
        $groups = SettingsHelper::getGroups();
        
        return view('backend.settings.index', compact('settings', 'groups'));
    }

    public function update(Request $request, $id)
    {
        $setting = Setting::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $request->validate([
            'setting_value' => 'required',
        ]);
        
        $setting->update([
            'setting_value' => $request->setting_value,
        ]);
        
        return redirect()->route('admin.settings.index')->with('success', 'Setting updated successfully.');
    }

    public function updateIndividualSetting(Request $request, $key)
    {
        // Handle file uploads for logo and favicon
        if (in_array($key, ['app_logo', 'app_favicon']) && $request->hasFile($key . '_file')) {
            $file = $request->file($key . '_file');
            
            // Validate file
            $request->validate([
                $key . '_file' => [
                    'required',
                    'image',
                    'mimes:jpeg,png,jpg,gif,ico',
                    'max:' . ($key === 'app_favicon' ? '1024' : '2048'), // 1MB for favicon, 2MB for logo
                ],
            ]);
            
            // Convert file to base64
            $imageData = file_get_contents($file->getPathname());
            $base64 = 'data:image/' . $file->getClientOriginalExtension() . ';base64,' . base64_encode($imageData);
            
            Setting::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'setting_key' => $key,
                ],
                [
                    'setting_value' => $base64,
                    'setting_type' => 'general',
                ]
            );
            
            return response()->json(['success' => true]);
        }
        
        // Handle regular text inputs
        $request->validate([
            'setting_value' => 'required',
        ]);
        
        $setting = Setting::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'setting_key' => $key,
            ],
            [
                'setting_value' => $request->setting_value,
                'setting_type' => 'general',
            ]
        );
        
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $setting = Setting::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $setting->delete();
        
        return redirect()->route('admin.settings.index')->with('success', 'Setting deleted successfully.');
    }

    public function socialMediaSettings()
    {
        $settings = Setting::byUser(Auth::id())
            ->whereIn('setting_key', array_keys(SettingsHelper::SOCIAL_MEDIA_SETTINGS))->get()->keyBy('setting_key');
        
        return view('backend.settings.social-media', compact('settings'));
    }

    public function saveSocialMediaSettings(Request $request)
    {
        $socialMediaKeys = array_keys(SettingsHelper::SOCIAL_MEDIA_SETTINGS);
        
        foreach ($socialMediaKeys as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'setting_key' => $key,
                    ],
                    [
                        'setting_value' => $request->$key,
                        'setting_type' => 'social_media',
                    ]
                );
            }
        }
        
        return redirect()->route('admin.settings.social-media')->with('success', 'Social media settings saved successfully.');
    }
}