<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/25/2017
 * Time: 1:42 PM
 */

namespace App;

use App\User\UserAdmin;
use App\User\UserCRUD;
use App\User\UserEDIT;
use App\User\UserGuest;
use App\User\UserVIEW;

class UserFactory
{
	/**
	 * RETURN USER
	 *
	 * @param $user
	 * @param $database
	 * @return User\User
	 */
	public static function create($user, $database)
	{
		switch ($user->rights) {
			case USER_RIGHT_ADMINISTRATOR:
				return new UserAdmin($user, $database);
				break;
			case USER_RIGHT_CRUD:
				return new UserCRUD($user, $database);
				break;
			case USER_RIGHT_EDIT:
				return new UserEDIT($user, $database);
				break;
			case USER_RIGHT_VIEW:
				return new UserVIEW($user, $database);
				break;
			default :
				return new UserGuest($user, $database);
				break;
		}
	}
}