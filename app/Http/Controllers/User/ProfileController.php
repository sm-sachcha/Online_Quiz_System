<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user()->load('profile');
        return view('user.profile', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'profile_picture' => ['nullable', 'image', 'max:2048']
        ]);

        $user = Auth::user();
        $user->update(['name' => $request->name]);

        $profileData = $request->only(['phone', 'address', 'city', 'country', 'bio']);

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profiles', 'public');
            
            if ($user->profile->profile_picture) {
                Storage::disk('public')->delete($user->profile->profile_picture);
            }
            
            $profileData['profile_picture'] = $path;
        }

        $user->profile()->update($profileData);

        return back()->with('success', 'Profile updated successfully!');
    }
}