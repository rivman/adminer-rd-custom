<?php
	/**
	 * Using https://github.com/pematon/adminer-custom
	 */


	function adminer_object (): AdminerPlugin
	{
		// Required to run any plugin.
		include_once "./plugins/plugin.php";

		// Plugins auto-loader.
		foreach (glob ("plugins/*.php") as $filename) {
			include_once "./$filename";
		}

		// Specify enabled plugins here.
		$plugins = [
			new AdminerDatabaseHide(["mysql" , "information_schema" , "performance_schema"]) ,
			new AdminerTablesFilter() ,
			new AdminerFloatThead ,
			new AdminerDumpZip ,
			new AdminerReadableDates ,
			new AdminerDumpArray ,
			new AdminerSimpleMenu() ,
			new AdminerCollations() ,
			new AdminerJsonPreview() ,

			// AdminerTheme has to be the last one.
			new AdminerTheme() ,
		];


		return new AdminerPlugin($plugins);
	}

	// Include original Adminer or Adminer Editor.
	$git_url = 'https://api.github.com/repos/vrana/adminer/releases/latest';
	$useragent = 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0';


	/**
	 * Get Adminer latest released version from GitHub API
	 *
	 * @param $url
	 * @param $useragent
	 * @return bool|array
	 */
	function get_adminer_latest_version ($url , $useragent)
	{
		$ch = curl_init ();

		curl_setopt ($ch , CURLOPT_AUTOREFERER , true);
		curl_setopt ($ch , CURLOPT_USERAGENT , $useragent);
		curl_setopt ($ch , CURLOPT_HEADER , 0);
		curl_setopt ($ch , CURLOPT_RETURNTRANSFER , 1);
		curl_setopt ($ch , CURLOPT_URL , $url);
		curl_setopt ($ch , CURLOPT_FOLLOWLOCATION , true);
		curl_setopt ($ch , CURLOPT_FAILONERROR , true);
		// Do not use SSL verifications, this is to make it work in local self-signed installations (Laragon)
		curl_setopt ($ch , CURLOPT_SSL_VERIFYPEER , false);

		$data = curl_exec ($ch);
		$http_status = curl_getinfo ($ch , CURLINFO_HTTP_CODE);
		$curl_errno = curl_errno ($ch);
		if (isset($http_status) && $http_status == 503) {
			echo "HTTP Status == 503 <br/>";
		}
		if ($curl_errno) {
			echo "Error in executing Curl : $curl_errno <br/>";
		}
		curl_close ($ch);

		return $data;
	}

	// Get latest version number from Github API
	$latest = json_decode (get_adminer_latest_version ($git_url , $useragent) , true);

	$latest_tag_version = $latest['tag_name'];
	$latest_file_name = str_replace ('v' , '' , $latest_tag_version);

	// Get current local installed version
	if (file_exists ('current-version')) {
		$current_local_version = file_get_contents ('./current-version');
	} else {
		$current_local_version = '0.0.0';
	}

	// Check if local version is different from online, then download and update local version file
	if ($current_local_version !== $latest_tag_version) {
		// New version found online, downloading new version

		// Write latest version to file
		$fp = fopen ('./current-version' , 'w+');
		$version = fwrite ($fp , $latest_tag_version);

		// Download latest version, from Github releases, and write adminer.php file to disk.
		$latest_version_url_download = 'https://github.com/vrana/adminer/releases/download/' . $latest_tag_version . '/adminer-' . $latest_file_name . '.php';
		$latest_adminer_file = get_adminer_latest_version ($latest_version_url_download , $useragent);

		// Write locally latest version
		if ($latest_adminer_file) {
			$fp = fopen ('./adminer.php' , 'w+');
			$result = fwrite ($fp , $latest_adminer_file);
		}
		$result = false;
		fclose ($fp);

		if (!$result) {
			echo ('Adminer latest version retrieval failed, local file not written, using the local latest version.<br>');
		}

		// File is written, continue loading, and make a check to see if the file is a empty one (curl error, on retrieval)
		if ($filecontent = file_get_contents ('adminer.php') !== false) {
			include_once 'adminer.php';
		} else {
			echo "Adminer file is empty, not loaded.";
		}
	} else {
		// Latest version should be already present locally, skip the download and include the Adminer file if there is not empty file";
		if ($filecontent = file_get_contents ('adminer.php') !== false) {
			include_once 'adminer.php';
		} else {
			echo "Adminer file is empty (missing content), not loaded.";
		}
	}
