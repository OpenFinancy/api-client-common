<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Filter;

/**
 * Composite filter component capable of combining multiple child filters.
 */
final class FilterComposite implements FilterComponentInterface
{
    /**
     * @var list<FilterComponentInterface>
     */
    private array $components = [];

    public function __construct(FilterComponentInterface ...$components)
    {
        foreach ($components as $component) {
            $this->add($component);
        }
    }

    public function add(FilterComponentInterface $component): self
    {
        $this->components[] = $component;

        return $this;
    }

    public function apply(array $parameters = []): array
    {
        $result = $parameters;

        foreach ($this->components as $component) {
            $result = $component->apply($result);
        }

        return $result;
    }

    public function isEmpty(): bool
    {
        return [] === $this->components;
    }
}


