<?php

declare(strict_types=1);

/**
 * SPDX-License-Identifier: Apache-2.0
 *
 * The OpenSearch Contributors require contributions made to
 * this file be licensed under the Apache-2.0 license or a
 * compatible open source license.
 *
 * Modifications Copyright OpenSearch Contributors. See
 * GitHub history for details.
 */

namespace OpenSearch\Endpoints\Indices;

use OpenSearch\Endpoints\AbstractEndpoint;

/**
 * Class Flush
 * Elasticsearch API name indices.flush
 *
 */
class Flush extends AbstractEndpoint
{
    public function getURI(): string
    {
        $index = $this->index ?? null;

        if (isset($index)) {
            return "/$index/_flush";
        }
        return "/_flush";
    }

    public function getParamWhitelist(): array
    {
        return [
            'force',
            'wait_if_ongoing',
            'ignore_unavailable',
            'allow_no_indices',
            'expand_wildcards'
        ];
    }

    public function getMethod(): string
    {
        return 'POST';
    }
}
