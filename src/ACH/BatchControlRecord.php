<?php
/**
 * Created by PhpStorm.
 * User: mcasiro
 * Date: 2018-06-06
 * Time: 16:17
 */

namespace RW\ACH;


class BatchControlRecord extends FileComponent
{
    /* FIXED VALUES */
    private const FIXED_RECORD_TYPE_CODE = '8';

    /* FIXED VALUE FIELD NAMES */
    private const MESSAGE_AUTHENTICATION_CODE      = 'MESSAGE_AUTHENTICATION_CODE';
    private const RESERVED                         = 'RESERVED';

    /* CALCULATED/RETRIEVED VALUE FIELD NAMES */
    private const ENTRY_AND_ADDENDA_COUNT          = 'ENTRY_AND_ADDENDA_COUNT';
    private const ENTRY_HASH                       = 'ENTRY_HASH';
    private const TOTAL_DEBIT_ENTRY_DOLLAR_AMOUNT  = 'TOTAL_DEBIT_ENTRY_DOLLAR_AMOUNT';
    private const TOTAL_CREDIT_ENTRY_DOLLAR_AMOUNT = 'TOTAL_CREDIT_ENTRY_DOLLAR_AMOUNT';
    private const COMPANY_ID                       = 'COMPANY_ID';
    private const ORIGINATING_DFI_ID               = 'ORIGINATING_DFI_ID';
    private const BATCH_NUMBER                     = 'BATCH_NUMBER';

    /**
     * BatchControl constructor.
     *
     * @param BatchHeaderRecord   $batchHeaderRecord
     * @param EntryDetailRecord[] $entryDetailRecords
     * @throws ValidationException
     */
    public function __construct($batchHeaderRecord, $entryDetailRecords)
    {
        $serviceClassCode = $batchHeaderRecord->fieldSpecifications[BatchHeaderRecord::SERVICE_CLASS_CODE];
        $entryAndAddendaCount         = $this->getEntryAndAddendaCount($entryDetailRecords);
        $entryHash                    = $this->getEntryHash($entryDetailRecords);
        $totalDebitEntryDollarAmount  = $this->getTotalEntryDollarAmount(
            $entryDetailRecords,
            EntryDetailRecord::DEBIT_TRANSACTION_CODES
        );
        $totalCreditEntryDollarAmount = $this->getTotalEntryDollarAmount(
            $entryDetailRecords,
            EntryDetailRecord::CREDIT_TRANSACTION_CODES
        );
        $companyIdentificationField   = $batchHeaderRecord->fieldSpecifications[BatchHeaderRecord::COMPANY_ID];
        $originatingDfiIdField        = $batchHeaderRecord->fieldSpecifications[BatchHeaderRecord::ORIGINATING_DFI_ID];
        $batchNumberField             = $batchHeaderRecord->fieldSpecifications[BatchHeaderRecord::BATCH_NUMBER];

        parent::__construct([
            self::SERVICE_CLASS_CODE               => $serviceClassCode[self::CONTENT],
            self::ENTRY_AND_ADDENDA_COUNT          => $entryAndAddendaCount,
            self::ENTRY_HASH                       => $entryHash,
            self::TOTAL_DEBIT_ENTRY_DOLLAR_AMOUNT  => $totalDebitEntryDollarAmount,
            self::TOTAL_CREDIT_ENTRY_DOLLAR_AMOUNT => $totalCreditEntryDollarAmount,
            self::COMPANY_ID                       => $companyIdentificationField[self::CONTENT],
            self::MESSAGE_AUTHENTICATION_CODE      => null,
            self::RESERVED                         => null,
            self::ORIGINATING_DFI_ID               => $originatingDfiIdField[self::CONTENT],
            self::BATCH_NUMBER                     => $batchNumberField[self::CONTENT],
        ]);
    }

