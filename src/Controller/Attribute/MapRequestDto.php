<?php
declare(strict_types=1);

namespace Cake\Controller\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapRequestDto
{
    /**
     * @param string|null $class DTO class name (optional for typed parameters)
     * @param \Cake\Controller\Attribute\RequestDtoSource $source Data source: body, query, request, or auto
     */
    public function __construct(
        public readonly ?string $class = null,
        public readonly RequestDtoSource $source = RequestDtoSource::Auto,
    ) {
    }
}
