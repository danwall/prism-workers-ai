<?php

declare(strict_types=1);

namespace PrismWorkersAi\Concerns;

use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Text\Request as TextRequest;

trait AppliesSessionAffinity
{
    /**
     * Apply session affinity header if provided via provider options.
     *
     * Enables prefix caching on Workers AI by routing requests with the same
     * session ID to the same model instance. Pass a consistent session ID
     * (e.g., conversation UUID) to benefit from cached input tensors on
     * multi-turn conversations.
     *
     * Usage:
     *   ->withProviderOptions(['session_affinity' => 'ses_' . $conversationId])
     */
    protected function applySessionAffinity(TextRequest|StructuredRequest $request): void
    {
        $sessionAffinity = $request->providerOptions('session_affinity');

        if (is_string($sessionAffinity) && $sessionAffinity !== '') {
            $this->client->withHeaders(['x-session-affinity' => $sessionAffinity]);
        }
    }
}
