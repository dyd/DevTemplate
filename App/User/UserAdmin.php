<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/25/2017
 * Time: 1:36 PM
 */

namespace App\User;

use App\User;

class UserAdmin extends \App\User\User
{
	public function hasAdmin()
	{
		return true;
	}

	public function hasCRUD()
	{
		return true;
	}

	public function hasEDIT()
	{
		return true;
	}

	public function hasVIEW()
	{
		return true;
	}
}