<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThemeController extends Controller
{
    /**
     * Toggle theme between light and dark mode
     */
    public function toggle(Request $request)
    {
        $currentTheme = $request->cookie('theme', 'light');
        $newTheme = $currentTheme === 'dark' ? 'light' : 'dark';
        
        return response()->json(['theme' => $newTheme])
            ->cookie('theme', $newTheme, 60 * 24 * 365); // 1 year
    }
    
    /**
     * Set specific theme
     */
    public function set(Request $request)
    {
        $request->validate([
            'theme' => 'required|in:light,dark'
        ]);
        
        $theme = $request->input('theme');
        
        return response()->json(['theme' => $theme])
            ->cookie('theme', $theme, 60 * 24 * 365); // 1 year
    }
}
