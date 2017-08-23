<?php

namespace Pim\Bundle\EnrichBundle\Elasticsearch;

use Akeneo\Bundle\ElasticsearchBundle\Client;
use Akeneo\Component\StorageUtils\Cursor\CursorInterface;
use Akeneo\Component\StorageUtils\Repository\CursorableRepositoryInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductModelInterface;

/**
 * Bounded cursor to iterate over items where a start and a limit are defined.
 * Internally, this is implemented with the from/size pagination.
 * {@see https://www.elastic.co/guide/en/elasticsearch/reference/5.x/search-request-from-size.html}
 *
 * This cursor is dedicated to the search in the datagrid where we need to have 2 types of objects:
 * products and product models.
 *
 * @author    Julien Janvier <jjanvier@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FromSizeCursor implements CursorInterface
{
    /** @var Client */
    protected $esClient;

    /** @var CursorableRepositoryInterface */
    protected $productRepository;

    /** @var CursorableRepositoryInterface */
    protected $productModelRepository;

    /** @var array */
    protected $esQuery;

    /** @var string */
    protected $indexType;

    /** @var array */
    protected $items;

    /** @var int */
    protected $pageSize;

    /** @var int */
    protected $count;

    /** @var int */
    protected $initialFrom;

    /** @var int */
    protected $from;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $to;

    /** @var int */
    protected $fetchedItemsCount;

    /**
     * @param Client                        $esClient
     * @param CursorableRepositoryInterface $productRepository
     * @param CursorableRepositoryInterface $productModelRepository
     * @param array                         $esQuery
     * @param string                        $indexType
     * @param int                           $pageSize
     * @param int                           $limit
     * @param int                           $from
     */
    public function __construct(
        Client $esClient,
        CursorableRepositoryInterface $productRepository,
        CursorableRepositoryInterface $productModelRepository,
        array $esQuery,
        $indexType,
        $pageSize,
        $limit,
        $from = 0
    ) {
        $this->esClient = $esClient;
        $this->productRepository = $productRepository;
        $this->productModelRepository = $productModelRepository;
        $this->esQuery = $esQuery;
        $this->indexType = $indexType;
        $this->pageSize = $pageSize;
        $this->limit = $limit;
        $this->from = $from;
        $this->initialFrom = $from;
        $this->to = $this->from + $this->limit;
    }


    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if (null === $this->items) {
            $this->rewind();
        }

        return current($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        if (null === $this->items) {
            $this->rewind();
        }

        return key($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        if (null === $this->items) {
            $this->rewind();
        }

        return !empty($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if (null === $this->items) {
            $this->rewind();
        }

        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if (false === next($this->items)) {
            $this->from += count($this->items);
            $this->items = $this->getNextItems($this->esQuery);
            reset($this->items);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->from = $this->initialFrom;
        $this->to = $this->from + $this->limit;
        $this->items = $this->getNextItems($this->esQuery);
        reset($this->items);
    }

    /**
     * Get the next items (hydrated from doctrine repository).
     *
     * @param array $esQuery
     *
     * @return array
     */
    protected function getNextItems(array $esQuery)
    {
        $identifiers = $this->getNextIdentifiers($esQuery);
        if (empty($identifiers)) {
            return [];
        }

        $productIdentifiers = [];
        $productModelIdentifiers = [];

        foreach ($identifiers as $identifier => $type) {
            if (in_array($type, ['PimCatalogProduct', 'PimCatalogVariantProduct'])) {
                $productIdentifiers[] = $identifier;
            } else {
                $productModelIdentifiers[] = $identifier;
            }
        }

        $hydratedProducts = $this->productRepository->getItemsFromIdentifiers($productIdentifiers);
        $hydratedProductModels = $this->productModelRepository->getItemsFromIdentifiers($productModelIdentifiers);
        $hydratedItems = array_merge($hydratedProducts, $hydratedProductModels);

        $orderedItems = [];

        foreach ($identifiers as $identifier => $type) {
            foreach ($hydratedItems as $hydratedItem) {
                if ($hydratedItem instanceof ProductInterface && $identifier === $hydratedItem->getIdentifier()) {
                    $orderedItems[] = $hydratedItem;
                    break;
                } elseif ($hydratedItem instanceof ProductModelInterface && $identifier === $hydratedItem->getCode()) {
                    $orderedItems[] = $hydratedItem;
                    break;
                }
            }
        }

        return $orderedItems;
    }

    /**
     * Returns an array containing the identifier as keys and the product type as values.
     * The idea is keep the sort of the identifier and to be able to know if it's a product or a product model.
     *
     * For instance
     *      [
     *          'tshirt-red-s'  => 'variant_product',
     *          'tshirt-red'    => 'product_model',
     *          'watch'         => 'product',
     *      ]
     *
     * @return array
     *
     * {@see https://www.elastic.co/guide/en/elasticsearch/reference/5.x/search-request-from-size.html}
     */
    protected function getNextIdentifiers(array $esQuery)
    {
        $size = ($this->to - $this->from) > $this->pageSize ? $this->pageSize : ($this->to - $this->from);
        $esQuery['size'] = $size;

        if (0 === $esQuery['size']) {
            return [];
        }

        $sort = ['_uid' => 'asc'];

        if (isset($esQuery['sort'])) {
            $sort = array_merge($esQuery['sort'], $sort);
        }

        $esQuery['sort'] = $sort;
        $esQuery['from'] = $this->from;

        $response = $this->esClient->search($this->indexType, $esQuery);
        $this->count = $response['hits']['total'];

        $identifiers = [];
        foreach ($response['hits']['hits'] as $hit) {
            $identifiers[$hit['_source']['identifier']] = $hit['_source']['product_type'];
        }

        return $identifiers;
    }
}
