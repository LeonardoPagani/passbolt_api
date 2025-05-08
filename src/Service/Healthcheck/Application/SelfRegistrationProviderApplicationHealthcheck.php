<?php
declare(strict_types=1);

/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         4.7.0
 */

namespace App\Service\Healthcheck\Application;

use App\Service\Healthcheck\HealthcheckCliInterface;
use App\Service\Healthcheck\HealthcheckServiceCollector;
use App\Service\Healthcheck\HealthcheckServiceInterface;
use Passbolt\SelfRegistration\Service\Healthcheck\SelfRegistrationHealthcheckService;

class SelfRegistrationProviderApplicationHealthcheck implements HealthcheckServiceInterface, HealthcheckCliInterface
{
    /**
     * Status of this health check if it is passed or failed.
     *
     * @var bool
     */
    private bool $status = false;

    /**
     * @var \Passbolt\SelfRegistration\Service\Healthcheck\SelfRegistrationHealthcheckService
     */
    private SelfRegistrationHealthcheckService $selfRegistrationHealthcheckService;

    /**
     * @var string|null
     */
    private ?string $selfRegistrationProvider = null;

    /**
     * @param \Passbolt\SelfRegistration\Service\Healthcheck\SelfRegistrationHealthcheckService $selfRegistrationHealthcheckService Self registration health check service.
     */
    public function __construct(SelfRegistrationHealthcheckService $selfRegistrationHealthcheckService)
    {
        $this->selfRegistrationHealthcheckService = $selfRegistrationHealthcheckService;
    }

    /**
     * @inheritDoc
     */
    public function check(): HealthcheckServiceInterface
    {
        $selfRegistrationChecks = $this->selfRegistrationHealthcheckService->getHealthcheck();
        $this->status = is_null($selfRegistrationChecks['selfRegistrationProvider']);
        $this->selfRegistrationProvider = $selfRegistrationChecks['selfRegistrationProvider'];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function domain(): string
    {
        return HealthcheckServiceCollector::DOMAIN_APPLICATION;
    }

    /**
     * @inheritDoc
     */
    public function isPassed(): bool
    {
        return $this->status;
    }

    /**
     * @inheritDoc
     */
    public function level(): string
    {
        return HealthcheckServiceCollector::LEVEL_NOTICE;
    }

    /**
     * @inheritDoc
     */
    public function getSuccessMessage(): string
    {
        return __('Registration is closed, only administrators can add users.');
    }

    /**
     * @inheritDoc
     */
    public function getFailureMessage(): string
    {
        return __('The self registration provider is: {0}.', $this->selfRegistrationProvider);
    }

    /**
     * @inheritDoc
     */
    public function getHelpMessage(): array|string|null
    {
        return null;
    }

    /**
     * CLI Option for this check.
     *
     * @return string
     */
    public function cliOption(): string
    {
        return HealthcheckServiceCollector::DOMAIN_APPLICATION;
    }

    /**
     * @inheritDoc
     */
    public function getLegacyArrayKey(): string
    {
        return 'registrationClosed.selfRegistrationProvider';
    }

    /**
     * @return string|null
     */
    public function getProvider(): ?string
    {
        return $this->selfRegistrationProvider;
    }
}
