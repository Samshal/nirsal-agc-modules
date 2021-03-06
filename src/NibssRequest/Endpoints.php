<?php declare(strict_types=1);
/**
 * @license MIT
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 *
 * This file is part of the NIRSAL AGC project by Skylab, please read the license document
 * available in the root level of the project
 */
namespace Skylab\NirsalAgc\Plugins\NibssRequest;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;

/**
 * class Endpoints.
 *
 * Endpoints Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 07/07/2021 14:28
 */
class Endpoints
{
	public static function reset()
	{
		
	}

	public static function verifySingleBvn(array $data)
	{
		$request = new XHttpRequest();
		$response = $request->httpPostRequest("/VerifySingleBVN", [
			"BVN"=>$data["bvn"]
		]);

		return $response;
	}
	
	public static function verifyMultipleBvn(array $data)
	{
		$request = new XHttpRequest();
		$response = $request->httpPostRequest("/VerifyMultipleBVN", [
			"BVNS"=>$data["bvns"]
		]);

		return $response;
		
	}

	public static function getSingleBvn(array $data)
	{
		$request = new XHttpRequest();
		$response = $request->httpPostRequest("/GetSingleBVN", [
			"BVN"=>$data["bvn"]
		]);

		return $response;
		
	}
	
	public static function getMultipleBvn(array $data)
	{
		$request = new XHttpRequest();
		$response = $request->httpPostRequest("/GetMultipleBVN", [
			"BVNS"=>$data["bvns"]
		]);

		if (isset($response["status_code"]) && $response["status_code"] == "200"){
			$response = $response["body"]["ValidationResponses"];
		}
		else {
			//handle this error.
		}


		return $response;
		
	}
}