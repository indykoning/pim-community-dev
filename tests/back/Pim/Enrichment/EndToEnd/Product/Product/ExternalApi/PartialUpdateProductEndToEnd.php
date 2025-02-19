<?php

declare(strict_types=1);

namespace AkeneoTest\Pim\Enrichment\EndToEnd\Product\Product\ExternalApi;

use Akeneo\Pim\Enrichment\Component\Product\Message\ProductUpdated;
use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\IntegrationTestsBundle\Messenger\AssertEventCountTrait;
use Psr\Log\Test\TestLogger;
use Symfony\Component\HttpFoundation\Response;

class PartialUpdateProductEndToEnd extends AbstractProductTestCase
{
    use AssertEventCountTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createProduct('product_family', [
            'family' => 'familyA2',
        ]);

        $this->createProduct('product_groups', [
            'groups' => ['groupA'],
        ]);

        $this->createProduct('product_categories', [
            'categories' => ['master'],
        ]);

        $this->createProduct('product_associations', [
            'associations'  => [
                'PACK'         => ['groups'   => [], 'products' => [], 'product_models' => []],
                'SUBSTITUTION' => ['groups'   => [], 'products' => [], 'product_models' => []],
                'UPSELL'       => ['groups'   => [], 'products' => [], 'product_models' => []],
                'X_SELL'       => ['groups'   => ['groupA'], 'products' => ['product_categories'], 'product_models' => []],
            ],
        ]);

        $this->createProduct('localizable', [
            'values'     => [
                'a_localizable_image' => [
                    ['data' => $this->getFileInfoKey($this->getFixturePath('akeneo.jpg')), 'locale' => 'en_US', 'scope' => null],
                    ['data' => $this->getFileInfoKey($this->getFixturePath('akeneo.jpg')), 'locale' => 'fr_FR', 'scope' => null],
                ]
            ]
        ]);

        $this->createProduct('complete', [
            'family'        => 'familyA2',
            'groups'        => ['groupA'],
            'categories'    => ['master'],
            'values'        => [
                'a_metric' => [
                    ['data' => ['amount' => '10.0000', 'unit' => 'KILOWATT'], 'locale' => null, 'scope' => null],
                ],
                'a_date'   => [
                    ['data' => '2016-06-13T00:00:00+02:00', 'locale' => null, 'scope' => null],
                ],
                'a_simple_select' => [
                    ['locale' => null, 'scope' => null, 'data' => 'optionB'],
                ],
            ],
            'associations'  => [
                'PACK'         => ['groups'   => [], 'products' => [], 'product_models' => []],
                'SUBSTITUTION' => ['groups'   => [], 'products' => [], 'product_models' => []],
                'UPSELL'       => ['groups'   => [], 'products' => [], 'product_models' => []],
                'X_SELL'       => ['groups'   => ['groupA'], 'products' => ['product_categories'], 'product_models' => []],
            ],
        ]);
    }

    public function testProductCreationWithIdenticalIdentifiers()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_create_with_identifier"
    }
JSON;
        $expectedProduct = [
            'identifier'    => 'product_create_with_identifier',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],
            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_create_with_identifier'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $client->request('PATCH', 'api/rest/v1/products/product_create_with_identifier', [], [], [], $data);

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_create_with_identifier');
        $this->assertArrayHasKey('location', $response->headers->all());
        $this->assertSame(
            'http://localhost/api/rest/v1/products/product_create_with_identifier',
            $response->headers->get('location')
        );
    }

    public function testProductCreationWithoutIdentifier()
    {
        $client = $this->createAuthenticatedClient();

        $data = '{}';

        $expectedProduct = [
            'identifier'    => 'product_create_without_identifier',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],
            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_create_without_identifier'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $client->request('PATCH', 'api/rest/v1/products/product_create_without_identifier', [], [], [], $data);

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_create_without_identifier');
        $this->assertArrayHasKey('location', $response->headers->all());
        $this->assertSame(
            'http://localhost/api/rest/v1/products/product_create_without_identifier',
            $response->headers->get('location')
        );
    }

    public function testProductCreationWithDifferentIdentifiers()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "bar"
    }
