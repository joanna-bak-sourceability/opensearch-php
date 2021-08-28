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

namespace OpenSearch\Endpoints\Security;

use OpenSearch\Endpoints\AbstractEndpoint;

/**
 * Class GetApiKey
 * Elasticsearch API name security.get_api_key
 *
 */
class GetApiKey extends AbstractEndpoint
{
    public function getURI(): string
    {
        return "/_security/api_key";
    }

    public function getParamWhitelist(): array
    {
        return [
            'id',
            'name',
            'username',
            'realm_name',
            'owner'
        ];
    }

    public function getMethod(): string
    {
        return 'GET';
    }
}
