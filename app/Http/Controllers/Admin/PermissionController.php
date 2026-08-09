<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PermissionsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/permissions/index', [
            'permissions' => Permission::query()
                ->withCount('roles')
                ->orderBy('group')
                ->orderBy('id')
                ->get(['id', 'name', 'group', 'guard_name']),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $format = $request->query('format') === 'csv' ? 'csv' : 'xlsx';

        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX;

        $filename = 'permissions-'.now()->format('Y-m-d').'.'.$extension;

        return Excel::download(new PermissionsExport, $filename, $writerType);
    }
}