JSON;

        $expectedContent = [
            'code'    => 422,
            'message' => 'The identifier "bar" provided in the request body must match the identifier "foo" provided in the url.',
        ];

        $client->request('PATCH', 'api/rest/v1/products/foo', [], [], [], $data);

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testProductCreationWithInvalidValues()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "new_identifier",
        "values": {
            "a_metric": {
                "locale": null,
                "scope": null,
                "data": null
            }
        }
    }
JSON;

        $expectedContent = [
            'code'    => 422,
            'message' => 'Property "a_metric" expects an array with valid data, one of the values is not an array. Check the expected format on the API documentation.',
            '_links' => [
                'documentation' => ['href' => 'http://api.akeneo.com/api-reference.html#patch_products__code_'],
            ],
        ];

        $client->request('PATCH', 'api/rest/v1/products/new_identifier', [], [], [], $data);

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testProductUpdateWithInvalidValues()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_family",
        "values": {
            "a_metric": {
                "locale": null,
                "scope": null,
                "data": null
            }
        }
    }
JSON;

        $expectedContent = [
            'code'    => 422,
            'message' => 'Property "a_metric" expects an array with valid data, one of the values is not an array. Check the expected format on the API documentation.',
            '_links' => [
                'documentation' => ['href' => 'http://api.akeneo.com/api-reference.html#patch_products__code_'],
            ],
        ];

        $client->request('PATCH', 'api/rest/v1/products/product_family', [], [], [], $data);

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testProductUpdateWithDuplicatedValues()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "complete",
        "values": {
            "a_simple_select": [
                {"locale": null, "scope": null, "data": "optionB"},
                {"locale": null, "scope": null, "data": "optionA"}
            ]
        }
    }
JSON;

        $expectedContent = [
            'code'    => 422,
            'message' => 'You cannot update the same product value on the "a_simple_select" attribute twice, with the same scope and locale. Check the expected format on the API documentation.',
            '_links' => [
                'documentation' => ['href' => 'http://api.akeneo.com/api-reference.html#patch_products__code_'],
            ],
        ];

        $client->request('PATCH', 'api/rest/v1/products/complete', [], [], [], $data);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
    }

    public function testProductPartialUpdateWithIdenticalIdentifiers()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_categories"
    }
JSON;

        $expectedProduct = [
            'identifier'    => 'product_categories',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],
            'categories'    => ['master'],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_categories'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_categories');
        $this->assertArrayHasKey('location', $response->headers->all());
        $this->assertSame(
            'http://localhost/api/rest/v1/products/product_categories',
            $response->headers->get('location')
        );
    }

    public function testProductPartialUpdateWithoutIdentifier()
    {
        $client = $this->createAuthenticatedClient();

        $data = '{}';

        $expectedProduct = [
            'identifier'    => 'product_categories',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],
            'categories'    => ['master'],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_categories'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_categories');
        $this->assertArrayHasKey('location', $response->headers->all());
        $this->assertSame(
            'http://localhost/api/rest/v1/products/product_categories',
            $response->headers->get('location')
        );
    }

    public function testProductPartialUpdateWithTheIdentifierUpdated()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "new_product_categories"
    }
