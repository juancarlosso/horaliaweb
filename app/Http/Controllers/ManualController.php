<?php

namespace App\Http\Controllers;

class ManualController extends Controller
{
    public function index()
    {
        $manualPath = resource_path('manual/chapters.json');
        abort_unless(is_file($manualPath), 404);

        $chapters = json_decode(file_get_contents($manualPath), true);

        abort_if(! is_array($chapters), 500, 'No fue posible cargar el manual de usuario.');

        return view('manual.index', [
            'pageTitle' => 'Manual',
            'navigation' => DashboardController::navigation(),
            'activeSection' => 'manual',
            'chapters' => $chapters,
        ]);
    }
}
