<?php

namespace PhpTwinfield;

class BrowseSortField
{
    /** @var string */
    private $code;

    /** @var string|null */
    private $order;

    /**
     * SortField constructor.
     *
     * @param string $code
     * @param null|string $order
     */
    public function __construct(string $code, ?string $order = null)
    {
        $this->code = $code;
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return null|string
     */
    public function getOrder(): ?string
    {
        return $this->order;
    }

    /**
     * @param null|string $order
     */
    public function setOrder(?string $order): void
    {
        $this->order = $order;
    }
}
