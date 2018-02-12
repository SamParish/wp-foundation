<?php

//some useful helpers
if ( ! function_exists('dd'))
{
	/**
	 * Dies and dumps.
	 *
	 * @return string
	 */
	function dd()
	{
		call_user_func_array('dump', func_get_args());
		die;
	}
}


if(! function_exists( 'wpView' ))
{
	/**
	 * @param        $pluginName
	 * @param string $path
	 * @param array  $data
	 *
	 * @return string
	 */
	function wpView($pluginName, $path,$data=[])
	{
		return \JB000\WordPress\PluginContext::instance($pluginName)->view($path,$data);
	}

}

if (! function_exists( 'wpAppPath' )) {
	/**
	 * Get the path to the application folder.
	 *
	 * @param         $pluginName
	 * @param  string $path
	 *
	 * @return string
	 */
	function wpAppPath($pluginName, $path = '')
	{
		$context = \JB000\WordPress\PluginContext::instance($pluginName);
		return $context->pluginPath().($path ? DIRECTORY_SEPARATOR.$path : $path);
	}
}