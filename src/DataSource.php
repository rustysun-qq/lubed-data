<?php

namespace Lubed\Data;

/**
 * DataSource interface
 */
interface DataSource
{
    public function getTablesData(): array;
}