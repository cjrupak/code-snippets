<?php

namespace App\Modules\Permissions\Repositories;

use App\Modules\Permissions\Models\Permissions;
use Czim\Repository\BaseRepository;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\Exceptions\Exception;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionsRepository extends BaseRepository {
	/**
	 * Returns specified model class name.
	 *
	 * @return string
	 */
	public function model() {
		return Permissions::class;
	}

	/**
	 * @return mixed
	 * @throws \Exception
	 */
	public function getDataTable() {
		$model = $this->model->select( '*' )->orderBy( 'created_at', 'DESC' );
		try {
			return Datatables::of( $model )
			                 ->addColumn( 'action', function ( $query ) {
								 //$show_route = route( 'admin.permissions.show', ['id' => $query->id] );
								 $show_route = '';
				                 $edit_route = route( 'admin.permissions.edit', ['id' => $query->id] );
				                 $destroy_route = route( 'admin.permissions.destroy', ['id' => $query->id] );

				                 return appHelpers()->gridActions( $query->id, $show_route, $edit_route, $destroy_route );
			                 } )
			                 ->rawColumns( ['action'] )
			                 ->make( true );
		} catch( Exception $e ) {
			dd( $e->getMessage() );
		}
	}

	public function rolesPermission($roleId) {
		$role = Role::findById($roleId);
		$allPermission = $role->permissions->pluck('name')->toArray();
		return $allPermission;
	}

	public function assignPermission($data) {
		// As we need to make lots of db operations so we are using transaction and exception handling here
		return DB::transaction(function () use ($data) {
			$roleId = $data['role'];
			$role = Role::findById($roleId);
			if (!isset($data['permission'])) {
				$role->syncPermissions([]);
				return $roleId;
			}
			$rolesPermissionData = [];
			$assignPermissionData = collect($data['permission'])->keys()->toArray();
			foreach ($assignPermissionData as $pKey => $pVal) {
				$confirmPermissionCreated = Permission::where(['name' => $pVal])->pluck('name','id')->toArray();
				if (empty($confirmPermissionCreated)) {
					$create_permission = Permission::create(['name' => $pVal]);
					$rolesPermissionData[] = $create_permission->id;
				} else {
					$rolesPermissionData[] = key($confirmPermissionCreated);
				}

			}
			$role->syncPermissions($rolesPermissionData);
			return $roleId;
		});
	}

}
