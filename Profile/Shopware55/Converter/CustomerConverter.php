<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroupDiscount\CustomerGroupDiscountDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroupTranslation\CustomerGroupTranslationDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Shopware\Core\System\Country\Aggregate\CountryStateTranslation\CountryStateTranslationDefinition;
use Shopware\Core\System\Country\Aggregate\CountryTranslation\CountryTranslationDefinition;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use SwagMigrationNext\Migration\Converter\AbstractConverter;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationNext\Profile\Shopware55\Premapping\PaymentMethodReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class CustomerConverter extends AbstractConverter
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var ConverterHelperService
     */
    private $helper;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $mainLocale;

    /**
     * @var string
     */
    private $oldCustomerId;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var string[]
     */
    private $requiredDataFieldKeys = [
        'firstname',
        'lastname',
        'email',
    ];

    /**
     * @var string[]
     */
    private $requiredAddressDataFieldKeys = [
        'firstname',
        'lastname',
        'zipcode',
        'city',
        'street',
    ];

    public function __construct(
        MappingServiceInterface $mappingService,
        ConverterHelperService $converterHelperService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
        $this->loggingService = $loggingService;
    }

    public function getSupportedEntityName(): string
    {
        return CustomerDefinition::getEntityName();
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $oldData = $data;
        $this->runId = $migrationContext->getRunUuid();

        $fields = $this->helper->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);
        if (!isset($data['group']['id'])) {
            $fields[] = 'group id';
        }

        if (!empty($fields)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                'Empty necessary data fields',
                sprintf('Customer-Entity could not converted cause of empty necessary field(s): %s.', implode(', ', $fields)),
                [
                    'id' => $data['id'],
                    'entity' => 'Customer',
                    'fields' => $fields,
                ],
                \count($fields)
            );

            return new ConvertStruct(null, $oldData);
        }

        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->context = $context;
        $this->mainLocale = $data['_locale'];
        unset($data['_locale']);

        $converted = [];
        if (isset($data['accountmode']) && $data['accountmode'] === '1') {
            $this->oldCustomerId = $data['id'];
        } else {
            $this->oldCustomerId = $data['email'];
        }

        $customerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            CustomerDefinition::getEntityName(),
            $this->oldCustomerId,
            $this->context
        );

        $converted['id'] = $customerUuid;
        unset($data['id']);

        $converted['salesChannelId'] = Defaults::SALES_CHANNEL;
        if (isset($data['subshopID'])) {
            $salesChannelId = $this->mappingService->getUuid(
                $this->connectionId,
                SalesChannelDefinition::getEntityName(),
                $data['subshopID'],
                $this->context
            );

            if ($salesChannelId !== null) {
                $converted['salesChannelId'] = $salesChannelId;
                unset($data['subshopID']);
            }
        }

        $this->helper->convertValue($converted, 'password', $data, 'password');
        $this->helper->convertValue($converted, 'active', $data, 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'email', $data, 'email');
        $this->helper->convertValue($converted, 'guest', $data, 'accountmode', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'confirmationKey', $data, 'confirmationkey');
        $this->helper->convertValue($converted, 'newsletter', $data, 'newsletter', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'validation', $data, 'validation');
        $this->helper->convertValue($converted, 'affiliate', $data, 'affiliate', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'referer', $data, 'referer');
        $this->helper->convertValue($converted, 'internalComment', $data, 'internalcomment');
        $this->helper->convertValue($converted, 'failedLogins', $data, 'failedlogins', $this->helper::TYPE_INTEGER); // Nötig?
        $this->helper->convertValue($converted, 'title', $data, 'title');
        $this->helper->convertValue($converted, 'salutation', $data, 'salutation');
        $this->helper->convertValue($converted, 'firstName', $data, 'firstname');
        $this->helper->convertValue($converted, 'lastName', $data, 'lastname');
        $this->helper->convertValue($converted, 'customerNumber', $data, 'customernumber');
        $this->helper->convertValue($converted, 'birthday', $data, 'birthday', $this->helper::TYPE_DATETIME);
        $this->helper->convertValue($converted, 'lockedUntil', $data, 'lockeduntil', $this->helper::TYPE_DATETIME);
        $this->helper->convertValue($converted, 'encoder', $data, 'encoder');

        if (!isset($converted['customerNumber']) || $converted['customerNumber'] === '') {
            $converted['customerNumber'] = 'number-' . $this->oldCustomerId;
        }

        $converted['group'] = $this->getCustomerGroup($data['group']);
        unset($data['group'], $data['customergroup']);

        if (isset($data['defaultpayment']['id'])) {
            $defaultPaymentMethodUuid = $this->getDefaultPaymentMethod($data['defaultpayment']);

            if ($defaultPaymentMethodUuid === null) {
                return new ConvertStruct(null, $oldData);
            }

            $converted['defaultPaymentMethodId'] = $defaultPaymentMethodUuid;
        }
        unset($data['defaultpayment'], $data['paymentpreset']);

        if (!isset($converted['defaultPaymentMethodId'])) {
            $converted['defaultPaymentMethodId'] = $this->mappingService->getUuid(
                $this->connectionId,
                PaymentMethodReader::getMappingName(),
                'default_payment_method',
                $this->context
            );

            if (!isset($converted['defaultPaymentMethodId'])) {
                $this->loggingService->addWarning(
                    $this->runId,
                    Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                    'Empty necessary data fields',
                    'Customer-Entity could not converted cause of empty necessary field(s): defaultpayment.',
                    [
                        'id' => $this->oldCustomerId,
                        'entity' => 'Customer',
                        'fields' => ['defaultpayment'],
                    ],
                    1
                );

                return new ConvertStruct(null, $oldData);
            }
        }

        if (isset($data['addresses'])) {
            $this->getAddresses($data, $converted, $customerUuid);
        }

        unset(
            $data['addresses'],

            // Legacy data which don't need a mapping or there is no equivalent field
            $data['doubleOptinRegister'],
            $data['doubleOptinEmailSentDate'],
            $data['doubleOptinConfirmDate'],
            $data['sessionID'],
            $data['pricegroupID'],
            $data['login_token'],
            $data['changed'],
            $data['group']['mode'],
            $data['paymentID'],
            $data['firstlogin'],
            $data['lastlogin'],

            // TODO check how to handle these
            $data['shop'], // TODO use for sales channel information?
            $data['language'], // TODO use for sales channel information?
            $data['customerlanguage'], // TODO use for sales channel information?
            $data['attributes']
        );

        if (empty($data)) {
            $data = null;
        }

        if (!isset($converted['defaultBillingAddressId'], $converted['defaultShippingAddressId'])) {
            $this->mappingService->deleteMapping($converted['id'], $this->connectionId, $this->context);

            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::NO_ADDRESS_DATA,
                'No address data',
                'Customer-Entity could not converted cause of empty address data.',
                ['id' => $this->oldCustomerId]
            );

            return new ConvertStruct(null, $oldData);
        }

        return new ConvertStruct($converted, $data);
    }

    private function getCustomerGroup(array $originalData): array
    {
        $group['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CustomerGroupDefinition::getEntityName(),
            $originalData['id'],
            $this->context
        );
        $translation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CustomerGroupTranslationDefinition::getEntityName(),
            $originalData['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $translation['customerGroupId'] = $group['id'];
        $this->helper->convertValue($translation, 'name', $originalData, 'description');

        $this->helper->convertValue($group, 'displayGross', $originalData, 'tax', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($group, 'inputGross', $originalData, 'taxinput', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($group, 'hasGlobalDiscount', $originalData, 'mode', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($group, 'percentageGlobalDiscount', $originalData, 'discount', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($group, 'minimumOrderAmount', $originalData, 'minimumorder', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($group, 'minimumOrderAmountSurcharge', $originalData, 'minimumordersurcharge', $this->helper::TYPE_FLOAT);

        if (isset($originalData['discounts'])) {
            $group['discounts'] = $this->getCustomerGroupDiscount($originalData['discounts'], $group['id']);
        }
        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $group['translations'][$languageData['uuid']] = $translation;

        return $group;
    }

    private function getCustomerGroupDiscount(array $oldDiscounts, $groupId): array
    {
        $discounts = [];
        foreach ($oldDiscounts as $old) {
            $oldDiscount = $old['discount'];
            $discount['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                CustomerGroupDiscountDefinition::getEntityName(),
                (string) $oldDiscount['id'],
                $this->context
            );

            $discount['customerGroupId'] = $groupId;
            $this->helper->convertValue($discount, 'percentageDiscount', $oldDiscount, 'basketdiscount', $this->helper::TYPE_FLOAT);
            $this->helper->convertValue($discount, 'minimumCartAmount', $oldDiscount, 'basketdiscountstart', $this->helper::TYPE_FLOAT);

            $discounts[] = $discount;
        }

        return $discounts;
    }

    private function getDefaultPaymentMethod(array $originalData): ?string
    {
        $paymentMethodUuid = $this->mappingService->getUuid(
            $this->connectionId,
            PaymentMethodReader::getMappingName(),
            $originalData['id'],
            $this->context
        );

        if ($paymentMethodUuid === null) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::UNKNOWN_PAYMENT_METHOD,
                'Cannot find payment method',
                'Customer-Entity could not converted cause of unknown payment method',
                [
                    'id' => $this->oldCustomerId,
                    'entity' => CustomerDefinition::getEntityName(),
                    'paymentMethod' => $originalData['id'],
                ]
            );
        }

        return $paymentMethodUuid;
    }

    /**
     * @param array[] $originalData
     */
    private function getAddresses(array &$originalData, array &$converted, string $customerUuid): void
    {
        $addresses = [];
        foreach ($originalData['addresses'] as $address) {
            $newAddress = [];

            $fields = $this->helper->checkForEmptyRequiredDataFields($address, $this->requiredAddressDataFieldKeys);
            if (!empty($fields)) {
                $this->loggingService->addInfo(
                    $this->runId,
                    Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                    'Empty necessary data fields for address',
                    sprintf('Address-Entity could not converted cause of empty necessary field(s): %s.', implode(', ', $fields)),
                    [
                        'id' => $this->oldCustomerId,
                        'uuid' => $customerUuid,
                        'entity' => 'Address',
                        'fields' => $fields,
                    ],
                    \count($fields)
                );

                continue;
            }

            $newAddress['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                CustomerAddressDefinition::getEntityName(),
                $address['id'],
                $this->context
            );

            if (isset($originalData['default_billing_address_id']) && $address['id'] === $originalData['default_billing_address_id']) {
                $converted['defaultBillingAddressId'] = $newAddress['id'];
                unset($originalData['default_billing_address_id']);
            }

            if (isset($originalData['default_shipping_address_id']) && $address['id'] === $originalData['default_shipping_address_id']) {
                $converted['defaultShippingAddressId'] = $newAddress['id'];
                unset($originalData['default_shipping_address_id']);
            }

            $newAddress['customerId'] = $customerUuid;
            $newAddress['country'] = $this->getCountry($address['country']);
            if (isset($address['state'])) {
                $newAddress['countryState'] = $this->getCountryState($address['state'], $newAddress['country']);
            }

            $this->helper->convertValue($newAddress, 'salutation', $address, 'salutation');
            $this->helper->convertValue($newAddress, 'firstName', $address, 'firstname');
            $this->helper->convertValue($newAddress, 'lastName', $address, 'lastname');
            $this->helper->convertValue($newAddress, 'zipcode', $address, 'zipcode');
            $this->helper->convertValue($newAddress, 'city', $address, 'city');
            $this->helper->convertValue($newAddress, 'company', $address, 'company');
            $this->helper->convertValue($newAddress, 'street', $address, 'street');
            $this->helper->convertValue($newAddress, 'department', $address, 'department');
            $this->helper->convertValue($newAddress, 'title', $address, 'title');
            $this->helper->convertValue($newAddress, 'vatId', $address, 'ustid');
            $this->helper->convertValue($newAddress, 'phoneNumber', $address, 'phone');
            $this->helper->convertValue($newAddress, 'additionalAddressLine1', $address, 'additional_address_line1');
            $this->helper->convertValue($newAddress, 'additionalAddressLine2', $address, 'additional_address_line2');

            $addresses[] = $newAddress;
        }

        if (empty($addresses)) {
            return;
        }

        $converted['addresses'] = $addresses;

        // No valid default billing and shipping address was converted, so use the first valid one as default
        $this->checkUnsetDefaultShippingAndDefaultBillingAddress($originalData, $converted, $customerUuid, $addresses);

        // No valid default shipping address was converted, but the default billing address is valid
        $this->checkUnsetDefaultShippingAddress($originalData, $converted, $customerUuid);

        // No valid default billing address was converted, but the default shipping address is valid
        $this->checkUnsetDefaultBillingAddress($originalData, $converted, $customerUuid);
    }

    private function getCountry(array $oldCountryData): array
    {
        $country = [];
        $countryUuid = null;
        if (isset($oldCountryData['countryiso'], $oldCountryData['iso3'])) {
            $countryUuid = $this->mappingService->getCountryUuid(
                $oldCountryData['id'],
                $oldCountryData['countryiso'],
                $oldCountryData['iso3'],
                $this->connectionId,
                $this->context
            );
        }

        if ($countryUuid !== null) {
            $country['id'] = $countryUuid;
        } else {
            $country['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                CountryDefinition::getEntityName(),
                $oldCountryData['id'],
                $this->context
            );
        }

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CountryTranslationDefinition::getEntityName(),
            $oldCountryData['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $translation['countryId'] = $country['id'];
        $this->helper->convertValue($translation, 'name', $oldCountryData, 'countryname');

        $this->helper->convertValue($country, 'iso', $oldCountryData, 'countryiso');
        $this->helper->convertValue($country, 'position', $oldCountryData, 'position', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($country, 'taxFree', $oldCountryData, 'taxfree', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($country, 'taxfreeForVatId', $oldCountryData, 'taxfree_ustid', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($country, 'taxfreeVatidChecked', $oldCountryData, 'taxfree_ustid_checked', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($country, 'active', $oldCountryData, 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($country, 'iso3', $oldCountryData, 'iso3');
        $this->helper->convertValue($country, 'displayStateInRegistration', $oldCountryData, 'display_state_in_registration', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($country, 'forceStateInRegistration', $oldCountryData, 'force_state_in_registration', $this->helper::TYPE_BOOLEAN);

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $country['translations'][$languageData['uuid']] = $translation;

        return $country;
    }

    private function getCountryState(array $oldStateData, array $newCountryData): array
    {
        $state = [];
        $state['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CountryStateDefinition::getEntityName(),
            $oldStateData['id'],
            $this->context
        );
        $state['countryId'] = $newCountryData['id'];

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CountryStateTranslationDefinition::getEntityName(),
            $oldStateData['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $translation['countryStateId'] = $state['id'];
        $this->helper->convertValue($translation, 'name', $oldStateData, 'name');
        $this->helper->convertValue($state, 'shortCode', $oldStateData, 'shortcode');
        $this->helper->convertValue($state, 'position', $oldStateData, 'position', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($state, 'active', $oldStateData, 'active', $this->helper::TYPE_BOOLEAN);

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $state['translations'][$languageData['uuid']] = $translation;

        return $state;
    }

    private function checkUnsetDefaultShippingAndDefaultBillingAddress(array &$originalData, array &$converted, string $customerUuid, $addresses): void
    {
        if (!isset($converted['defaultBillingAddressId']) && !isset($converted['defaultShippingAddressId'])) {
            $converted['defaultBillingAddressId'] = $addresses[0]['id'];
            $converted['defaultShippingAddressId'] = $addresses[0]['id'];
            unset($originalData['default_billing_address_id'], $originalData['default_shipping_address_id']);

            $this->loggingService->addInfo(
                $this->runId,
                Shopware55LogTypes::NO_DEFAULT_BILLING_AND_SHIPPING_ADDRESS,
                'No default billing and shipping address',
                'Default billing and shipping address of customer is empty and will set with the first address.',
                [
                    'id' => $this->oldCustomerId,
                    'uuid' => $customerUuid,
                ]
            );
        }
    }

    private function checkUnsetDefaultShippingAddress(array &$originalData, array &$converted, string $customerUuid): void
    {
        if (!isset($converted['defaultShippingAddressId']) && isset($converted['defaultBillingAddressId'])) {
            $converted['defaultShippingAddressId'] = $converted['defaultBillingAddressId'];
            unset($originalData['default_shipping_address_id']);

            $this->loggingService->addInfo(
                $this->runId,
                Shopware55LogTypes::NO_DEFAULT_SHIPPING_ADDRESS,
                'No default shipping address',
                'Default shipping address of customer is empty and will set with the default billing address.',
                [
                    'id' => $this->oldCustomerId,
                    'uuid' => $customerUuid,
                ]
            );
        }
    }

    private function checkUnsetDefaultBillingAddress(array &$originalData, array &$converted, string $customerUuid): void
    {
        if (!isset($converted['defaultBillingAddressId']) && isset($converted['defaultShippingAddressId'])) {
            $converted['defaultBillingAddressId'] = $converted['defaultShippingAddressId'];
            unset($originalData['default_billing_address_id']);

            $this->loggingService->addInfo(
                $this->runId,
                Shopware55LogTypes::NO_DEFAULT_BILLING_ADDRESS,
                'No default billing address',
                'Default billing address of customer is empty and will set with the default shipping address.',
                [
                    'id' => $this->oldCustomerId,
                    'uuid' => $customerUuid,
                ]
            );
        }
    }
}
