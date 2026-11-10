<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function index(Request $request)
    {
        $role = auth_role();

        if ($role === null) {
            return redirect(url('/login'));
        }

        return redirect(url($role->homePath()));
    }
}
