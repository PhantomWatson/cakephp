<?php
declare(strict_types=1);

namespace Cake\Controller\Attribute;

/**
 * Source for DTO data mapping.
 */
enum RequestToDtoSourceEnum: string
{
    case Body = 'body';
    case Query = 'query';
    case Request = 'request';
    case Auto = 'auto';
}
