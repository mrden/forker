<?php

namespace Mrden\Fork\Contracts;

interface DataPreparable
{
    /**
     * Prepare the main data for all clones of the process
     */
    public function prepareData(): void;
}
