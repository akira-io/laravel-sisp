<?php

declare(strict_types=1);

namespace Akira\Sisp\Enums;

enum ErrorMessageType: string
{
    case referToCardIssuer = '1';
    case invalidMerchant = '3';
    case cardRetained = '4';
    case transactionRefused = '5';
    case issuerError = '6';
    case invalidTransaction = '12';
    case invalidAmount = '13';
    case invalidCard = '14';
    case formatError = '30';
    case cardExpired = '33';
    case fraudSuspected = '34';
    case restrictedCard = '36';
    case pinTriesExceeded = '38';
    case cardLost = '41';
    case cardStolen = '43';
    case insufficientFunds = '51';
    case incorrectPin = '55';
    case transactionNotAllowed = '57';
    case transactionNotAllowedTerminal = '58';
    case amountExceedsLimit = '61';
    case cardRestrictedByCountry = '62';
    case transactionCountExceeded = '65';
    case cardBlocked = '76';
    case processingError = '77';
    case cardNotActivated = '78';
    case expirationDateError = '80';
    case encryptionError = '81';
    case authenticationError = '82';
    case securityVerificationFailure = '83';
    case issuerUnavailable = '91';
    case financialInstitutionNotFound = '92';
    case transactionDuplication = '94';
    case systemError = '96';
    case communicationTimeout = '97';
    case invalidFingerprint = '98';
    case genericError = '99';

    public function label(): string
    {
        return match ($this) {
            self::referToCardIssuer => __('sisp::messages.errors.labels.referToCardIssuer'),
            self::invalidMerchant => __('sisp::messages.errors.labels.invalidMerchant'),
            self::cardRetained => __('sisp::messages.errors.labels.cardRetained'),
            self::transactionRefused => __('sisp::messages.errors.labels.transactionRefused'),
            self::issuerError => __('sisp::messages.errors.labels.issuerError'),
            self::invalidTransaction => __('sisp::messages.errors.labels.invalidTransaction'),
            self::invalidAmount => __('sisp::messages.errors.labels.invalidAmount'),
            self::invalidCard => __('sisp::messages.errors.labels.invalidCard'),
            self::formatError => __('sisp::messages.errors.labels.formatError'),
            self::cardExpired => __('sisp::messages.errors.labels.cardExpired'),
            self::fraudSuspected => __('sisp::messages.errors.labels.fraudSuspected'),
            self::restrictedCard => __('sisp::messages.errors.labels.restrictedCard'),
            self::pinTriesExceeded => __('sisp::messages.errors.labels.pinTriesExceeded'),
            self::cardLost => __('sisp::messages.errors.labels.cardLost'),
            self::cardStolen => __('sisp::messages.errors.labels.cardStolen'),
            self::insufficientFunds => __('sisp::messages.errors.labels.insufficientFunds'),
            self::incorrectPin => __('sisp::messages.errors.labels.incorrectPin'),
            self::transactionNotAllowed => __('sisp::messages.errors.labels.transactionNotAllowed'),
            self::transactionNotAllowedTerminal => __('sisp::messages.errors.labels.transactionNotAllowedTerminal'),
            self::amountExceedsLimit => __('sisp::messages.errors.labels.amountExceedsLimit'),
            self::cardRestrictedByCountry => __('sisp::messages.errors.labels.cardRestrictedByCountry'),
            self::transactionCountExceeded => __('sisp::messages.errors.labels.transactionCountExceeded'),
            self::cardBlocked => __('sisp::messages.errors.labels.cardBlocked'),
            self::processingError => __('sisp::messages.errors.labels.processingError'),
            self::cardNotActivated => __('sisp::messages.errors.labels.cardNotActivated'),
            self::expirationDateError => __('sisp::messages.errors.labels.expirationDateError'),
            self::encryptionError => __('sisp::messages.errors.labels.encryptionError'),
            self::authenticationError => __('sisp::messages.errors.labels.authenticationError'),
            self::securityVerificationFailure => __('sisp::messages.errors.labels.securityVerificationFailure'),
            self::issuerUnavailable => __('sisp::messages.errors.labels.issuerUnavailable'),
            self::financialInstitutionNotFound => __('sisp::messages.errors.labels.financialInstitutionNotFound'),
            self::transactionDuplication => __('sisp::messages.errors.labels.transactionDuplication'),
            self::systemError => __('sisp::messages.errors.labels.systemError'),
            self::communicationTimeout => __('sisp::messages.errors.labels.communicationTimeout'),
            self::invalidFingerprint => __('sisp::messages.errors.labels.invalidFingerprint'),
            self::genericError => __('sisp::messages.errors.labels.genericError'),
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::cardExpired,
            self::cardLost,
            self::cardStolen,
            self::cardBlocked,
            self::cardNotActivated,
            self::restrictedCard,
            self::cardRetained => 'card',

            self::insufficientFunds,
            self::amountExceedsLimit,
            self::transactionCountExceeded => 'funds',

            self::fraudSuspected,
            self::securityVerificationFailure,
            self::authenticationError,
            self::incorrectPin,
            self::pinTriesExceeded => 'security',

            self::invalidCard,
            self::invalidAmount,
            self::invalidTransaction,
            self::invalidMerchant,
            self::invalidFingerprint,
            self::formatError,
            self::expirationDateError,
            self::cardRestrictedByCountry,
            self::transactionNotAllowed,
            self::transactionNotAllowedTerminal => 'validation',

            self::encryptionError,
            self::processingError,
            self::systemError,
            self::communicationTimeout,
            self::issuerError,
            self::issuerUnavailable,
            self::financialInstitutionNotFound => 'system',

            self::referToCardIssuer,
            self::transactionRefused,
            self::transactionDuplication => 'issuer',

            self::genericError => 'unknown',
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category()) {
            'card' => __('sisp::messages.errors.categories.card'),
            'funds' => __('sisp::messages.errors.categories.funds'),
            'security' => __('sisp::messages.errors.categories.security'),
            'validation' => __('sisp::messages.errors.categories.validation'),
            'system' => __('sisp::messages.errors.categories.system'),
            'issuer' => __('sisp::messages.errors.categories.issuer'),
            'unknown' => __('sisp::messages.errors.categories.unknown'),
        };
    }

