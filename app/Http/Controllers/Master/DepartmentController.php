<?php

namespace App\Http\Controllers\Master;

use App\Models\Department;
use App\Support\MasterResource;

/** Pengganti controller CI3 Department (URL tetap /department). */
class DepartmentController extends MasterController
{
    protected function resource(): MasterResource
    {
        return new MasterResource(
            model: Department::class,
            prefix: 'department',
            title: 'Department',
            icon: 'bi-diagram-3',
            touchOnUpdate: false, // CI3: update_action hanya mengubah name
            fields: [
                'name' => [
                    'label' => 'Department Name',
                    'list_label' => 'Name',
                    'rules' => ['required', 'string', 'max:100'],
                    'placeholder' => 'e.g. Build Team',
                ],
            ],
        );
    }
}
