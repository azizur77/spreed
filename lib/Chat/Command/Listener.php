<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Spreed\Chat\Command;


use OCA\Spreed\Chat\ChatManager;
use OCA\Spreed\Chat\MessageParser;
use OCA\Spreed\Chat\Parser\Command as CommandParser;
use OCA\Spreed\Service\CommandService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Comments\IComment;
use OCP\IUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class Listener {

	/** @var EventDispatcherInterface */
	protected $dispatcher;
	/** @var CommandService */
	protected $commandService;
	/** @var Executor */
	protected $executor;

	public function __construct(EventDispatcherInterface $dispatcher, CommandService $commandService, Executor $executor) {
		$this->dispatcher = $dispatcher;
		$this->commandService = $commandService;
		$this->executor = $executor;
	}

	public function register(): void {
		$this->dispatcher->addListener(ChatManager::class . '::preSendMessage', function(GenericEvent $event) {
			/** @var IComment $message */
			$message = $event->getArgument('comment');

			try {
				[$command, $arguments] = $this->getCommand($message->getMessage());
			} catch (DoesNotExistException $e) {
				return;
			}

			$this->executor->exec($event->getSubject(), $message, $command, $arguments);
		});

		$this->dispatcher->addListener(MessageParser::class . '::parseMessage', function(GenericEvent $event) {
			/** @var IComment $chatMessage */
			$chatMessage = $event->getSubject();

			if ($chatMessage->getVerb() !== 'command') {
				return;
			}

			/** @var CommandParser $parser */
			$parser = \OC::$server->query(CommandParser::class);

			$user = $event->getArgument('user');
			if ($user instanceof IUser) {
				$parser->setUser($event->getArgument('user'));
			}

			try {
				[$message, $parameters] = $parser->parseMessage($chatMessage);

				$event->setArguments([
					'message' => $message,
					'parameters' => $parameters,
				]);
				$event->stopPropagation();
			} catch (\OutOfBoundsException $e) {
				// Unknown message, ignore
			} catch (\RuntimeException $e) {
				$event->stopPropagation();
			}
		});
	}

	/**
	 * @param string $message
	 * @return array [Command, string]
	 * @throws DoesNotExistException
	 */
	protected function getCommand(string $message): array {
		[$app, $cmd, $arguments] = $this->matchesCommand($message);

		if ($app === '') {
			throw new DoesNotExistException('No command found');
		}

		try {
			return [$this->commandService->find($app, $cmd), trim($arguments)];
		} catch (DoesNotExistException $e) {
		}

		try {
			return [$this->commandService->find('',  $app), trim($cmd . ' ' . $arguments)];
		} catch (DoesNotExistException $e) {
		}

		return [$this->commandService->find('',  'help'), trim($message)];
	}

	protected function matchesCommand(string $message): array {
		if (strpos($message, '/') !== 0) {
			return ['', '', ''];
		}

		$cmd = explode(' ', substr($message, 1), 3);
		return [
			$cmd[0],
			$cmd[1] ?? '',
			$cmd[2] ?? '',
		];
	}
}
