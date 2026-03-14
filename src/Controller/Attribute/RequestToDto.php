<?php
declare(strict_types=1);

namespace Cake\Controller\Attribute;

use Attribute;
use Cake\Http\ServerRequest;

#[Attribute(Attribute::TARGET_PARAMETER)]
class RequestToDto
{
    /**
     * @param string|null $class DTO class name (optional for typed parameters)
     * @param \Cake\Controller\Attribute\RequestToDtoSourceEnum $source Data source: body, query, request, or auto
     */
    public function __construct(
        public readonly ?string $class = null,
        public readonly RequestToDtoSourceEnum $source = RequestToDtoSourceEnum::Auto,
    ) {
    }

    /**
     * Extract data from request based on source.
     *
     * @param \Cake\Http\ServerRequest $request The server request
     * @return array<string, mixed>
     */
    public function extractData(ServerRequest $request): array
    {
        return match ($this->source) {
            RequestToDtoSourceEnum::Body => (array)$request->getData(),
            RequestToDtoSourceEnum::Query => $request->getQueryParams(),
            RequestToDtoSourceEnum::Request => array_merge(
                $request->getQueryParams(),
                (array)$request->getData(),
            ),
            RequestToDtoSourceEnum::Auto => $this->extractAutoData($request),
        };
    }

    /**
     * Auto-detect data source based on request method.
     *
     * @param \Cake\Http\ServerRequest $request The server request
     * @return array<string, mixed>
     */
    protected function extractAutoData(ServerRequest $request): array
    {
        if ($request->is(['get', 'head'])) {
            return $request->getQueryParams();
        }

        $data = (array)$request->getData();
        if ($data !== []) {
            return $data;
        }

        return $request->getQueryParams();
    }
}
