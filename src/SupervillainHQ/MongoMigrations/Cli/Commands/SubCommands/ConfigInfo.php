<?php


namespace SupervillainHQ\MongoMigrations\Cli\Commands\SubCommands {


	use SupervillainHQ\MongoMigrations\Cli\CliCommand;
	use SupervillainHQ\MongoMigrations\Config\Config;

	class ConfigInfo implements CliCommand {

		function execute(): int {
			$config = Config::instance();
			$migrationsInfo = $config->migrations;
			echo "Paths:\n";
			echo "  config path: {$config->realPath()}\n";
			echo "  vendor path: {$config->vendorPath()}\n";
			echo "  migrations path: {$migrationsInfo->path}\n";
			echo "Database:\n";
			echo "  database: {$config->database}\n";
			return 0;
		}

		function help() {
			// TODO: Implement help() method.
		}
	}
}
