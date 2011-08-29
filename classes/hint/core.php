<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Light "flash" messages for the Kohana framework.
 *
 * @package    Hint
 * @category   Base
 * @author     Gregorio Ramirez
 * @copyright  (c) 2011 Gregorio Ramirez
 * @license    MIT
 * @see        http://github.com/goyote/hint/
 */
class Hint_Core {

	// Message types
	const ERROR   = 'error';
	const SUCCESS = 'success';
	const NOTICE  = 'notice';
	const ALERT   = 'alert';
	const ACCESS  = 'access';
	const WARNING = 'warning';

	/**
	 * @var  string  session key used for storing messages
	 */
	public static $storage_key = 'hint';

	/**
	 * @var  string  default view name
	 */
	public static $view = 'hint/default';

	/**
	 * Set a new message.
	 *
	 *     Hint::set(Hint::SUCCESS, 'Your account has been deleted');
	 *
	 *     // Embed some values with sprintf
	 *     Hint::set(Hint::ERROR, '%s is not writable', array($file));
	 *
	 *     // Embed some values with strtr
	 *     Hint::set(Hint::ERROR, ':file is not writable', 
	 *         array(':file' => $file));
	 *
	 * @param   string  message type (e.g. Hint::SUCCESS)
	 * @param   mixed   message text or array of messages
	 * @param   array   values to replace with sprintf or strtr
	 * @param   mixed   custom data
	 */
	public static function set($type, $text, array $values = NULL, $data = NULL)
	{
		if (is_array($text))
		{
			foreach ($text as $message)
			{
				// Recursively set each message
				Hint::set($type, $message);
			}

			return;
		}

		if ($values)
		{
			if (Arr::is_assoc($values))
			{
				// Insert the values into the message
				$text = strtr($text, $values);
			}
			else
			{
				// The target string goes first
				array_unshift($values, $text);

				// Insert the values into the message
				$text = call_user_func_array('sprintf', $values);
			}
		}

		// Load existing messages
		$messages = Hint::get(NULL, array());

		// Append a new message
		$messages[] = array(
			'type' => $type,
			'text' => $text,
			'data' => $data,
		);

		// Store the updated messages
		Session::instance()->set(Hint::$storage_key, $messages);
	}

	/**
	 * Set a new message using the `messages/hint` file.
	 *
	 *     // The array path to the message
	 *     Hint::error('user.login.error');
	 *
	 *     // Embed some values
	 *     Hint::success('user.login.success', array($username));
	 *
	 * @param  string  message type (e.g. Hint::SUCCESS)
	 * @param  array   remaining parameters
	 * @uses   __()
	 */
	public static function __callStatic($type, $arg)
	{
		// Get the message
		$message = Kohana::message('hint', $arg[0]);

		Hint::set($type, __($message), Arr::get($arg, 1), Arr::get($arg, 2));
	}

	/**
	 * Get messages.
	 *
	 *     $messages = Hint::get();
	 *
	 *     // Get error messages
	 *     $error_messages = Hint::get(Hint::ERROR);
	 *
	 *     // Get error and alert messages
	 *     $messages = Hint::get(array(Hint::ERROR, Hint::ALERT));
	 *
	 *     // Get everything except error and alert messages
	 *     $messages = Hint::get(array(1 => array(Hint::ERROR, Hint::ALERT)));
	 *
	 *     // Customize the default value
	 *     $error_messages = Hint::get(Hint::ERROR, 'my default value');
	 *
	 * @param   mixed  message type (e.g. Hint::SUCCESS, array(Hint::ERROR, Hint::ALERT))
	 * @param   mixed  default value to return
	 * @param   bool   delete the messages?
	 * @return  mixed
	 */
	public static function get($type = NULL, $default = NULL, $delete = FALSE)
	{
		// Load existing messages
		$messages = Session::instance()->get(Hint::$storage_key);
		
		if ($messages === NULL)
		{
			// No messages found
			return $default;
		}

		if ($type !== NULL)
		{			
			// Will hold the filtered set of messages to return
			$return = array();

			// Store the remainder in case `delete` or `get_once` is called
			$remainder = array();

			foreach ($messages as $message)
			{
				if (($message['type'] === $type)
					OR (is_array($type) AND in_array($message['type'], $type))
					OR (is_array($type) AND Arr::is_assoc($type) AND ! in_array($message['type'], $type[1])))
				{
					$return[] = $message;
				}
				else
				{
					$remainder[] = $message;
				}
			}

			if (empty($return))
			{
				// No messages of '$type' to return
				return $default;
			}

			$messages = $return;
		}

		if ($delete === TRUE)
		{
			if ($type === NULL OR empty($remainder))
			{
				// Nothing to save, delete the key from memory
				Hint::delete();
			}
			else
			{
				// Override messages with the remainder to simulate a deletion
				Session::instance()->set(Hint::$storage_key, $remainder);
			}
		}

		return $messages;
	}

	/**
	 * Get messages once.
	 *
	 *     $messages = Hint::get_once();
	 *
	 *     // Get error messages
	 *     $error_messages = Hint::get_once(Hint::ERROR);
	 *
	 *     // Get error and alert messages
	 *     $error_messages = Hint::get_once(array(Hint::ERROR, Hint::ALERT));
	 *
	 *     // Get everything except error and alert messages
	 *     $messages = Hint::get_once(array(1 => array(Hint::ERROR, Hint::ALERT)));
	 *
	 *     // Customize the default value
	 *     $error_messages = Hint::get_once(Hint::ERROR, 'my default value');
	 *
	 * @param   mixed  message type (e.g. Hint::SUCCESS, array(Hint::ERROR, Hint::ALERT))
	 * @param   mixed  default value to return
	 * @return  mixed
	 */	
	public static function get_once($type = NULL, $default = NULL)
	{
		return Hint::get($type, $default, TRUE);
	}

	/**
	 * Delete messages.
	 *
	 *     Hint::delete();
	 *
	 *     // Delete error messages
	 *     Hint::delete(Hint::ERROR);
	 *
	 *     // Delete error and alert messages
	 *     Hint::delete(array(Hint::ERROR, Hint::ALERT));
	 *
	 * @param  mixed  message type (e.g. Hint::SUCCESS, array(Hint::ERROR, Hint::ALERT))
	 */
	public static function delete($type = NULL)
	{
		if ($type === NULL)
		{
			// Delete everything!
			Session::instance()->delete(Hint::$storage_key);
		}
		else
		{
			// Deletion by type happens in get(), too weird?
			Hint::get($type, NULL, TRUE);
		}
	}
	
	/**
	 * Render messages (deletes them by default.)
	 *
	 *     <div id="wrapper">
	 *         ...
	 *         <?php echo Hint::render() ?>
	 *
	 * @param   mixed   message type (e.g. Hint::SUCCESS, array(Hint::ERROR, Hint::ALERT))
	 * @param   bool    delete the messages?
	 * @param   mixed   View name or object
	 * @return  string  rendered View
	 */
	public static function render($type = NULL, $delete = TRUE, $view = NULL)
	{
		if (($messages = Hint::get($type, NULL, $delete)) === NULL)
		{
			// No messages to return
			return '';
		}

		if ($view === NULL)
		{
			// Use the default view
			$view = Hint::$view;
		}

		if ( ! $view instanceof View)
		{
			// Load the view file
			$view = new View($view);
		}

		return $view
			->set('messages', $messages)
			->render();
	}
	
} // End Hint_Core
