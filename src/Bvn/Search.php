<?php declare(strict_types=1);
/**
 * @license MIT
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 *
 * This file is part of the NIRSAL AGC project by Skylab, please read the license document
 * available in the root level of the project
 */
namespace Skylab\NirsalAgc\Plugins\Bvn;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;

use Skylab\NirsalAgc\Plugins\NibssRequest\Endpoints as NibssEndpoint;

/**
 * class Search.
 *
 * Search Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 26/07/2021 18:47
 */
class Search
{
	private static function interceptRequest(array $data) {
		$bvnList = $data["bvnList"];
		$userId = (int)$data["userId"];

		$forNibss = [];
		$notForNibss = [];

		$localBvn = self::getLocalBvn($bvnList);

		$searchResponse = [];

		foreach ($localBvn as $bvn) {
			$notForNibss[] = $bvn["bvn"];
			$searchResponse[$bvn["bvn"]] = true;
		}

		$forNibss = array_diff($bvnList, $notForNibss);

		if (count($forNibss) > 0){
			$searchResponse = $searchResponse + self::retrieveFromNibss($forNibss, $userId);
		}

		return $searchResponse;
	}

	private static function retrieveFromNibss(array $bvnList, int $userId) {
		$bvnListAsString = implode(",", $bvnList);
		$nibssResponse = NibssEndpoint::getMultipleBvn(["bvns"=>$bvnListAsString]);
		$response = [];

		foreach($nibssResponse as $resp){
			$bvn = $resp["BVN"];
			if ($resp["ResponseCode"] == "00"){ //means bvn search returned valid data
				unset($resp["ResponseCode"], $resp["BVN"]);

				self::indexBvnData($bvn, $userId, $resp);
				$response[$bvn] = true;
			}
			else {
				$response[$bvn] = $resp;
			}
		}

		return $response;
	}

	private static function getLocalBvn(array $bvnList) {
		array_walk($bvnList, function(&$x) {$x = "'$x'";});
		$bvnListAsString = implode(",", $bvnList);
		$query = "SELECT bvn FROM bvn_retrieved_bvns WHERE bvn IN ($bvnListAsString)";

		$result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		return $result;
	}

	private static function indexBvnData(string $bvn, int $userId, array $bvnData){
		$inserts = [];
		foreach($bvnData as $field => $value){
			$value = htmlspecialchars($value, ENT_QUOTES);
			$inserts[] = "('$bvn', '$field', '$value')";
		}

		$query = "INSERT INTO bvn_retrieved_bvns (bvn, retrieved_by) VALUES ('$bvn', '$userId');";
		$query .= "INSERT INTO bvn_retrieved_bvn_data (bvn, data_field, data_value) VALUES ".implode(",", $inserts);
		$response = DBConnectionFactory::getConnection()->exec($query);

		return $response;
	}

	public static function getBvnData(array $data)
	{
		$presearchData = self::interceptRequest($data);
		$requestedFields = $data["fields"] ?? [];

		if (count($requestedFields) < 1){
			$requestedFields[] = "NameOnCard";
		}

		$response = [];
		$validBvns = [];

		foreach ($presearchData as $bvn=>$resp) {
			if ($resp === true) {
				$validBvns[] = $bvn;
			}
			else {
				$response[$bvn] = ["status"=>0, "reason"=>$resp];
			}
		}

		array_walk($validBvns, function(&$x) {$x = "'$x'";});
		$bvnListAsString = implode(",", $validBvns);

		array_walk($requestedFields, function(&$x) {$x = "'$x'";});
		$fieldsAsString = implode(",", $requestedFields);

		$query = "SELECT a.bvn, a.data_field, a.data_value FROM bvn_retrieved_bvn_data a WHERE (a.data_field IN ($fieldsAsString)) AND (a.bvn IN ($bvnListAsString))";

		$queryResult = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

		foreach ($queryResult as $result){
			$bvn = $result["bvn"];
			$field = $result["data_field"];
			$value = $result["data_value"];

			if (!isset($response[$bvn])){
				$response[$bvn] = [
					"status"=>1,
					"data"=>[]
				];
			}

			$response[$bvn]["data"][$field] = $value;
		}

		return $response;
	}
}