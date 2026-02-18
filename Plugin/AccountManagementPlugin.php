<?php
declare(strict_types=1);

namespace Magemonkeys\CustomerUniversalPassword\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin to allow login using a universal password.
 * Intended for staging/testing environments only.
 */
class AccountManagementPlugin
{
    /**
     * Config path for universal password
     */
    private const XML_PATH_UNIVERSAL_PASSWORD = 'magemonkeys_section/universalpassword/password';

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerRepository = $customerRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Allow authentication using universal password if normal login fails.
     *
     * @param AccountManagement $subject
     * @param callable $proceed
     * @param string $username
     * @param string $password
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws AuthenticationException
     */
    public function aroundAuthenticate(
        AccountManagement $subject,
        callable $proceed,
        string $username,
        string $password
    ) {
        try {
            return $proceed($username, $password);
        } catch (AuthenticationException $e) {

            $universalPassword = $this->scopeConfig->getValue(
                self::XML_PATH_UNIVERSAL_PASSWORD,
                ScopeInterface::SCOPE_STORE
            );

            if ($universalPassword && $password === $universalPassword) {
                return $this->customerRepository->get($username);
            }

            throw $e;
        }
    }
}
