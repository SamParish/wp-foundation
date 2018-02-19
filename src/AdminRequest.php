<?php


namespace SamParish\WordPress;


use Illuminate\Http\Request;

class AdminRequest extends Request
{

	/**
	 * Prepares the path info.
	 *
	 * @return string path info
	 */
	protected function preparePathInfo()
	{
		/*
		 * This method is used to obtain the path to match to routes.
		 * Because the entry point to wordpress admin pages is not static (i.e. index.php) then the standard request
		 * will always return '/' as the current url path.
		 *
		 * What we need to do is override the baseUrl as this is incorrect.
		 *
		 * Instead we return the requestUri
		 *
		 */



		if (null === ($requestUri = $this->getRequestUri())) {
			return '/';
		}

		// Remove the query string from REQUEST_URI
		if (false !== $pos = strpos($requestUri, '?')) {
			$requestUri = substr($requestUri, 0, $pos);
		}

		if ($requestUri !== '' && $requestUri[0] !== '/') {
			$requestUri = '/'.$requestUri;
		}

		return (string)$requestUri;
	}

	/**
	 * Overridden method to prevent the Request from converted uploaded files.
	 *
	 * If we allow this to happen then wordpress will throw a http error.
	 *
	 * @param  array  $files
	 * @return array
	 */
	protected function convertUploadedFiles(array $files)
	{
		return $files;
	}

}