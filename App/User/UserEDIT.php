<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 7/25/2017
 * Time: 1:40 PM
 */

namespace App\User;


use App\User;

class UserEDIT extends \App\User\User
{
	public function hasEDIT()
	{
		return true;
	}

	public function hasVIEW()
	{
		return true;
	}
}