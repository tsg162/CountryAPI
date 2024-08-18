<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/CountriesAPI.php';

header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriSegments = explode('/', trim($uri, '/'));


$api = new CountriesAPI(array(
	"cache"=>"redis"
));

// Basic routing
switch ($uriSegments[1] ?? null) {
    case 'countries':
		$options = array();
		if(count($uriSegments) > 2) {
			if($uriSegments[2] == "name" && count($uriSegments) > 3) {
				$options["name"] = $uriSegments[3];
				if(isset($_REQUEST["fullText"]) && $_REQUEST["fullText"] == "true") {
					$options["fullText"] = true;
				}
				if(isset($_REQUEST["pageNum"])) {
					$options["pageNum"] = intval($_REQUEST["pageNum"]);
				}
				echo json_encode($api->getCountriesPaginated($options));
			} else if(count($uriSegments) == 3) {
				$countryCode = $uriSegments[2];
				echo json_encode($api->getCountryByCode($countryCode));
			}
		} else {
			//Get all the countries
			if(isset($_REQUEST["pageLength"])) {
				$options["pageLength"] = intval($_REQUEST["pageLength"]);
			}
			if(isset($_REQUEST["pageNum"])) {
				$options["pageNum"] = intval($_REQUEST["pageNum"]);
			}
			echo json_encode($api->getCountriesPaginated($options));
		}
        
        break;
		
    case 'regions':
        $options = array();
        if (isset($uriSegments[2]) && $uriSegments[2] === 'name' && isset($uriSegments[3])) {
            $options["region"] = $uriSegments[3];
        }
        if (isset($uriSegments[2]) && $uriSegments[2] === 'subregion' && isset($uriSegments[3])) {
            $options["subregion"] = $uriSegments[3];
        }
        echo json_encode($api->getCountriesByRegions($options));
        break;

    case 'languages':
        $options = array();
        if (isset($uriSegments[2]) && $uriSegments[2] === 'name' && isset($uriSegments[3])) {
            $options["language"] = $uriSegments[3];
        }
        echo json_encode($api->getCountriesByLanguages($options));
        break;
		
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Resource not found']);
        break;
}
?>
