<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Unit tests for the Hint module.
 *
 * @group      hint
 * @package    Unittest
 * @category   Tests
 * @author     Gregorio Ramirez
 * @copyright  (c) 2011 Gregorio Ramirez
 * @license    http://kohanaphp.com/license
 */
class HintTest extends Kohana_Unittest_TestCase {

	public function setUp()
	{
		parent::setUp();
		Session::instance()->destroy();
	}

	/**
	 * @test
	 * @covers  Hint::set
	 * @expectedException  ErrorException
	 */
	public function test_passing_no_arguments_to_set()
	{
		Hint::set();
	}

	/**
	 * @test
	 * @covers  Hint::set
	 * @expectedException  ErrorException
	 */
	public function test_passing_no_message_to_set()
	{
		Hint::set(Hint::ERROR);
	}

	/**
	 * @test
	 * @covers  Hint::set
	 * @expectedException  ErrorException
	 */
	public function test_passing_no_message_to_set_callStatic()
	{
		Hint::error();
	}

	/**
	 * @return  array
	 */
	public function provider_test_set_and_get()
	{
		return array(
			array(Hint::ERROR, 'teh bomb', NULL, NULL),
			array(Hint::ALERT, 23, NULL, FALSE),
			array(Hint::SUCCESS, 1.1, NULL, 'DUH'),
			array(Hint::WARNING, TRUE, NULL, array('foo' => 'bar', 'lol' => TRUE)),
		);
	}

	/**
	 * @test
	 * @dataProvider  provider_test_set_and_get
	 * @covers  Hint::get
	 * @covers  Hint::set
	 */
	public function test_set_and_get($type, $text, $values, $data)
	{
		Hint::set($type, $text, $values, $data);
		$messages = Hint::get_once();
		$this->assertType('array', $messages);
		$this->assertSame($type, $messages[0]['type']);
		$this->assertSame($text, $messages[0]['text']);
		$this->assertSame($data, $messages[0]['data']);
	}

	public function testCallStatic()
	{
		/*
		return array(
			'i' => array(
				'am' => array(
					'legend' => '%s',
				),
			),
		);
		*/
		$text = 'ROFL';
		$data = array('oh', 'my', 'god');
		Hint::error('i.am.legend', array($text), $data);
		$messages = Hint::get_once();
		$this->assertSame('error', $messages[0]['type']);
		$this->assertSame($text, $messages[0]['text']);
		$this->assertSame($data, $messages[0]['data']);
	}

	/**
	 * @covers  Hint::set
	 */
	public function testSettingMultipleMessages()
	{
		$array = array(
			'text1',
			'text2',
		);
		Hint::set(Hint::SUCCESS, $array);
		$messages = Hint::get();
		$this->assertSame('success', $messages[0]['type']);
		$this->assertSame('text1', $messages[0]['text']);
		$this->assertSame('success', $messages[1]['type']);
		$this->assertSame('text2', $messages[1]['text']);
	}

	/**
	 * @test
	 * @covers  Hint::set
	 */
	public function test_embedding_values_with_sprintf()
	{
		$expected_outcome = 'You are 2 dorky';
		Hint::set(Hint::ACCESS, 'You are %d %s', array(2, 'dorky'));
		$messages = Hint::get_once();
		$this->assertSame($expected_outcome, $messages[0]['text']);
	}

	/**
	 * @test
	 * @covers  Hint::set
	 */
	public function test_embedding_values_with_strtr()
	{
		$expected_outcome = 'You are 2 dorky';
		Hint::set(Hint::ACCESS, 'You are :amount :state',
			array(':amount' => 2, ':state' => 'dorky'));
		$messages = Hint::get_once();
		$this->assertSame($expected_outcome, $messages[0]['text']);
	}

	/**
	 * @test
	 * @covers  Hint::get
	 */
	public function test_getting_null_when_no_messages_are_set()
	{
		$this->assertNULL(Hint::get());
		Hint::set(Hint::ERROR, '%s', array('lol'), 'custom data');
		Hint::delete(Hint::ERROR);
		$this->assertNULL(Hint::get());
		Hint::set(Hint::ERROR, '%s', array('lol'), 'custom data');
		$this->assertNULL(Hint::get(Hint::ACCESS));
	}