    /**
     * Generate the field specifications for each field in the file component.
     * Format is an array of arrays as follows:
     *  $this->fieldSpecifications = [
     *      FIELD_NAME => [
     *          self::FIELD_INCLUSION => Mandatory, Required, or Optional (reserved for future use)
     *          self::FORMAT          => Description of the expected format (informational)
     *          self::VALIDATOR       => array: [
     *              Validation type (self::VALIDATOR_REGEX or self::VALIDATOR_DATE_TIME)
     *              Validation string (regular expression or date-time format)
     *          ]
     *          self::LENGTH          => Required if 'PADDING' is provided: Fixed width of the field
     *          self::POSITION_START  => Starting position within the component (reserved for future use)
     *          self::POSITION_END    => Ending position within the component (reserved for future use)
     *          self::PADDING         => Optional: self::ALPHANUMERIC_PADDING or self::NUMERIC_PADDING
     *          self::CONTENT         => The content to be output for this field
     *      ],
     *      ...
     *  ]
     */
    protected function getDefaultFieldSpecifications()
    {
        return [
            self::RECORD_TYPE_CODE                 => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_MANDATORY,
                self::FORMAT          => 'N',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^\d{1}$/'],
                self::LENGTH          => 1,
                self::POSITION_START  => 1,
                self::POSITION_END    => 1,
                self::CONTENT         => self::FIXED_RECORD_TYPE_CODE,
            ],
            self::SERVICE_CLASS_CODE               => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_MANDATORY,
                self::FORMAT          => 'NNN',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^(200|220|225)$/'],
                self::LENGTH          => 3,
                self::POSITION_START  => 2,
                self::POSITION_END    => 4,
                self::CONTENT         => null,
            ],
            self::ENTRY_AND_ADDENDA_COUNT          => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_MANDATORY,
                self::FORMAT          => 'NNNNNN',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^\d{1,6}$/'],
                self::LENGTH          => 6,
                self::POSITION_START  => 5,
                self::POSITION_END    => 10,
                self::PADDING         => self::NUMERIC_PADDING,
                self::CONTENT         => null,
            ],
            self::ENTRY_HASH                       => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_MANDATORY,
                self::FORMAT          => 'NNNNNNNNNNN',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^\d{1,10}$/'],
                self::LENGTH          => 10,
                self::POSITION_START  => 11,
                self::POSITION_END    => 20,
                self::PADDING         => self::NUMERIC_PADDING,
                self::CONTENT         => null,
            ],
            self::TOTAL_DEBIT_ENTRY_DOLLAR_AMOUNT  => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_MANDATORY,
                self::FORMAT          => '$$$$$$$$$cc',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^\d{0,12}$/'],
                self::LENGTH          => 12,
                self::POSITION_START  => 21,
                self::POSITION_END    => 32,
                self::PADDING         => self::NUMERIC_PADDING,
                self::CONTENT         => null,
            ],
            self::TOTAL_CREDIT_ENTRY_DOLLAR_AMOUNT => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_MANDATORY,
                self::FORMAT          => '$$$$$$$$$cc',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^\d{0,12}$/'],
                self::LENGTH          => 12,
                self::POSITION_START  => 33,
                self::POSITION_END    => 44,
                self::PADDING         => self::NUMERIC_PADDING,
                self::CONTENT         => null,
            ],
            self::COMPANY_ID                       => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_REQUIRED,
                self::FORMAT          => 'NNNNNNNNNN',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^\d{10}$/'],
                self::LENGTH          => 10,
                self::POSITION_START  => 45,
                self::POSITION_END    => 54,
                self::CONTENT         => null,
            ],
            self::MESSAGE_AUTHENTICATION_CODE      => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_OPTIONAL,
                self::FORMAT          => 'bbbbbbbbbbbbbbbbbbb',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^ {0,19}$/'],
                self::LENGTH          => 19,
                self::POSITION_START  => 55,
                self::POSITION_END    => 73,
                self::PADDING         => self::ALPHANUMERIC_PADDING,
                self::CONTENT         => null,
            ],
            self::RESERVED                         => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_OPTIONAL,
                self::FORMAT          => 'bbbbbb',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^ {0,6}$/'],
                self::LENGTH          => 6,
                self::POSITION_START  => 74,
                self::POSITION_END    => 79,
                self::PADDING         => self::ALPHANUMERIC_PADDING,
                self::CONTENT         => null,
            ],
            self::ORIGINATING_DFI_ID               => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_MANDATORY,
                self::FORMAT          => 'NNNNNNNN',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^\d{8}$/'],
                self::LENGTH          => 8,
                self::POSITION_START  => 80,
                self::POSITION_END    => 87,
                self::CONTENT         => null,
            ],
            self::BATCH_NUMBER                     => [
                self::FIELD_INCLUSION => self::FIELD_INCLUSION_MANDATORY,
                self::FORMAT          => 'NNNNNNN',
                self::VALIDATOR       => [self::VALIDATOR_REGEX, '/^\d{1,7}$/'],
                self::LENGTH          => 7,
                self::POSITION_START  => 88,
                self::POSITION_END    => 94,
                self::PADDING         => self::NUMERIC_PADDING,
                self::CONTENT         => null,
            ],
        ];
    }

    /**
     * @param EntryDetailRecord[] $entryDetailRecords
     * @return int
     */
    private function getEntryAndAddendaCount($entryDetailRecords)
    {
        // This does does not account for addenda records yet!
        return count($entryDetailRecords);
    }

    /**
     * @param EntryDetailRecord[] $entryDetailRecords
     * @return int
     */
    private function getEntryHash($entryDetailRecords)
    {
        $transitSum = 0;
        /** @var EntryDetailRecord $entryDetailRecord */
        foreach ($entryDetailRecords as $entryDetailRecord) {
            $transitSum += $entryDetailRecord->getTransitAbaNumber();
        }

        // Use the ten low-order (right most) digits from the sum
        return $transitSum % 10000000000;
    }

    /**
     * @param EntryDetailRecord[] $entryDetailRecords
     * @param array               $validTransactionCodes
     * @return int
     */
    private function getTotalEntryDollarAmount($entryDetailRecords, $validTransactionCodes)
    {
        $dollarSum = 0;
        /** @var EntryDetailRecord $entryDetailRecord */
        foreach ($entryDetailRecords as $entryDetailRecord) {
            if (in_array($entryDetailRecord->getTransactionCode(), $validTransactionCodes)) {
                $dollarSum += $entryDetailRecord->getAmount();
            }
        }

        return $dollarSum;
    }
}