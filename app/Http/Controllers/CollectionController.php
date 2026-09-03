<?php

namespace App\Http\Controllers;

use App\DataObjects\CollectionFilters;
use App\Enums\CollectionType;
use App\Http\Requests\CollectionFilterRequest;
use App\Http\Resources\ProductCardResource;
use App\Services\CollectionService;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function __construct(private readonly CollectionService $collections)
    {
    }

    public function newArrivals(CollectionFilterRequest $request): Response
    {
        return $this->render(CollectionType::NewArrivals, $request);
    }

    public function bestSellers(CollectionFilterRequest $request): Response
    {
        return $this->render(CollectionType::BestSellers, $request);
    }

    public function accessories(CollectionFilterRequest $request): Response
    {
        return $this->render(CollectionType::Accessories, $request);
    }

    public function deals(CollectionFilterRequest $request): Response
    {
        return $this->render(CollectionType::Deals, $request);
    }

    private function render(CollectionType $type, CollectionFilterRequest $request): Response
    {
        $filters = CollectionFilters::fromRequest($request);
        $products = $this->collections->paginate($type, $filters);

        return Inertia::render('Collection', [
            'collection' => [
                'key' => $type->value,
                'title' => $type->title(),
                'description' => $type->description(),
            ],
            'products' => ProductCardResource::collection($products),
            'filters' => $filters->toQueryArray(),
            'filterOptions' => $this->collections->filterOptions($type),
        ]);
    }
}
