<?php

namespace App\Http\Controllers\api\mobile\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function index(): View {
        return view('docs.mobile', [
            'schemaUrl' => url('/api/mobile/v1/docs/openapi.yaml'),
        ]);
    }

    public function schema(): Response {
        $path = base_path('docs/openapi.yaml');
        abort_unless(file_exists($path), 404);

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'text/yaml; charset=UTF-8',
        ]);
    }
}
