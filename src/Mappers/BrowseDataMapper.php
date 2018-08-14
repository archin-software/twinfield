<?php

namespace PhpTwinfield\Mappers;

use PhpTwinfield\Exception;
use PhpTwinfield\Office;
use PhpTwinfield\Response\Response;
use PhpTwinfield\Util;
use PhpTwinfield\BrowseData;
use PhpTwinfield\BrowseDataCell;
use PhpTwinfield\BrowseDataHeader;
use PhpTwinfield\BrowseDataRow;

class BrowseDataMapper extends BaseMapper
{
    /**
     * Maps a Response object to a BrowseData entity.
     *
     * @param Response $response
     * @return BrowseData
     * @throws Exception
     */
    public static function map(Response $response)
    {
        // Generate new BrowseData object
        $browseData = new BrowseData();

        // Get the browse data element
        $document = $response->getResponseDocument();
        $browseDataElement = $document->documentElement;

        $browseData->setFirst((int)$browseDataElement->getAttribute('first'));
        $browseData->setLast((int)$browseDataElement->getAttribute('last'));
        $browseData->setTotal((int)$browseDataElement->getAttribute('total'));

        // Get headers
        $headersElement = $browseDataElement->getElementsByTagName('th')[0];
        foreach ($headersElement->getElementsByTagName('td') as $headerElement) {
            $browseDataHeader = new BrowseDataHeader();
            $browseDataHeader->setLabel($headerElement->getAttribute('label'));
            $browseDataHeader->setHideForUser(Util::parseBoolean($headerElement->getAttribute('hideforuser')));
            $browseDataHeader->setType($headerElement->getAttribute('type'));
            $browseDataHeader->setCode($headerElement->textContent);

            $browseData->addHeader($browseDataHeader);
        }

        // Get rows
        foreach ($browseDataElement->getElementsByTagName('tr') as $rowElement) {
            $browseDataRow = new BrowseDataRow();

            // Get row key
            $keyElement = $rowElement->getElementsByTagName('key')[0];

            $office = new Office();
            $office->setCode(self::getField($keyElement, 'office'));
            $browseDataRow->setOffice($office);
            $browseDataRow->setCode(self::getField($keyElement, 'code'));
            $browseDataRow->setNumber(self::getField($keyElement, 'number'));
            $browseDataRow->setLine(self::getField($keyElement, 'line'));

            $browseData->addRow($browseDataRow);

            // Get cells
            foreach ($rowElement->getElementsByTagName('td') as $cellElement) {
                $browseDataCell = new BrowseDataCell();
                $browseDataCell->setField($cellElement->getAttribute('field'));
                $browseDataCell->setHideForUser(Util::parseBoolean($cellElement->getAttribute('hideforuser')));
                $browseDataCell->setType($cellElement->getAttribute('type'));
                $browseDataCell->setValue(
                    self::parseBrowseDataValue($browseDataCell->getType(), $cellElement->textContent)
                );

                $browseDataRow->addCell($browseDataCell);
            }
        }

        return $browseData;
    }

    /**
     * Parse value of browse data to the given type.
     *
     * @param string $type
     * @param string $value
     * @return mixed
     * @throws Exception
     */
    private static function parseBrowseDataValue(string $type, string $value)
    {
        switch ($type) {
            case 'Long':
                return (int)$value;
            case 'Decimal':
            case 'Value':
                return floatval($value);
            case 'Date':
                return Util::parseDate($value);
            case 'Datetime':
                return Util::parseDateTime($value);
            default:
                return $value;

        }
    }
}
