<?php
/**
 * Acts as an object wrapper for HTML pages with embedded PHP, called "views".
 * Variables can be assigned with the view object and referenced locally within
 * the view.
 *
 * $Id: view.php 3740 2008-12-01 20:24:43Z Geert $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class View_Core {

	// Array of global variables
	protected static $global_data = array();

	/**
	 * Returns a new View object.
	 *
	 * @chainable
	 * @param   string  view filename
	 * @param   array   array of values
	 * @return  View
	 */
	public static function factory($file = NULL, array $data = array())
	{
		return new View($file, $data);
	}

	/**
	 * Captures the output that is generated when a view is included.
	 * The view data will be extracted to make local variables. This method
	 * is static to prevent object scope resolution.
	 *
	 * @param   string  filename
	 * @param   array   variables
	 * @return  string
	 */
	protected static function capture($kohana_view_filename, array $kohana_view_data)
	{
		// Import the view variables to local namespace
		extract($kohana_view_data, EXTR_SKIP);

		// Capture the view output
		ob_start();

		// Load the view within the current scope
		include $kohana_view_filename;

		// Get the captured output and close the buffer
		return ob_get_clean();
	}

	// View filename
	protected $file;

	// Array of local variables
	protected $data = array();

	/**
	 * Sets the initial view filename and local data.
	 *
	 * @param   string  view filename
	 * @param   array   array of values
	 * @return  void
	 */
	public function __construct($file = NULL, array $data = array())
	{
		if ( ! empty($file))
		{
			$this->set_filename($file);
		}

		if ( ! empty($data))
		{
			$this->data = array_merge($this->data, $data);
		}
	}

	/**
	 * Magic method, searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 * @param   string  variable name
	 * @return  mixed
	 */
	public function __get($key)
	{
		if (isset($this->data[$key]))
		{
			return $this->data[$key];
		}
		elseif (isset(View::$global_data[$key]))
		{
			return View::$global_data[$key];
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * Magic method, calls set() with the same parameters.
	 *
	 * @param   string  variable name
	 * @param   mixed   value
	 * @return  void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Magic method, returns the output of render(). If any exceptions are
	 * thrown, the exception output will be returned instead.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch (Kohana_Exception $e)
		{
			return (string) $e;
		}
	}

	/**
	 * Sets the view filename. If the view file cannot be found, an exception
	 * will be thrown.
	 *
	 * @chainable
	 * @throws  Kohana_Exception
	 * @param   string  filename
	 * @return  View
	 */
	public function set_filename($file)
	{
		if ($file = Kohana::find_file('views', $file))
		{
			$this->file = $file;
		}
		else
		{
			throw new Kohana_Exception('kohana.file_not_found', 'views', $file);
		}

		return $this;
	}

	/**
	 * Assigns a variable by name. Assigned values will be available as a
	 * variable within the view file:
	 *
	 *     // This value can be accessed as $foo within the view
	 *     $view->set('foo', 'my value');
	 *
	 * @chainable
	 * @param   string   variable name or an array of variables
	 * @param   mixed    value
	 * @return  View
	 */
	public function set($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $key2 => $value)
			{
				$this->data[$key2] = $value;
			}
		}
		else
		{
			$this->data[$key] = $value;
		}

		return $this;
	}

	/**
	 * Exactly the same as set, but assigns the value globally.
	 *
	 * @chainable
	 * @param   string   variable name or an array of variables
	 * @param   mixed    value
	 * @return  View
	 */
	public function set_global($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $key2 => $value)
			{
				View::$global_data[$key2] = $value;
			}
		}
		else
		{
			View::$global_data[$key] = $value;
		}

		return $this;
	}

	/**
	 * Assigns a value by reference. The benefit of binding is that values can
	 * be altered without re-setting them. It is also possible to bind variables
	 * before they have values. Assigned values will be available as a
	 * variable within the view file:
	 *
	 *     // This reference can be accessed as $ref within the view
	 *     $view->bind('ref', $bar);
	 *
	 * @chainable
	 * @param   string   variable name
	 * @param   mixed    referenced variable
	 * @return  View
	 */
	public function bind($key, & $value)
	{
		$this->data[$key] =& $value;

		return $this;
	}

	/**
	 * Exactly the same as bind, but assigns the value globally.
	 *
	 * @chainable
	 * @param   string   variable name
	 * @param   mixed    referenced variable
	 * @return  View
	 */
	public function bind_global($key, & $value)
	{
		View::$global_data[$key] =& $value;

		return $this;
	}

	/**
	 * Renders the view object to a string. Global and local data are merged
	 * and extracted to create local variables within the view file.
	 * Optionally, the view filename can be set before rendering.
	 *
	 * @throws   Kohana_Exception
	 * @param    string  filename
	 * @return   string
	 */
	public function render($file = NULL)
	{
		if ( ! empty($file))
		{
			$this->set_filename($file);
		}

		if (empty($this->file))
		{
			throw new Kohana_Exception('kohana.view.set_filename');
		}

		// Combine global and local data. Global variables with the same name
		// will be overwritten by local variables.
		$data = array_merge(View::$global_data, $this->data);

		return View::capture($this->file, $data);
	}

} // End View