JSON;

        $expectedProduct = [
            'identifier'    => 'new_product_categories',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],
            'categories'    => ['master'],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'new_product_categories'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'new_product_categories');
        $this->assertArrayHasKey('location', $response->headers->all());
        $this->assertSame(
            'http://localhost/api/rest/v1/products/new_product_categories',
            $response->headers->get('location')
        );

        $product = $this->get('pim_catalog.repository.product')->findOneByIdentifier('product_categories');
        $this->assertSame(null, $product);
    }

    public function testProductPartialUpdateWithTheIdentifierUpdatedWithNull()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": null
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $expectedContent = [
            'code'    => 422,
            'message' => 'Validation failed.',
            'errors'  => [
                [
                    'property' => 'identifier',
                    'message'  => 'The identifier attribute cannot be empty.',
                ],
            ],
        ];

        $response = $client->getResponse();

        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testProductPartialUpdateWithTheFamilyUpdated()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_family",
        "family": "familyA"
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_family', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'product_family',
            'family'        => 'familyA',
            'parent'        => null,
            'groups'        => [],
            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_family'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_family');
        $this->assertEventCount(1, ProductUpdated::class);
    }

    public function testProductPartialUpdateWithTheFamilyDeleted()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_family",
        "family": null
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_family', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'product_family',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],
            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_family'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_family');
    }

    public function testProductPartialUpdateWithTheGroupsUpdated()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_groups",
        "groups": ["groupB", "groupA"]
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_groups', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'product_groups',
            'family'        => null,
            'parent'        => null,
            'groups'        => ['groupA', 'groupB'],
            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_groups'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_groups');
        $this->assertEventCount(1, ProductUpdated::class);
    }

    public function testProductPartialUpdateWithTheGroupsDeleted()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_groups",
        "groups": []
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_groups', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'product_groups',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_groups'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_groups');
        $this->assertEventCount(1, ProductUpdated::class);
    }

    public function testProductPartialUpdateWithTheCategoriesUpdated()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_categories",
        "categories": ["categoryA", "categoryA1"]
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'product_categories',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => ["categoryA", "categoryA1"],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_categories'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_categories');
        $this->assertEventCount(1, ProductUpdated::class);
    }

    public function testProductPartialUpdateWithTheCategoriesDeleted()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_categories",
        "categories": []
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'product_categories',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_categories'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_categories');
        $this->assertEventCount(1, ProductUpdated::class);
    }

    /**
     * @group ce
     */
    public function testProductPartialUpdateWithTheAssociationsUpdated()
    {
        $this->createProductModel([
            'code' => 'a_product_model',
            'family_variant' => 'familyVariantA1',
            'values'  => [],
        ]);

        $client = $this->createAuthenticatedClient();

        $data = <<<JSON
{
    "identifier": "product_associations",
    "associations": {
        "PACK": {
            "groups": ["groupA"],
            "products": ["product_categories", "product_family"]
        },
        "SUBSTITUTION": {
            "product_models": ["a_product_model"]
        }
    }
}
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_associations', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'product_associations',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_associations'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations' => [
                'PACK' => [
                    'groups' => ['groupA'],
                    'products' => ['product_family', 'product_categories'],
                    'product_models' => [],
                ],
                'SUBSTITUTION' => [
                    'groups' => [],
                    'products' => [],
                    'product_models' => ['a_product_model']
                ],
                'UPSELL' => [
                    'groups' => [],
                    'products' => [],
                    'product_models' => [],
                ],
                'X_SELL' => [
                    'groups' => ['groupA'],
                    'products' => ['product_categories'],
                    'product_models' => [],
                ],
            ],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_associations');

        $this->assertEventCount(1, ProductUpdated::class);

    }

    public function testProductPartialUpdateWithTheAssociationsDeletedOnGroups()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_associations",
        "associations": {
            "X_SELL": {
                "groups": []
            }
        }
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_associations', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'product_associations',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_associations'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [
                'PACK'         => ['groups'   => [], 'products' => [], 'product_models' => []],
                'SUBSTITUTION' => ['groups'   => [], 'products' => [], 'product_models' => []],
                'UPSELL'       => ['groups'   => [], 'products' => [], 'product_models' => []],
                'X_SELL'       => ['groups'   => [], 'products' => ['product_categories'], 'product_models' => []],
            ],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_associations');
    }

    public function testProductPartialUpdateWithTheAssociationsDeleted()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_associations",
        "associations": {
            "PACK": {
                "groups": [],
                "products": []
            },
            "X_SELL": {
                "groups": [],
                "products": []
            }
        }
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_associations', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'product_associations',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_associations'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [
                'PACK'         => ['groups'   => [], 'products' => [], 'product_models' => []],
                'SUBSTITUTION' => ['groups'   => [], 'products' => [], 'product_models' => []],
                'UPSELL'       => ['groups'   => [], 'products' => [], 'product_models' => []],
                'X_SELL'       => ['groups'   => [], 'products' => [], 'product_models' => []],
            ],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_associations');
        $this->assertEventCount(1, ProductUpdated::class);
    }

    public function testProductPartialUpdateWithProductDisable()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_categories",
        "enabled": false
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'product_categories',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => ['master'],
            'enabled'       => false,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_categories'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'product_categories');
        $this->assertEventCount(1, ProductUpdated::class);
    }

    public function testProductPartialUpdateWhenProductValueAddedOnAttribute()
    {
        $client = $this->createAuthenticatedClient();

        $akeneoJpgPath = $this->getFileInfoKey($this->getFixturePath('akeneo.jpg'));

        $data =
            <<<JSON
    {
        "identifier": "localizable",
        "values": {
            "a_localizable_image": [{
                "locale": "zh_CN",
                "scope": null,
                "data": "${akeneoJpgPath}"
            }]
        }
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/localizable', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'localizable',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'localizable'],
                ],
                'a_localizable_image' => [
                    ['locale' => 'en_US', 'scope' => null, 'data' => '4/d/e/b/4deb535f0979dea59cf34661e22336459a56bed3_akeneo.txt'],
                    ['locale' => 'fr_FR', 'scope' => null, 'data' => '4/d/e/b/4deb535f0979dea59cf34661e22336459a56bed3_akeneo.txt'],
                    ['locale' => 'zh_CN', 'scope' => null, 'data' => '4/d/e/b/4deb535f0979dea59cf34661e22336459a56bed3_akeneo.txt'],
                ]
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'localizable');
        $this->assertEventCount(1, ProductUpdated::class);
    }

    public function testProductPartialUpdateWhenProductValueUpdatedOnAttribute()
    {
        $client = $this->createAuthenticatedClient();

        $ziggyPngPath = $this->getFileInfoKey($this->getFixturePath('ziggy.png'));

        $data =
            <<<JSON
    {
        "identifier": "localizable",
        "values": {
            "a_localizable_image": [{
                "locale": "en_US",
                "scope": null,
                "data": "${ziggyPngPath}"
            }]
        }
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/localizable', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'localizable',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'localizable'],
                ],
                'a_localizable_image' => [
                    ['locale' => 'en_US', 'scope' => null, 'data' => '4/d/e/b/4deb535f0979dea59cf34661e22336459a56bed3_ziggy.png'],
                    ['locale' => 'fr_FR', 'scope' => null, 'data' => '4/d/e/b/4deb535f0979dea59cf34661e22336459a56bed3_akeneo.txt'],
                ]
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'localizable');
    }

    public function testProductPartialUpdateWhenProductValueDeletedOnAttribute()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
                {
        "identifier": "localizable",
        "values": {
            "a_localizable_image": [{
                "locale": "en_US",
                "scope": null,
                "data": null
            }]
        }
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/localizable', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'localizable',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => [],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'localizable'],
                ],
                'a_localizable_image' => [
                    ['locale' => 'fr_FR', 'scope' => null, 'data' => '4/d/e/b/4deb535f0979dea59cf34661e22336459a56bed3_akeneo.txt'],
                ]
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'localizable');
    }

    public function testProductPartialUpdateOnMultipleAttributes()
    {
        $client = $this->createAuthenticatedClient();

        $files = [
            'akeneo_pdf' => $this->getFileInfoKey($this->getFixturePath('akeneo.pdf')),
            'akeneo_jpg' => $this->getFileInfoKey($this->getFixturePath('akeneo.jpg')),
            'ziggy_png'  => $this->getFileInfoKey($this->getFixturePath('ziggy.png')),
        ];

        $data =
            <<<JSON
    {
        "identifier": "complete",
        "groups": ["groupA", "groupB"],
        "family": "familyA2",
        "categories": ["master", "categoryA"],
        "values": {
            "a_metric": [{
                "locale": null,
                "scope": null,
                "data": null
            }],
            "a_price": [{
                "locale": null,
                "scope": null,
                "data": [{
                    "amount": "56.53",
                    "currency": "EUR"
                },
                {
                    "amount": "45.00",
                    "currency": "USD"
                }]
            }],
            "a_price_without_decimal": [{
                "locale": null,
                "scope": null,
                "data": [{
                    "amount": 56,
                    "currency": "EUR"
                },
                {
                    "amount": -45,
                    "currency": "USD"
                }]
            }],
            "a_ref_data_multi_select": [{
                "locale": null,
                "scope": null,
                "data": ["airguard", "braid"]
            }],
            "a_ref_data_simple_select": [{
                "locale": null,
                "scope": null,
                "data": "bright-lilac"
            }],
            "a_simple_select": [{
                "locale": null,
                "scope": null,
                "data": "optionA"
            }],
            "a_localizable_scopable_image": [{
                "locale": "en_US",
                "scope": "ecommerce",
                "data": "${files['ziggy_png']}"
            }, {
                "locale": "fr_FR",
                "scope": "tablet",
                "data": "${files['akeneo_jpg']}"
            }],
            "a_localized_and_scopable_text_area": [{
                "locale": "en_US",
                "scope": "ecommerce",
                "data": "a text area for ecommerce in English"
            }, {
                "locale": "en_US",
                "scope": "tablet",
                "data": "a text area for tablets in English"
            }, {
                "locale": "fr_FR",
                "scope": "tablet",
                "data": "une zone de texte pour les tablettes en fran\u00e7ais"
            }]
        }
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/complete', [], [], [], $data);

        $expectedProduct = [
            'identifier'    => 'complete',
            'family'        => 'familyA2',
            'parent'        => null,
            'groups'        => ['groupA', 'groupB'],
            'categories'    => ['categoryA', 'master'],
            'enabled'       => true,
            'values'        => [
                'sku'                                => [
                    ['locale' => null, 'scope' => null, 'data' => 'complete'],
                ],
                'a_date'   => [
                    ['locale' => null, 'scope' => null, 'data' => '2016-06-13T00:00:00+02:00'],
                ],
                'a_simple_select'                    => [
                    ['locale' => null, 'scope' => null, 'data' => 'optionA'],
                ],
                'a_price'                            => [
                    [
                        'locale' => null,
                        'scope'  => null,
                        'data'   => [
                            ['amount' => '56.53', 'currency' => 'EUR'],
                            ['amount' => '45.00', 'currency' => 'USD'],
                        ],
                    ],
                ],
                'a_price_without_decimal'            => [
                    [
                        'locale' => null,
                        'scope'  => null,
                        'data'   => [
                            ['amount' => 56, 'currency' => 'EUR'],
                            ['amount' => -45, 'currency' => 'USD'],
                        ],
                    ],
                ],
                'a_ref_data_multi_select'            => [
                    ['locale' => null, 'scope' => null, 'data' => ['airguard', 'braid']],
                ],
                'a_ref_data_simple_select'           => [
                    ['locale' => null, 'scope' => null, 'data' => 'bright-lilac'],
                ],
                'a_localizable_scopable_image'       => [
                    [
                        'locale' => 'en_US',
                        'scope'  => 'ecommerce',
                        'data'   => '6/2/e/3/62e376e75300d27bfec78878db4d30ff1490bc53_ziggy_en_US.png',
                    ],
                    [
                        'locale' => 'fr_FR',
                        'scope'  => 'tablet',
                        'data'   => '0/f/5/0/0f5058de76f68446bb6b2371f19cd2234b245c00_akeneo_fr_FR.jpg',
                    ],
                ],
                'a_localized_and_scopable_text_area' => [
                    [
                        'locale' => 'en_US',
                        'scope'  => 'ecommerce',
                        'data'   => 'a text area for ecommerce in English',
                    ],
                    [
                        'locale' => 'en_US',
                        'scope'  => 'tablet',
                        'data'   => 'a text area for tablets in English',
                    ],
                    [
                        'locale' => 'fr_FR',
                        'scope'  => 'tablet',
                        'data'   => 'une zone de texte pour les tablettes en français',
                    ],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [
                'PACK'         => ['groups'   => [], 'products' => [], 'product_models' => []],
                'SUBSTITUTION' => ['groups'   => [], 'products' => [], 'product_models' => []],
                'UPSELL'       => ['groups'   => [], 'products' => [], 'product_models' => []],
                'X_SELL'       => ['groups'   => ['groupA'], 'products' => ['product_categories'], 'product_models' => []],
            ],
            'quantified_associations' => [],
        ];

        $response = $client->getResponse();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSameProducts($expectedProduct, 'complete');
    }

    public function testProductPartialUpdateWithIgnoredProperties()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_categories",
        "created": "2014-06-14T13:12:50+02:00",
        "updated": "2014-06-14T13:12:50+02:00"
    }
JSON;

        $expectedProduct = [
            'identifier'    => 'product_categories',
            'family'        => null,
            'parent'        => null,
            'groups'        => [],

            'categories'    => ['master'],
            'enabled'       => true,
            'values'        => [
                'sku' => [
                    ['locale' => null, 'scope' => null, 'data' => 'product_categories'],
                ],
            ],
            'created'       => '2016-06-14T13:12:50+02:00',
            'updated'       => '2016-06-14T13:12:50+02:00',
            'associations'  => [],
            'quantified_associations' => [],
        ];

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $response = $client->getResponse();


        $this->assertSame('', $response->getContent());
        $this->assertSameProducts($expectedProduct, 'product_categories');
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $product = $this->get('pim_catalog.repository.product')->findOneByIdentifier('product_categories');
        $standardizedProduct = $this->get('pim_standard_format_serializer')->normalize($product, 'standard');

        $this->assertNotSame('2014-06-14T13:12:50+02:00', $standardizedProduct['created']);
        $this->assertNotSame('2014-06-14T13:12:50+02:00', $standardizedProduct['updated']);
    }

    public function testPartialUpdateResponseWhenIdentifierPropertyNotEqualsToIdentifierInValues()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_categories",
        "values": {
            "sku": [{
                "locale": null,
                "scope": null,
                "data": "foo"
            }]
         }
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertArrayHasKey('location', $response->headers->all());
        $this->assertSame(
            'http://localhost/api/rest/v1/products/product_categories',
            $response->headers->get('location')
        );
        $this->assertSame('', $response->getContent());
    }

    public function testPartialUpdateResponseWhenMissingIdentifierPropertyAndProvidedIdentifierInValues()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "values": {
            "sku": [{
                "locale": null,
                "scope": null,
                "data": "foo"
            }]
         }
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertArrayHasKey('location', $response->headers->all());
        $this->assertSame(
            'http://localhost/api/rest/v1/products/product_categories',
            $response->headers->get('location')
        );
        $this->assertSame('', $response->getContent());
    }

    public function testResponseWhenProductAlreadyExists()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_family"
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $expectedContent = [
            'code'    => 422,
            'message' => 'Validation failed.',
            'errors'  => [
                [
                    'property' => 'identifier',
                    'message'  => 'The product_family identifier is already used for another product.',
                ],
            ],
        ];

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testResponseWhenAPropertyIsNotExpected()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "extra_property": "foo"
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);
        $expectedContent = [
            'code'    => 422,
            'message' => 'Property "extra_property" does not exist. Check the expected format on the API documentation.',
            '_links'  => [
                'documentation' => [
                    'href' => 'http://api.akeneo.com/api-reference.html#patch_products__code_'
                ],
            ],
        ];

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testResponseWhenContentIsEmpty()
    {
        $client = $this->createAuthenticatedClient();

        $data = '';

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $expectedContent = [
            'code'    => 400,
            'message' => 'Invalid json message received',
        ];

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testResponseWhenContentIsNotValid()
    {
        $client = $this->createAuthenticatedClient();

        $data = '{';

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);

        $expectedContent = [
            'code'    => 400,
            'message' => 'Invalid json message received',
        ];

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testProductPartialUpdateWithProductDisableWithNullValue()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_categories",
        "enabled": null
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_categories', [], [], [], $data);
        $expectedContent = [
            'code'    => 422,
            'message' => 'Property "enabled" expects a boolean as data, "NULL" given. Check the expected format on the API documentation.',
            '_links'  => [
                'documentation' => [
                    'href' => 'http://api.akeneo.com/api-reference.html#patch_products__code_'
                ],
            ],
        ];

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testProductPartialUpdateWithAnUnknownAttribute()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_family",
        "family": "familyA2",
        "groups": [],
        "categories": [],
        "values": {
            "unknown_attribute":[{
                "locale": null,
                "scope": null,
                "data": true
            }]
        },
        "associations": {}
    }
