<?php

declare(strict_types=1);

/*
 * This file is part of the Akeneo PIM Enterprise Edition.
 *
 * (c) 2020 Akeneo SAS (http://www.akeneo.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Akeneo\Test\Pim\Automation\DataQualityInsights\Integration\Persistence\Query\Completeness;

use Akeneo\Pim\Automation\DataQualityInsights\Domain\ValueObject\ProductId;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Test\Integration\TestCase;
use Webmozart\Assert\Assert;

abstract class CompletenessTestCase extends TestCase
{
    protected function getConfiguration()
    {
        return $this->catalog->useMinimalCatalog();
    }

    protected function givenFamilies(array $familiesData): void
    {
        $families = array_map(function ($familyData) {
            $family = $this->get('pim_catalog.factory.family')->create();
            $this->get('pim_catalog.updater.family')->update(
                $family,
                [
                    'code' => $familyData['code'],
                    'attributes'  =>  $familyData['attribute_codes'] ?? [],
                    'attribute_requirements' => $familyData['attribute_requirements'] ?? [],
                ]
            );

            $errors = $this->get('validator')->validate($family);
            Assert::count($errors, 0);

            return $family;
        }, $familiesData);

        $this->get('pim_catalog.saver.family')->saveAll($families);
    }

    protected function givenAFamilyVariant(array $familyVariantData): void
    {
        $family_variant = $this->get('pim_catalog.factory.family_variant')->create();
        $this->get('pim_catalog.updater.family_variant')->update($family_variant, $familyVariantData);

        $errors = $this->get('validator')->validate($family_variant);
        Assert::count($errors, 0);

        $this->get('pim_catalog.saver.family_variant')->save($family_variant);
    }

    protected function givenAttributes(array $attributesData): void
    {
        $attributes = array_map(function ($attributeData) {
            $attribute = $this->get('pim_catalog.factory.attribute')->create();
            $this->get('pim_catalog.updater.attribute')->update(
                $attribute,
                [
                    'code' => $attributeData['code'],
                    'type' => $attributeData['type'],
                    'localizable' => $attributeData['localizable'] ?? false,
                    'scopable' => $attributeData['scopable'] ?? false,
                    'group' => 'other',
                    'available_locales' => $attributeData['available_locales'] ?? [],
                    'decimals_allowed' => $attributeData['type'] === AttributeTypes::PRICE_COLLECTION ? false : null,
                ]
            );

            $errors = $this->get('validator')->validate($attribute);
            Assert::count($errors, 0);

            return $attribute;
        }, $attributesData);

        $this->get('pim_catalog.saver.attribute')->saveAll($attributes);
    }

    protected function givenCurrencyForChannel(array $channelsData): void
    {
        $channels = array_map(function ($channelData) {
            $channel = $this->get('pim_catalog.repository.channel')->findOneBy(['code' => $channelData['code']]);
            $this->get('pim_catalog.updater.channel')->update(
                $channel,
                [
                    'currencies' => $channelData['currencies']
                ]
            );

            $errors = $this->get('validator')->validate($channel);
            Assert::count($errors, 0);

            return $channel;
        }, $channelsData);

        $this->get('pim_catalog.saver.channel')->saveAll($channels);
    }

    protected function givenChannels(array $channelsData): void
    {
        $channels = array_map(function ($channelData) {
            $channel = $this->get('pim_catalog.factory.channel')->create();
            $this->get('pim_catalog.updater.channel')->update(
                $channel,
                [
                    'code' => $channelData['code'],
                    'locales' => $channelData['locales'],
                    'currencies' => $channelData['currencies'],
                    'category_tree' => 'master'
                ]
            );

            $errors = $this->get('validator')->validate($channel);
            Assert::count($errors, 0);

            return $channel;
        }, $channelsData);

        $this->get('pim_catalog.saver.channel')->saveAll($channels);
    }

    protected function givenAProductModel(string $productModelCode, string $familyVariant): ProductId
    {
        $productModel = $this->get('akeneo_integration_tests.catalog.product_model.builder')
            ->withCode($productModelCode)
            ->withFamilyVariant($familyVariant)
            ->build();

        $this->get('pim_catalog.saver.product_model')->save($productModel);

        return new ProductId((int) $productModel->getId());
    }

    protected function givenASubProductModel(string $productModelCode, string $familyVariant, string $parentCode): ProductId
    {
        $productModel = $this->get('akeneo_integration_tests.catalog.product_model.builder')
            ->withCode($productModelCode)
            ->withFamilyVariant($familyVariant)
            ->withParent($parentCode)
            ->build();

        $this->get('pim_catalog.saver.product_model')->save($productModel);

        return new ProductId((int) $productModel->getId());
    }
}
