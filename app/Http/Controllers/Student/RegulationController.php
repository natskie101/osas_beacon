<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Regulation;
use App\Models\Tracking;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;
use App\Support\Session;

final class RegulationController extends Controller
{
    public function index(Request $request)
    {
        $actor = Auth::user();
        $regulations = Regulation::published();
        $search = mb_strtolower((string) $request->string('search'));
        $category = (string) $request->string('category');

        if ($search !== '') {
            $regulations = array_values(array_filter(
                $regulations,
                static fn (array $item): bool => str_contains(mb_strtolower((string) $item['title']), $search)
                    || str_contains(mb_strtolower((string) $item['summary']), $search)
                    || str_contains(mb_strtolower((string) $item['body']), $search),
            ));
        }

        if ($category !== '') {
            $regulations = array_values(array_filter(
                $regulations,
                static fn (array $item): bool => (string) $item['category'] === $category,
            ));
        }

        return view('student/regulations/index', [
            'title' => 'Rules and Regulations',
            'pageTitle' => config('navigation.meta.student.rules.title'),
            'pageSubtitle' => config('navigation.meta.student.rules.subtitle'),
            'pageKey' => 'rules',
            'user' => $actor,
            'role' => $actor['role'],
            'regulations' => $regulations,
            'categories' => Regulation::categories(),
            'categoryCounts' => Regulation::publishedCategoryCounts(),
            'totalPublished' => count(Regulation::published()),
            'filters' => ['search' => (string) $request->string('search'), 'category' => $category],
        ]);
    }

    public function show(Request $request, string $publicId)
    {
        $actor = Auth::user();
        $regulation = Regulation::findPublished($publicId);

        if ($regulation === null) {
            throw new NotFoundHttpException('The requested regulation could not be found.');
        }

        Regulation::incrementViews((int) $regulation['id']);
        Tracking::record((string) $regulation['title'], 'Regulation', (string) $regulation['public_id']);

        return view('student/regulations/show', [
            'title' => $regulation['title'],
            'pageTitle' => $regulation['title'],
            'pageSubtitle' => $regulation['reference_code'] . ' · ' . $regulation['category'],
            'pageKey' => 'rules',
            'user' => $actor,
            'role' => $actor['role'],
            'regulation' => $regulation,
            'sanctions' => json_items($regulation['sanctions'] ?? null),
        ]);
    }

    public function acknowledge(Request $request, string $publicId)
    {
        $regulation = Regulation::findPublished($publicId);

        if ($regulation === null) {
            throw new NotFoundHttpException('The requested regulation could not be found.');
        }

        Session::flash('status', 'You acknowledged "' . $regulation['title'] . '".');

        return redirect(url('/student/rules/' . $regulation['public_id']));
    }
}