JSON;

        $client->request('PATCH', 'api/rest/v1/products/product_family', [], [], [], $data);
        $expectedContent = [
            'code'    => 422,
            'message' => 'The unknown_attribute attribute does not exist in your PIM. Check the expected format on the API documentation.',
            '_links'  => [
                'documentation' => [
                    'href' => "http://api.akeneo.com/api-reference.html#patch_products__code_"
                ],
            ],
        ];

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testProductPartialUpdateWithInvalidFieldData()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "product_family",
        "family": ["familyA"]
    }
JSON;
        $client->request('PATCH', 'api/rest/v1/products/product_family', [], [], [], $data);

        $expectedContent = [
            'code'    => 422,
            'message' => 'Property "family" expects a scalar as data, "array" given. Check the expected format on the API documentation.',
            '_links'  => [
                'documentation' => [
                    'href' => "http://api.akeneo.com/api-reference.html#patch_products__code_"
                ],
            ],
        ];

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testProductPartialUpdateWithInvalidAttributeData()
    {
        $client = $this->createAuthenticatedClient();

        $data =
            <<<JSON
    {
        "identifier": "big_boot",
        "family": "familyA",
        "values": {
            "a_text":[{
                "locale": null,
                "scope": null,
                "data": ["an_array"]
            }]
        }
    }
JSON;
        $client->request('PATCH', 'api/rest/v1/products/big_boot', [], [], [], $data);

        $expectedContent = [
            'code'    => 422,
            'message' => 'The a_text attribute requires a string, a array was detected. Check the expected format on the API documentation.',
            '_links'  => [
                'documentation' => [
                    'href' => "http://api.akeneo.com/api-reference.html#patch_products__code_"
                ],
            ],
        ];

        $response = $client->getResponse();
        $this->assertSame($expectedContent, json_decode($response->getContent(), true));
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testResponseWhenAssociatingToNonExistingProductModel()
    {
        $client = $this->createAuthenticatedClient();

        $data = <<<JSON
{
    "identifier": "big_boot",
    "associations": {
        "X_SELL": {
            "product_models": ["a_non_exiting_product_model"]
        }
    }
}
JSON;

        $expected = <<<JSON
{
    "code": 422,
    "message": "Property \"associations\" expects a valid product model identifier. The product model does not exist, \"a_non_exiting_product_model\" given. Check the expected format on the API documentation.",
    "_links": {
        "documentation": {
            "href": "http:\/\/api.akeneo.com\/api-reference.html#post_products"
        }
    }
}
JSON;

        $client->request('POST', 'api/rest/v1/products', [], [], [], $data);

        $response = $client->getResponse();

        $this->assertJsonStringEqualsJsonString($expected, $response->getContent());
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testAccessDeniedWhenPartialUpdateOnProductWithoutTheAcl()
    {
        $client = $this->createAuthenticatedClient();
        $this->removeAclFromRole('action:pim_api_product_edit');

        $data =
            <<<JSON
{"identifier": "foo"}
JSON;

        $client->request('PATCH', 'api/rest/v1/products/foo', [], [], [], $data);
        $response = $client->getResponse();

        $logger = self::$container->get('monolog.logger.pim_api_product_acl');
        assert($logger instanceof TestLogger);

        $this->assertTrue(
            $logger->hasWarning('User "admin" with roles ROLE_ADMINISTRATOR is not granted "pim_api_product_edit"'),
            'Expected warning not found in the logs.'
        );

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration(): Configuration
    {
        return $this->catalog->useTechnicalCatalog();
    }
}
