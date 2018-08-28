<?php
/**
 * @copyright Copyright (c) 2018- Technologies Coeus Inc.
 * @license   https://github.com/zendframework/zend-mail/blob/master/LICENSE.md New BSD License
 */
namespace Wikisuite\Jmap\Core\Exception;

/**
 * Exception for Jmap methot call returning an error message
 */
class ResponseUnsupportedFilterException extends ResponseErrorException
{
    public function __construct($unsupportedFilters)
    {
        parent::__construct('Unsupported filter(s): '.implode(', ', $unsupportedFilters), 'unsupportedFilter');
    }
}
