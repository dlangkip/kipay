<?php
namespace MyPaymentGateway\PaymentChannels;

/**
 * Kenyan Payment Channels
 * 
 * Provides constants and methods for working with Kenyan payment channels
 */
class KenyanChannels {
    // Mobile Money Payment Methods
    const MPESA = 'MPESA';
    const AIRTEL_MONEY = 'AIRTEL';
    const EQUITEL = 'EQUITEL';
    const TKASH = 'TKASH';
    
    // Bank Payment Methods
    const EQUITY_BANK = 'EQUITY';
    const KCB_BANK = 'KCB';
    const COOPERATIVE_BANK = 'COOP';
    const ABSA_BANK = 'ABSA';
    const STANDARD_CHARTERED = 'SCB';
    const NCBA_BANK = 'NCBA';
    const FAMILY_BANK = 'FAMILY';
    const DTB_BANK = 'DTB';
    const I_AND_M_BANK = 'IMB';
    const STANBIC_BANK = 'STANBIC';
    
    // Card Payment Methods
    const VISA = 'VISA';
    const MASTERCARD = 'MASTERCARD';
    const AMERICAN_EXPRESS = 'AMEX';
    
    /**
     * Get all supported payment channels
     * 
     * @return array List of payment channels
     */
    public static function getAllChannels(): array {
        return [
            'mobile_money' => self::getMobileMoneyChannels(),
            'banks' => self::getBankChannels(),
            'cards' => self::getCardChannels()
        ];
    }
    
    /**
     * Get supported mobile money channels
     * 
     * @return array List of mobile money channels
     */
    public static function getMobileMoneyChannels(): array {
        return [
            self::MPESA => 'M-Pesa',
            self::AIRTEL_MONEY => 'Airtel Money',
            self::EQUITEL => 'Equitel',
            self::TKASH => 'T-Kash'
        ];
    }
    
    /**
     * Get supported bank channels
     * 
     * @return array List of bank channels
     */
    public static function getBankChannels(): array {
        return [
            self::EQUITY_BANK => 'Equity Bank',
            self::KCB_BANK => 'KCB Bank',
            self::COOPERATIVE_BANK => 'Cooperative Bank',
            self::ABSA_BANK => 'ABSA Bank',
            self::STANDARD_CHARTERED => 'Standard Chartered Bank',
            self::NCBA_BANK => 'NCBA Bank',
            self::FAMILY_BANK => 'Family Bank',
            self::DTB_BANK => 'DTB Bank',
            self::I_AND_M_BANK => 'I&M Bank',
            self::STANBIC_BANK => 'Stanbic Bank'
        ];
    }
    
    /**
     * Get supported card channels
     * 
     * @return array List of card channels
     */
    public static function getCardChannels(): array {
        return [
            self::VISA => 'Visa',
            self::MASTERCARD => 'Mastercard',
            self::AMERICAN_EXPRESS => 'American Express'
        ];
    }
    
    /**
     * Get paybill numbers for mobile money channels
     * 
     * @return array Paybill numbers
     */
    public static function getPaybillNumbers(): array {
        return [
            self::MPESA => '174379', // Example Pesapal Paybill
            self::AIRTEL_MONEY => '174379',
            self::EQUITEL => '174379',
            self::TKASH => '174379'
        ];
    }
    
    /**
     * Get till numbers for mobile money channels
     * 
     * @return array Till numbers
     */
    public static function getTillNumbers(): array {
        return [
            self::MPESA => '123456', // Example till number
            self::AIRTEL_MONEY => '123456',
            self::EQUITEL => '123456',
            self::TKASH => '123456'
        ];
    }
    
    /**
     * Get payment channel from method
     * 
     * @param string $method Payment method
     * @return string Channel type (mobile_money, bank, card)
     */
    public static function getChannelType(string $method): string {
        if (array_key_exists($method, self::getMobileMoneyChannels())) {
            return 'mobile_money';
        } else if (array_key_exists($method, self::getBankChannels())) {
            return 'bank';
        } else if (array_key_exists($method, self::getCardChannels())) {
            return 'card';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Get payment method display name
     * 
     * @param string $method Payment method code
     * @return string Display name
     */
    public static function getMethodName(string $method): string {
        $allChannels = array_merge(
            self::getMobileMoneyChannels(),
            self::getBankChannels(),
            self::getCardChannels()
        );
        
        return $allChannels[$method] ?? $method;
    }
}