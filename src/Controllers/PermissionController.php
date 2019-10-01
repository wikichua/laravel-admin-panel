<?php

namespace Wikichua\SimpleControlPanel\Controllers;

use App\Http\Controllers\Controller;
use Yajra\DataTables\Html\Builder;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth_admin', 'can:Access Admin Panel']);
        $this->middleware('intend_url')->only(['index', 'read']);
        $this->middleware('can:Create Permissions')->only(['createForm', 'create']);
        $this->middleware('can:Read Permissions')->only(['index', 'read']);
        $this->middleware('can:Update Permissions')->only(['updateForm', 'update']);
        $this->middleware(['can:Delete Permissions'])->only('delete');
    }

    public function index(Builder $builder)
    {
        if (request()->ajax()) {
            $permissions = app(config('lap.models.permission'))->query();
            $datatable = datatables($permissions)
                ->editColumn('actions', function ($permission) {
                    return view('lap::permissions.datatable.actions', compact('permission'));
                })
                ->rawColumns(['actions']);

            return $datatable->toJson();
        }

        $html = $builder->columns([
            ['title' => 'Group', 'data' => 'group'],
            ['title' => 'Name', 'data' => 'name'],
            ['title' => '', 'data' => 'actions', 'searchable' => false, 'orderable' => false],
        ]);
        $html->setTableAttribute('id', 'permissions_datatable');

        return view('lap::permissions.index', compact('html'));
    }

    public function createForm()
    {
        return view('lap::permissions.create');
    }

    public function create()
    {
        $this->validate(request(), [
            'group' => 'required',
            'name' => 'required',
        ]);

        $permission = app(config('lap.models.permission'))->create(request()->all());

        activity('Created Permission: ' . $permission->id, request()->all(), $permission);
        flash(['success', 'Permission created!']);

        if (request()->input('_submit') == 'redirect') {
            return response()->json(['redirect' => session()->pull('url.intended', route('admin.permissions'))]);
        }
        else {
            return response()->json(['reload_page' => true]);
        }
    }

    public function read(Permission $permission)
    {
        return view('lap::permissions.read', compact('permission'));
    }

    public function updateForm(Permission $permission)
    {
        return view('lap::permissions.update', compact('permission'));
    }

    public function update(Permission $permission)
    {
        $this->validate(request(), [
            'group' => 'required',
            'name' => 'required',
        ]);

        activity('Updated Permission: ' . $permission->id, request()->all(), $permission);
        flash(['success', 'Permission updated!']);

        $permission->update(request()->all());
        
        if (request()->input('_submit') == 'redirect') {
            return response()->json(['redirect' => session()->pull('url.intended', route('admin.permissions'))]);
        }
        else {
            return response()->json(['reload_page' => true]);
        }
    }

    public function delete(Permission $permission)
    {
        $permission->delete();

        activity('Deleted Permission: ' . $permission->id, $permission->toArray());
        $flash = ['success', 'Permission deleted!'];

        if (request()->input('_submit') == 'reload_datatables') {
            return response()->json([
                'flash' => $flash,
                'reload_datatables' => true,
            ]);
        }
        else {
            flash($flash);

            return redirect()->route('admin.permissions');
        }
    }
}