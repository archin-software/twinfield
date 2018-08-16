<?php

namespace PhpTwinfield\Enums;

use MyCLabs\Enum\Enum;

/**
 * @method static BrowseColumnOperator ASCENDING()
 * @method static BrowseColumnOperator DESCENDING()
 */
class Order extends Enum
{
    protected const ASCENDING = 'ascending';
    protected const DESCENDING = 'descending';
}