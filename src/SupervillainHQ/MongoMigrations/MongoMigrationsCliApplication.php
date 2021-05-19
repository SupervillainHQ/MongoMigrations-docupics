<?php
/**
 * Created by PhpStorm.
 * User: anderskrarup
 * Date: 2019-01-22
 * Time: 14:09
 */

namespace SupervillainHQ\MongoMigrations {


	use Commando\Command;
	use Phalcon\Di;
	use Phalcon\Di\FactoryDefault\Cli as CliDI;
	use Phalcon\DiInterface;
	use SupervillainHQ\MongoMigrations\Config\Config;

	class MongoMigrationsCliApplication {

		private static $migrationDir;
		private static $mongoDatabase;

		static function migrationDir(){
			return realpath(self::$migrationDir);
		}

		static function database():string{
			return trim(self::$mongoDatabase);
		}

		static function run(string $configPath, Command $command){
			$di = new CliDI();
			Config::load($configPath);

			self::$migrationDir = Config::instance()->migrations->path;
			self::$mongoDatabase = Config::instance()->database;

			$dependencies = [
				'SupervillainHQ\MongoMigrations\Core\Dependencies\Mongo'
			];
			self::loadDependencies($dependencies);

			if(!is_writable(self::$migrationDir) || !is_dir(self::$migrationDir)){
				$path = self::$migrationDir;
				throw new \Exception("Invalid migration directory. Unable to verify path '{$path}'");
			}

			if (stripos(getenv("APP_ENV"), "prod")){
				set_exception_handler(function (\Throwable $e) use ($di) {
					$debug = $di->get("debug");
					$debug->log(\Phalcon\Logger::CRITICAL, "Message: " . $e->getMessage());
					$debug->log(\Phalcon\Logger::CRITICAL, "File: " . $e->getFile());
					$debug->log(\Phalcon\Logger::CRITICAL, "Line: " . $e->getLine());
					$debug->log(\Phalcon\Logger::CRITICAL, "Trace: " . $e->getTraceAsString());
					echo "Command failed. See log for details\n";
				});
			}

			self::evaluate($command);
		}

		private static function evaluate(Command $command){
			$cmd = ucfirst($command['command']);
			$arguments = $command->getArgumentValues();

			$commandClass = "SupervillainHQ\\MongoMigrations\\Cli\\Commands\\{$cmd}";
			if (!class_exists($commandClass)) {
				$commandClass = "SupervillainHQ\\MongoMigrations\\Cli\\Commands\\Help";
			}
			$reflector = new \ReflectionClass($commandClass);
			if ($reflector->implementsInterface('SupervillainHQ\MongoMigrations\Cli\CliCommand')) {
				if ($reflector->hasMethod('__construct')) {
					$method = $reflector->getMethod('__construct');
					$params = $method->getParameters();
					if (count($params)) {
						$cliCommand = $reflector->newInstanceArgs($arguments);
					}
					else{
						$cliCommand = $reflector->newInstance();
					}
				}
				else{
					$cliCommand = $reflector->newInstance();
				}
				if(isset($cliCommand)){
					$exitCode = $cliCommand->execute();
					exit($exitCode);
				}
			}
			throw new \Exception("No such command");
		}

		private static function loadDependencies(array $dependencies, DiInterface $dependencyInjector = null) {
			if(is_null($dependencyInjector)){
				$dependencyInjector = Di::getDefault();
			}

			foreach ($dependencies as $dependency) {
				$reflection = new \ReflectionClass($dependency);

				if ($reflection->implementsInterface("SupervillainHQ\\MongoMigrations\\Core\\Dependency")) {
					$service = $reflection->newInstance();

					if ($service->shared()) {
						$dependencyInjector->setShared($service->getName(), $service->definition());
					}
					else {
						$dependencyInjector->set($service->getName(), $service->definition());
					}
				}

			}
		}

	}
}
