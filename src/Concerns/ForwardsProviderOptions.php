<?php

declare(strict_types=1);

namespace PrismWorkersAi\Concerns;

use Prism\Prism\Contracts\PrismRequest;

trait ForwardsProviderOptions
{
    /**
     * Extract provider options that should be forwarded to the API payload.
     *
     * Filters out keys consumed internally by this package (e.g. session_affinity,
     * schema) and returns the rest for direct inclusion in the request body.
     *
     * @return array<string, mixed>
     */
    protected function forwardedProviderOptions(PrismRequest $request): array
    {
        $options = $request->providerOptions() ?? [];

        $internal = ['session_affinity', 'schema'];

        return array_diff_key($options, array_flip($internal));
    }
}
