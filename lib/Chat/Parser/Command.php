<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
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

namespace OCA\Spreed\Chat\Parser;

use OCP\Comments\IComment;
use OCP\IUser;
use OCP\IUserSession;

class Command {

	/** @var ?IUser */
	protected $recipient;

	public function __construct(IUserSession $userSession) {
		$this->recipient = $userSession->getUser();
	}

	public function setUser(IUser $user): void {
		$this->recipient = $user;
	}

	/**
	 * @param IComment $comment
	 * @return array
	 * @throws \OutOfBoundsException
	 */
	public function parseMessage(IComment $comment): array {
		$data = json_decode($comment->getMessage(), true);
		if (!\is_array($data)) {
			throw new \OutOfBoundsException('Invalid message');
		}

		if ($data['visibility'] === \OCA\Spreed\Model\Command::OUTPUT_NONE) {
			throw new \RuntimeException('Message should not print');
		}

		if ($this->recipient instanceof IUser &&
			$data['visibility'] !== \OCA\Spreed\Model\Command::OUTPUT_ALL &&
			$data['user'] !== $this->recipient->getUID()) {
			throw new \RuntimeException('Message should not print');
		}

		$comment->setMessage($data['output']);

		return [$data['output'], []];
	}
}
