<?php
/**
 * PHP Library package of Les Ateliers Pierrot
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <https://github.com/atelierspierrot/library>
 */

namespace Library;

use \Library\Helper\Url as UrlHelper;
use \Library\Helper\Text as TextHelper;
use \Patterns\Interfaces\RouterInterface;
use \Patterns\Commons\Collection;

/**
 * The global router class
 *
 * @author 		Piero Wbmstr <piero.wbmstr@gmail.com>
 */
class Router implements RouterInterface
{

    /**
     * Current URL to work on
     * @var string
     */
    protected $url;

    /**
     * Current route of the handled request
     * @var string
     */
    protected $route;

    /**
     * Current route parsing result
     * @var array
     */
    protected $route_parsed;

    /**
     * A collection of available routes mapping
     * @var \Patterns\Commons\Collection
     */
    protected $routes_collection;

    /**
     * A collection of arguments correspondances
     * @var \Patterns\Commons\Collection
     */
    protected $arguments_collection;

    /**
     * A collection of masks to expand routes
     * @var \Patterns\Commons\Collection
     */
    protected $matchers_collection;

    /**
     * Construction
     *
     * @param string $route
     * @param array|object $routes_table
     * @param array|object $arguments_table
     * @param array|object $matchers_table
     */
    public function __construct(
        $route = null, array $routes_table = array(), array $arguments_table = array(), array $matchers_table = array()
    ) {
        if (!empty($routes_table)) {
            $this->setRoutes($this->getCollection($routes_table));
        }
        if (!empty($arguments_table)) {
            $this->setArgumentsMap($this->getCollection($arguments_table));
        }
        if (!empty($matchers_table)) {
            $this->setMatchers($this->getCollection($matchers_table));
        }
        if (!empty($route)) {
            $this->setRoute($route);
        }
    }

    /**
     * Get a collection object if it was not
     *
     * @param array|object $collection
     *
     * @return \Patterns\Commons\Collection
     */
    public function getCollection(array $collection)
    {
        if ($collection instanceof \Patterns\Commons\Collection) {
            return $collection;
        } else {
            return new Collection($collection);
        }
    }

// ----------------------
// Setters / Getters
// ----------------------

