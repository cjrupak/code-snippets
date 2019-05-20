<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Repositories\UsersRepository;
use App\Modules\Users\Requests\CreateUserRequest;
use App\Modules\Users\Requests\UpdateUserRequest;
use Exception;

class AdminUsersController extends Controller {

	private $userRepository;

	public function __construct( UsersRepository $userRepository ) {
		$this->userRepository = $userRepository;
	}

	public function index() {
		return view( "Users::admin.index" );
	}

	public function dataTable() {
		return $this->userRepository->getDataTable();
	}

	public function create() {
		return view( "Users::admin.create" );
	}

	public function store( CreateUserRequest $request ) {
		try {
			$request->validated();
			$this->userRepository->create( $request->all() );

			return json_encode( ['status' => true, 'redirect_uri' => route( 'admin.users.index' )] );
		} catch( Exception $e ) { echo $e->getmessage();die;
			return json_encode( ['userStatus' => false, 'errors' => 'Something went wrong, please try again later.'] );
		}
	}

	public function show( $id ) {
		$model = $this->userRepository->find( $id );

		return view( "Users::admin.show", compact( 'model' ) );
	}

	public function edit( $id ) {
		$model = $this->userRepository->find( $id );

		return view( "Users::admin.edit", compact( 'model' ) );
	}

	public function update( UpdateUserRequest $request, $id ) {
		try {
			$request->validated();
			$this->userRepository->updateUser( $request->all(), $id );

			return json_encode( ['status' => true, 'redirect_uri' => route( 'admin.users.index' )] );
		} catch( Exception $e ) {
			return json_encode( ['userStatus' => false, 'errors' => 'Something went wrong, please try again later.'] );
		}
	}

	public function destroy( $id ) {
		$this->userRepository->delete( $id );

		return redirect()->route( 'admin.users.index' )->with( 'alert-success', 'User deleted successfully!' );
	}
}