	/**
	 * @covers  Hint::get_once
	 */
	public function testGettingMultipleTypes()
	{
		Hint::set(Hint::ERROR, '%s', array('lol'), 'custom data');
		Hint::set(Hint::ERROR, '%s', array('lol'), 'custom data');
		Hint::set(Hint::ALERT, '%s', array('lol'), 'custom data');
		Hint::set(Hint::ALERT, '%s', array('lol'), 'custom data');
		Hint::set(Hint::WARNING, '%s', array('lol'), 'custom data');
		$this->assertSame(4, count(Hint::get_once(array(Hint::ERROR, Hint::ALERT))));
		$this->assertSame(1, count(Hint::get_once(Hint::WARNING)));
		$this->assertNULL(Hint::get_once());
	}

	/**
	 * @covers  Hint::get_once
	 */
	public function testGettingEverythingButACertainType()
	{
		Hint::set(Hint::ERROR, '%s', array('lol'), 'custom data');
		Hint::set(Hint::ERROR, '%s', array('lol'), 'custom data');
		Hint::set(Hint::ALERT, '%s', array('lol'), 'custom data');
		Hint::set(Hint::ALERT, '%s', array('lol'), 'custom data');
		Hint::set(Hint::WARNING, '%s', array('lol'), 'custom data');

		$error_messages = Hint::get_once(array(1 => array(Hint::ALERT, Hint::WARNING)));
		$this->assertSame(2, count($error_messages));
		$this->assertSame(Hint::ERROR, $error_messages[0]['type']);
		$this->assertSame(Hint::ERROR, $error_messages[1]['type']);

		$alert_messages = Hint::get_once(array(1 => array(Hint::WARNING)));
		$this->assertSame(2, count($alert_messages));
		$this->assertSame(Hint::ALERT, $alert_messages[0]['type']);
		$this->assertSame(Hint::ALERT, $alert_messages[1]['type']);
		$this->assertNULL(Hint::get_once(array(1 => array(Hint::WARNING))));

		$warning_messages = Hint::get_once(array(1 => array(Hint::ERROR)));
		$this->assertSame(1, count($warning_messages));
		$this->assertSame(Hint::WARNING, $warning_messages[0]['type']);
	}

	/**
	 * @test
	 * @covers  Hint::get
	 */
	public function test_overriding_the_default_return_value()
	{
		$my_default = 'OMFG';
		$this->assertSame($my_default, Hint::get(NULL, $my_default));
		$this->assertSame($my_default, Hint::get(Hint::SUCCESS, $my_default));

		Hint::set(Hint::ERROR, '%s', array('lol'), 'custom data');
		$this->assertSame($my_default, Hint::get(Hint::NOTICE, $my_default));
		$this->assertSame($my_default, Hint::get(array(Hint::NOTICE, Hint::ACCESS), $my_default));

		Hint::delete(Hint::ERROR);
		$this->assertSame($my_default, Hint::get(Hint::ERROR, $my_default));
		$this->assertSame($my_default, Hint::get(NULL, $my_default));
		$this->assertSame($my_default, Hint::get(array(Hint::NOTICE, Hint::ERROR), $my_default));
	}

	/**
	 * @test
	 * @covers  Hint::get_once
	 */
	public function test_overriding_the_default_return_value_get_once()
	{
		$my_default = 'OMFG';
		$this->assertSame($my_default, Hint::get_once(NULL, $my_default));
		$this->assertSame($my_default, Hint::get_once(Hint::SUCCESS, $my_default));

		Hint::set(Hint::ERROR, '%s', array('lol'), 'custom data');
		$this->assertSame($my_default, Hint::get_once(Hint::NOTICE, $my_default));
		$this->assertSame($my_default, Hint::get_once(array(Hint::NOTICE, Hint::ACCESS), $my_default));

		Hint::delete(Hint::ERROR);
		$this->assertSame($my_default, Hint::get_once(Hint::ERROR, $my_default));
		$this->assertSame($my_default, Hint::get_once(NULL, $my_default));
		$this->assertSame($my_default, Hint::get_once(array(Hint::NOTICE, Hint::ERROR), $my_default));
	}

