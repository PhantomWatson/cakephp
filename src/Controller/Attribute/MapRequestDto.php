<?php
declare(strict_types=1);

namespace Cake\Controller\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapRequestDto
{
    public const SOURCE_BODY = 'body';
    public const SOURCE_QUERY = 'query';
    public const SOURCE_REQUEST = 'request';
    public const SOURCE_AUTO = 'auto';

    /**
     * @param string|null $class DTO class name (optional for typed parameters)
     * @param string $source Data source: body, query, request, or auto
     */
    public function __construct(
        public readonly ?string $class = null,
        public readonly string $source = self::SOURCE_AUTO,
    ) {
    }
}
