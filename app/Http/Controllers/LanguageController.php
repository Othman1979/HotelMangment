<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function __invoke(Request $request)
    {
        $locale = in_array($request->input('locale'), SetLocale::LOCALES, true) ? $request->input('locale') : 'ar';
        $return = (string) $request->input('return', '/');
        if (! str_starts_with($return, '/') || str_starts_with($return, '//')) {
            $return = '/';
        }

        return redirect($return)->withCookie(cookie()->forever(SetLocale::COOKIE, $locale));
    }
}