	/**
	 * @test
	 * @covers  Hint::delete
	 */
	public function test_delete_function()
	{
		Hint::set(Hint::ERROR, 'LOL');
		Hint::delete(Hint::ERROR);
		$this->assertNULL(Hint::get(Hint::ERROR));
		$this->assertNULL(Hint::get());
	}

	/**
	 * @test
	 * @covers  Hint::delete
	 */
	public function test_deleting_messages_of_a_certain_type()
	{
		Hint::set(Hint::ACCESS, 'LOL');
		Hint::set(Hint::ACCESS, 'LOL');
		Hint::set(Hint::ERROR, 'LOL');
		Hint::set(Hint::ERROR, 'LOL');
		Hint::set(Hint::SUCCESS, 'LOL');
		Hint::set(Hint::SUCCESS, 'LOL');
		Hint::delete(Hint::ACCESS);
		$this->assertSame(2, count(Hint::get(Hint::ERROR)));
		$this->assertSame(4, count(Hint::get()));
	}

	/**
	 * @test
	 * @covers  Hint::delete
	 */
	public function test_deleting_multiple_messages_of_a_certain_type()
	{
		Hint::set(Hint::ACCESS, 'LOL');
		Hint::set(Hint::ACCESS, 'LOL');
		Hint::set(Hint::ERROR, 'LOL');
		Hint::set(Hint::ERROR, 'LOL');
		Hint::set(Hint::SUCCESS, 'LOL');
		Hint::set(Hint::SUCCESS, 'LOL');
		Hint::set(Hint::ALERT, 'LOL');
		Hint::set(Hint::ALERT, 'LOL');
		Hint::delete(array(Hint::ACCESS, Hint::ERROR));
		$this->assertSame(2, count(Hint::get(Hint::SUCCESS)));
		$this->assertSame(4, count(Hint::get()));
	}

	/**
	 * @test
	 * @covers  Hint::render
	 */
	public function test_the_render_function()
	{
		Hint::set(Hint::ACCESS, 'LOL');
		$output1 = Hint::render();
		$output2 = View::factory('hint/default')
			->set('messages', array(
				array(
					'type' => 'access',
					'text' => 'LOL',
				),
			))
			->render();
		$this->assertSame($output1, $output2);
		$this->assertNULL(Hint::get());
	}

	/**
	 * @test
	 * @covers  Hint::render
	 */
	public function test_rendering_only_one_message_type()
	{
		Hint::set(Hint::ACCESS, 'LOL');
		Hint::set(Hint::ERROR, 'LOL');
		$output1 = Hint::render(Hint::ACCESS);
		$output2 = View::factory('hint/default')
			->set('messages', array(
				array(
					'type' => 'access',
					'text' => 'LOL',
				),
			))
			->render();
		$this->assertSame($output1, $output2);
		$this->assertSame(1, count(Hint::get()));
	}

	/**
	 * @test
	 * @covers  Hint::render
	 */
	public function test_rendering_multiple_message_type()
	{
		Hint::set(Hint::ACCESS, 'LOL');
		Hint::set(Hint::ERROR, 'LOL');
		Hint::set(Hint::SUCCESS, 'LOL');
		$output1 = Hint::render(array(Hint::ACCESS, Hint::ERROR));
		$output2 = View::factory('hint/default')
			->set('messages', array(
				array(
					'type' => 'access',
					'text' => 'LOL',
				),
				array(
					'type' => 'error',
					'text' => 'LOL',
				),
			))
			->render();
		$this->assertSame($output1, $output2);
		$this->assertSame(1, count(Hint::get()));
	}

	/**
	 * @test
	 * @covers  Hint::render
	 */
	public function test_rendering_without_deleting()
	{
		Hint::set(Hint::ACCESS, 'LOL');
		Hint::set(Hint::ERROR, 'LOL');
		Hint::set(Hint::SUCCESS, 'LOL');
		Hint::render(array(Hint::ACCESS, Hint::ERROR), FALSE);
		$this->assertSame(3, count(Hint::get()));
	}

	/**
	 * @test
	 * @covers  Hint::render
	 */
	public function test_render_should_return_empty_string_when_no_messages_are_set()
	{
		$this->assertSame('', Hint::render());
	}

} // End HintTest