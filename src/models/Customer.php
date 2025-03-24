<?php
namespace MyPaymentGateway\Models;

class Customer {
    private $firstName;
    private $lastName;
    private $email;
    private $phone;
    private $metadata;
    
    /**
     * Create a new customer model
     * 
     * @param string $firstName Customer first name
     * @param string $lastName Customer last name
     * @param string $email Customer email
     * @param string $phone Customer phone number
     * @param array $metadata Additional customer metadata
     */
    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        array $metadata = []
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
        $this->metadata = $metadata;
    }
    
    /**
     * Get customer first name
     * 
     * @return string First name
     */
    public function getFirstName(): string {
        return $this->firstName;
    }
    
    /**
     * Get customer last name
     * 
     * @return string Last name
     */
    public function getLastName(): string {
        return $this->lastName;
    }
    
    /**
     * Get customer full name
     * 
     * @return string Full name
     */
    public function getFullName(): string {
        return $this->firstName . ' ' . $this->lastName;
    }
    
    /**
     * Get customer email
     * 
     * @return string Email
     */
    public function getEmail(): string {
        return $this->email;
    }
    
    /**
     * Get customer phone
     * 
     * @return string Phone number
     */
    public function getPhone(): string {
        return $this->phone;
    }
    
    /**
     * Get customer metadata
     * 
     * @return array Metadata
     */
    public function getMetadata(): array {
        return $this->metadata;
    }
    
    /**
     * Set customer metadata
     * 
     * @param array $metadata Metadata
     * @return self
     */
    public function setMetadata(array $metadata): self {
        $this->metadata = $metadata;
        return $this;
    }
    
    /**
     * Add a metadata item
     * 
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return self
     */
    public function addMetadata(string $key, $value): self {
        $this->metadata[$key] = $value;
        return $this;
    }
}