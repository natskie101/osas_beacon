<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Auth;
use App\Support\Request;
use App\Support\Response;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $role = Auth::role();

        if ($role === null) {
            return Response::redirect(url('/login'));
        }

        return Response::redirect(url($role->homePath()));
    }
}
