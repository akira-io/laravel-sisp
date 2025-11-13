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
            self::referToCardIssuer => __('Refer to card issuer'),
            self::invalidMerchant => __('Invalid merchant'),
            self::cardRetained => __('Card retained'),
            self::transactionRefused => __('Transaction refused'),
            self::issuerError => __('Issuer or banking system error'),
            self::invalidTransaction => __('Invalid transaction'),
            self::invalidAmount => __('Invalid amount'),
            self::invalidCard => __('Invalid card'),
            self::formatError => __('Format error'),
            self::cardExpired => __('Card expired'),
            self::fraudSuspected => __('Fraud suspected'),
            self::restrictedCard => __('Restricted card'),
            self::pinTriesExceeded => __('PIN tries exceeded'),
            self::cardLost => __('Card lost'),
            self::cardStolen => __('Card stolen'),
            self::insufficientFunds => __('Insufficient funds'),
            self::incorrectPin => __('Incorrect PIN'),
            self::transactionNotAllowed => __('Transaction not allowed'),
            self::transactionNotAllowedTerminal => __('Transaction not allowed at terminal'),
            self::amountExceedsLimit => __('Amount exceeds limit'),
            self::cardRestrictedByCountry => __('Card restricted by country'),
            self::transactionCountExceeded => __('Transaction count exceeded'),
            self::cardBlocked => __('Card blocked'),
            self::processingError => __('Processing error'),
            self::cardNotActivated => __('Card not activated'),
            self::expirationDateError => __('Expiration date error'),
            self::encryptionError => __('Encryption error'),
            self::authenticationError => __('Authentication error'),
            self::securityVerificationFailure => __('Security verification failure'),
            self::issuerUnavailable => __('Issuer unavailable'),
            self::financialInstitutionNotFound => __('Financial institution not found'),
            self::transactionDuplication => __('Transaction duplication'),
            self::systemError => __('System error'),
            self::communicationTimeout => __('Communication timeout'),
            self::invalidFingerprint => __('Invalid fingerprint'),
            self::genericError => __('Generic error'),
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
            'card' => __('Card Issue'),
            'funds' => __('Insufficient Funds'),
            'security' => __('Security Issue'),
            'validation' => __('Invalid Details'),
            'system' => __('System Error'),
            'issuer' => __('Issuer Issue'),
            'unknown' => __('Unknown Error'),
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
            'contact-issuer' => __('Contact your card issuer'),
            'contact-issuer-activate' => __('Contact your card issuer to activate your card'),
            'use-different-card' => __('Use a different card'),
            'reduce-amount' => __('Reduce the transaction amount'),
            'contact-issuer-security' => __('Contact your card issuer for security verification'),
            'retry' => __('Please try again'),
            'check-payment-details' => __('Check your payment details and try again'),
            'contact-support' => __('Contact customer support'),
        };
    }
}