    /**
     * Set the current URL
     *
     * @param string $url
     *
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get the current URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the current route
     *
     * @param string $route
     *
     * @return self
     */
    public function setRoute($route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Get the current route
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Set the current route parsed infos
     *
     * @param array $infos
     *
     * @return self
     */
    public function setRouteParsed($infos)
    {
        $this->route_parsed = $infos;
        return $this;
    }

    /**
     * Get the current route parsed infos
     *
     * @return array
     */
    public function getRouteParsed()
    {
        return $this->route_parsed;
    }

    /**
     * Set the routes collection
     *
     * @param obj $collection \Patterns\Commons\Collection
     *
     * @return self
     */
    public function setRoutes(Collection $collection)
    {
        $this->routes_collection = $collection;
        return $this;
    }

    /**
     * Get the routes collection
     *
     * @return \Patterns\Commons\Collection
     */
    public function getRoutes()
    {
        return $this->routes_collection;
    }

    /**
     * Set the arguments correspondances table like ( true arg in URL => true arg name in the app )
     *
     * @param obj $collection \Patterns\Commons\Collection
     *
     * @return self
     */
    public function setArgumentsMap(Collection $collection)
    {
        $this->arguments_collection = $collection;
        return $this;
    }

    /**
     * Get the arguments table
     *
     * @return \Patterns\Commons\Collection
     */
    public function getArgumentsMap()
    {
        return $this->arguments_collection;
    }

    /**
     * Set a collection of masks to parse and match a route URL
     *
     * @param obj $collection \Patterns\Commons\Collection
     *
     * @return self
     */
    public function setMatchers(Collection $collection)
    {
        $this->matchers_collection = $collection;
        return $this;
    }

    /**
     * Get the route matcher
     *
     * @return \Patterns\Commons\Collection
     */
    public function getMatchers()
    {
        return $this->matchers_collection;
    }

    /**
     * Check if a route exists
     *
     * @param string $route The route to test
     *
     * @return bool
     */
    public function routeExists($route)
    {
        return is_array($routes = $this->getRoutes()) && isset($routes[$route]);
    }

// ----------------------
// Processes & utilities
// ----------------------

	/**
	 * URL parser : load and parse the current URL
	 *
	 * 
	 */
	protected function _parseUrl()
	{
		$url_frgts = UrlHelper::parse($this->getUrl());
		$route = array('all'=>array());
		if (!empty($url_frgts['params'])) {
			$frgts = array();
			$url_args = $this->getArgumentsMap();
			foreach ($url_frgts['params'] as $_var=>$_val) {
				if (isset($url_args[$_var])) {
                    $route[$url_args[$_var]] = $_val;
				} else {
                    $route['all'][$_var] = $_val;
				}
			}
		}
		$this->setRouteParsed($route);
	}

	/**
	 * Route parser : load and parse the current route
	 */
	protected function _parseRoute()
	{
		$route_rule = $this->matchUrl($this->getRoute());
		if (!empty($route_rule)) {
    		$this->setRouteParsed($route_rule);
		}
	}

	/**
	 * Special 'urlencode' function to only encode strings and let any "%s" mask not encoded
	 *
	 * @param string $str The URL or argument to encode
	 * @param bool $keep_mask
	 *
	 * @return string The encoded URL if so
	 */
	public static function urlEncode($str = null, $keep_mask = true)
	{
		if (
		    (!empty($str) && is_numeric($str)) ||
		    (true===$keep_mask && $str==='%s')
		) {
		    return $str;
		}
		if (empty($str) || !is_string($str)) {
		    return '';
		}
		return urlencode($str);
	}

    /**
     * Build a new route URL
     *
	 * The class will pass arguments values to any `$this->toUrlParam($value)` method for the 
	 * parameter named `param`.
	 *
     * @param misc $route_infos The informations about the route to analyze, can be a string route or an array
     *                  of arguments like `param => value`
     * @param string $base_uri The URI to add the new route to
     * @param string $hash A hash tag to add to the generated URL
	 * @param string $separator The argument/value separator (default is escaped ampersand : '&amp;')
	 *
     * @return string The application valid URL for the route
     *
     * @todo manage the case of $route_infos = route
     */
    public function generateUrl($route_infos, $base_uri, $hash = null, $separator = '&amp;')
	{
		$url_args = $this->getArgumentsMap()->getCollection();
		
		$url = $base_uri;

		if (is_array($route_infos)) {
    		$final_params = array();
		    foreach ($route_infos as $_var=>$_val) {
				if (!empty($_val)) {
					$arg = in_array($_var, $url_args) ? array_search($_var, $url_args) : $_var;
					$_meth = 'toUrl'.TextHelper::toCamelCase($_var);
					if (method_exists($this, $_meth)) {
					    $_val = call_user_func_array(array($this, $_meth), array($_val));
					}
                    if (is_string($_val)) {
                        $final_params[$this->urlEncode($arg)] = $this->urlEncode($_val);
                    } elseif (is_array($_val)) {
                        foreach($_val as $_j=>$_value) {
                            $final_params[$this->urlEncode($arg).'['.(is_string($_j) ? $_j : '').']'] = $this->urlEncode($_val);
                        }
                    }
				}
		    }
            $url .= '?'.http_build_query($final_params, '', $separator);
		}

        if (!empty($hash)) $url .= '#'.$hash;
        return $url;
	}

    /**
     * Test if an URL has a corresponding route
     *
     * @param misc $pathinfo
     *
     * @return false|misc
     */
    public function matchUrl($pathinfo)
    {
        $routes = $this->getRoutes();
        if (!empty($routes) && isset($routes[$pathinfo])) {
            return $routes[$pathinfo];
        }
        return false;
    }

    /**
     * Actually dispatch the current route
     *
     * @return self
     */
    public function distribute()
    {
        $route = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);
        if (!empty($route) && in_array($route{0}, array('?', '/', '&'))) {
            $route = substr($route, 1);
        }
        if (!empty($route) && $this->matchUrl($route)) {
            $this->setRoute($route)->_parseRoute();
        } elseif (!empty($this->url)) {
            $this->_parseUrl();
        } else {
            throw new \RuntimeException('No route or URL to analyze and distribute!');
        }
        return $this;
    }

    /**
     * Forward the application to a new route (no HTTP redirect)
     *
     * @param misc $pathinfo The path information to forward to
     * @param string $hash A hash tag to add to the generated URL
     *
     * @return void
     */
    public function forward($pathinfo, $hash = null)
    {
    }

    /**
     * Make a redirection to a new route (HTTP redirect)
     *
     * @param misc $pathinfo The path information to redirect to
     * @param string $hash A hash tag to add to the generated URL
     *
     * @return void
     */
    public function redirect($pathinfo, $hash = null)
	{
	    $uri = is_string($pathinfo) ? $pathinfo : $this->generateUrl($pathinfo);
		if (!headers_sent()) {
            header("Location: $uri");
		} else {
			echo <<<MESSAGE
<!DOCTYPE HTML>
<head>
<meta http-equiv='Refresh' content='0; url={$uri}'><title>HTTP 302</title>
</head><body>
<h1>HTTP 302</h1>
<p>Your browser will be automatically redirected.
<br />If not, please clic on next link: <a href="{$uri}">{$uri}</a>.</p>
</body></html>
MESSAGE;
		}
		exit;
	}

}

// Endfile