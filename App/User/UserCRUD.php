<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/25/2017
 * Time: 1:39 PM
 */

namespace App\User;


use App\User;

class UserCRUD extends \App\User\User
{
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