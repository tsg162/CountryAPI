<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class CountriesAPI {
    public function getCountryByCode($code) {
		//Handle requests like /api/countries/AW
		$url = 'https://restcountries.com/v3.1/alpha/' . $code;
		$data = @file_get_contents($url);
		if($data === false) {
			return [];
		}
		return $data;
	}
    function getCountries($options = array()) {
		// Default settings
		$fullText = $options['fullText'] ?? false;
		$url = 'https://restcountries.com/v3.1/';

		// Construct the API URL based on the presence of 'name' in options
		if (!empty($options['name'])) {
			$url .= "name/" . $options['name'];
			if ($fullText) {
				$url .= '?fullText=true';
			}
		} else {
			$url .= "all";
		}
		
		// Fetch data from the API
		$data = @file_get_contents($url);
		
		if ($data === false) {
			return [];
		}

		// Decode the JSON data into an associative array
		$countries = json_decode($data, true);
		if (!is_array($countries)) {
			return [];
		}

		return $countries;
	}

	public function getCountriesPaginated($options = array()) {
		$pageLength = $options['pageLength'] ?? 25;
		$pageNum = $options['pageNum'] ?? 1;
		$name = $options['name'] ?? null;

		// Use the helper function to fetch countries
		$countries = $this->getCountries($options);
		$pagedCountries = [];
		$nextPage = false;
		$totalCountries = count($countries);

		if (!empty($countries)) {
			// Sort countries by their 'common' name
			usort($countries, function($a, $b) {
				return strcmp($a['name']['common'], $b['name']['common']);
			});

			// Implement pagination
			$start = ($pageNum - 1) * $pageLength;
			$pagedCountries = array_slice($countries, $start, $pageLength);

			// Construct the next page URL
			if ($start + $pageLength < $totalCountries) {
				$baseUrl = "/api/countries";
				if ($name) {
					$baseUrl .= "/name/" . urlencode($name);
				}
				$nextPage = $baseUrl . "?pageNum=" . ($pageNum + 1) . "&pageLength=" . $pageLength;
			} else {
				$nextPage = false;
			}
		}

		// Trim unwanted properties
		foreach ($pagedCountries as $index => $country) {
			if (isset($country["idd"]) && isset($country["idd"]["suffixes"])) {
				unset($pagedCountries[$index]["idd"]["suffixes"]);
			}
		}

		$result = [
			"countries" => $pagedCountries,
			"page" => $pageNum,
			"nextPageUrl" => $nextPage,
			"total" => $totalCountries,
			"pages" => ceil($totalCountries / $pageLength)
		];

		// Return the result as a JSON response
		return $result;
	}



	public function getCountriesByRegions($options = array()) {
		$url = 'https://restcountries.com/v3.1/';
		if (isset($options["region"])) {
			$url .= "region/" . urlencode($options["region"]);
		} else {
			$url .= "all";
		}

		// Fetch and decode the data from the API
		$data = file_get_contents($url);
		$countries = json_decode($data, true);

		// Group countries by region
		$groupedCountries = [];
		foreach ($countries as $country) {
			$region = $country['region'];
			if (!isset($groupedCountries[$region])) {
				$groupedCountries[$region] = [];
			}
			$groupedCountries[$region][] = [
				'commonName' => $country['name']['common'],
				'officialName' => $country['name']['official'],
				'region' => $country['region']
			];
		}

		// Sort the grouped countries by region
		ksort($groupedCountries);

		// Construct the result array
		$result = ["regions" => array()];
		foreach ($groupedCountries as $region => $list) {
			$result["regions"][] = [
				"region" => $region,
				"countries" => $list
			];
		}

		// Return the result as a JSON response
		return $result;
	}

	public function getCountriesByLanguages($options = array()) {
		$countries = array();
		if (isset($options["language"])) {
			// Direct API call for specific language
			$url = 'https://restcountries.com/v3.1/lang/' . urlencode($options["language"]);
			$data = @file_get_contents($url);
			if ($data !== false) {
				$countries = json_decode($data, true);
			}
		} else {
			// Use getCountries class method to get all countries if no language specified
			$countries = $this->getCountries();
		}

		// Group countries by main language
		$groupedCountries = [];
		foreach ($countries as $country) {
			if (isset($country['languages'])) {
				foreach ($country['languages'] as $langCode => $langName) {
					if (!isset($options["language"]) || strtolower($options["language"]) == strtolower($langName) || strtolower($options["language"]) == strtolower($langCode)) {
						$groupedCountries[$langName][] = [
							'commonName' => $country['name']['common'],
							'officialName' => $country['name']['official'],
							'languages' => array_values($country['languages'])
						];
					}
				}
			}
		}

		// Sort the grouped countries by language
		ksort($groupedCountries);

		// Construct the result array
		$result = ["languages" => array()];
		foreach ($groupedCountries as $language => $list) {
			$result["languages"][] = [
				"language" => $language,
				"countries" => $list
			];
		}

		// Return the result as a JSON response
		return $result;
	}



}
?>
