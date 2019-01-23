<?php
/**
 * @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\Spreed\Controller;

use OCA\Spreed\Service\CommandService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class CommandController extends OCSController {


	/** @var CommandService */
	protected $commandService;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param CommandService $commandService
	 */
	public function __construct($appName,
								IRequest $request,
								CommandService $commandService) {
		parent::__construct($appName, $request);
		$this->commandService = $commandService;
	}

	/**
	 * @return DataResponse
	 */
	public function index(): DataResponse {
		$commands = $this->commandService->findAll();

		$result = [];
		foreach ($commands as $command) {
			$result[] = [
				'id' => $command->getId(),
				'app' => $command->getApp(),
				'name' => $command->getName(),
				'pattern' => $command->getCommand(),
				'script' => $command->getScript(),
				'response' => $command->getResponse(),
				'enabled' => $command->getEnabled(),
			];
		}

		return new DataResponse($result);
	}

	/**
	 * @param string $cmd
	 * @param string $name
	 * @param string $script
	 * @param int $response
	 * @param int $enabled
	 * @return DataResponse
	 */
	public function create(string $cmd, string $name, string $script, int $response, int $enabled): DataResponse {
		try {
			$command = $this->commandService->create('', $name, $cmd, $script, $response, $enabled);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse([
			'id' => $command->getId(),
			'app' => $command->getApp(),
			'name' => $command->getName(),
			'pattern' => $command->getCommand(),
			'script' => $command->getScript(),
			'response' => $command->getResponse(),
			'enabled' => $command->getEnabled(),
		]);
	}

	/**
	 * @param int $id
	 * @return DataResponse
	 */
	public function show(int $id): DataResponse {
		try {
			$command = $this->commandService->findById($id);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse([
			'id' => $command->getId(),
			'app' => $command->getApp(),
			'name' => $command->getName(),
			'pattern' => $command->getCommand(),
			'script' => $command->getScript(),
			'response' => $command->getResponse(),
			'enabled' => $command->getEnabled(),
		]);
	}

	/**
	 * @param int $id
	 * @param string $cmd
	 * @param string $name
	 * @param string $script
	 * @param int $response
	 * @param int $enabled
	 * @return DataResponse
	 */
	public function update(int $id, string $cmd, string $name, string $script, int $response, int $enabled): DataResponse {
		try {
			$command = $this->commandService->update($id, $name,  $cmd, $script, $response, $enabled);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse([
			'id' => $command->getId(),
			'app' => $command->getApp(),
			'name' => $command->getName(),
			'pattern' => $command->getCommand(),
			'script' => $command->getScript(),
			'response' => $command->getResponse(),
			'enabled' => $command->getEnabled(),
		]);
	}

	/**
	 * @param int $id
	 * @return DataResponse
	 */
	public function destroy(int $id): DataResponse {
		try {
			$this->commandService->delete($id);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse();
	}

}
