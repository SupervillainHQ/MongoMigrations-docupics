<?php
/**
 * Created by PhpStorm.
 * User: anderskrarup
 * Date: 2019-01-22
 * Time: 14:16
 */

namespace SupervillainHQ\MongoMigrations\Cli{
	interface CliCommand {
		function execute():int;
		function help();
	}
}
