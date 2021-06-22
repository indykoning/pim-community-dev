<?php

declare(strict_types=1);

namespace Specification\Akeneo\Pim\Enrichment\Component\Product\Exception;

use Akeneo\Pim\Enrichment\Component\Error\DomainErrorInterface;
use Akeneo\Pim\Enrichment\Component\Error\TemplatedErrorMessage\TemplatedErrorMessageInterface;
use Akeneo\Pim\Enrichment\Component\Product\Exception\NotLocalizableAndNotScopableAttributeException;
use PhpSpec\ObjectBehavior;

class NotLocalizableAndNotScopableAttributeExceptionSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beConstructedThrough('fromAttributeChannelAndLocale', ['description', 'ecommerce', 'en_US']);
    }

    public function it_is_a_domain_and_templated_error_exception(): void
    {
        $this->shouldHaveType(NotLocalizableAndNotScopableAttributeException::class);
        $this->shouldImplement(DomainErrorInterface::class);
        $this->shouldImplement(TemplatedErrorMessageInterface::class);
    }

    public function it_provides_a_templated_error_message(): void
    {
        $templatedMessage = $this->getTemplatedErrorMessage();
        $templatedMessage
            ->getTemplate()
            ->shouldReturn(
                'The {attribute_code} attribute requires neither a channel ({channel_code} was detected)' .
                ' nor a locale ({locale_code} was detected).',
            );
        $templatedMessage->getParameters()->shouldReturn([
            'attribute_code' => 'description',
            'channel_code' => 'ecommerce',
            'locale_code' => 'en_US',
        ]);
    }

    public function it_provides_the_attribute_code(): void
    {
        $this->getAttributeCode()->shouldReturn('description');
    }

    public function it_provides_the_property_name(): void
    {
        $this->getPropertyName()->shouldReturn('attribute');
    }
}
