<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @covers Geppetto\DatabaseHandler
 */
final class DatabaseHandlerTest extends TestCase
{
	public function testIsSingleton()//: void
	{
		$db_handler = Geppetto\DatabaseHandler::init();

		$this->assertEquals(
			Geppetto\DatabaseHandler::init(),
			$db_handler
		);
	}

	public function testProhibitCreatingNewClass()//: void
	{
		$this->expectException(Error::class);

		$db_handler = new Geppetto\DatabaseHandler();
	}
}