    public function action(): string
    {
        return match ($this) {
            self::cardExpired,
            self::cardLost,
            self::cardStolen,
            self::cardBlocked => 'contact-issuer',

            self::cardNotActivated,
            self::restrictedCard,
            self::cardRetained => 'contact-issuer-activate',

            self::insufficientFunds => 'use-different-card',

            self::amountExceedsLimit,
            self::transactionCountExceeded => 'reduce-amount',

            self::fraudSuspected,
            self::securityVerificationFailure => 'contact-issuer-security',

            self::authenticationError,
            self::incorrectPin,
            self::pinTriesExceeded => 'retry',

            self::invalidCard,
            self::invalidAmount,
            self::formatError => 'check-payment-details',

            self::invalidTransaction,
            self::transactionDuplication => 'contact-support',

            self::invalidMerchant,
            self::invalidFingerprint,
            self::encryptionError => 'contact-support',

            self::expirationDateError,
            self::cardRestrictedByCountry,
            self::transactionNotAllowed,
            self::transactionNotAllowedTerminal => 'use-different-card',

            self::processingError,
            self::systemError,
            self::communicationTimeout,
            self::issuerError,
            self::issuerUnavailable => 'retry',

            self::financialInstitutionNotFound,
            self::referToCardIssuer,
            self::transactionRefused => 'contact-issuer',

            self::genericError => 'contact-support',
        };
    }

    public function actionLabel(): string
    {
        return match ($this->action()) {
            'contact-issuer' => __('sisp::messages.errors.actions.contact-issuer'),
            'contact-issuer-activate' => __('sisp::messages.errors.actions.contact-issuer-activate'),
            'use-different-card' => __('sisp::messages.errors.actions.use-different-card'),
            'reduce-amount' => __('sisp::messages.errors.actions.reduce-amount'),
            'contact-issuer-security' => __('sisp::messages.errors.actions.contact-issuer-security'),
            'retry' => __('sisp::messages.errors.actions.retry'),
            'check-payment-details' => __('sisp::messages.errors.actions.check-payment-details'),
            'contact-support' => __('sisp::messages.errors.actions.contact-support'),
        };
    }
}
