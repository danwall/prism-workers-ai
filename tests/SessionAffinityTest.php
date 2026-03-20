<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Prism\Prism\Facades\Prism;

it('sends x-session-affinity header when provider option is set', function () {
    Http::fake([
        'gateway.ai.cloudflare.com/*' => Http::response(
            $this->fixture('text-response.json'),
        ),
    ]);

    Prism::text()
        ->using('workers-ai', 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast')
        ->withProviderOptions(['session_affinity' => 'ses_test-conversation-123'])
        ->withPrompt('Hello!')
        ->asText();

    Http::assertSent(function ($request) {
        return $request->hasHeader('x-session-affinity', 'ses_test-conversation-123');
    });
});

it('does not send x-session-affinity header when provider option is not set', function () {
    Http::fake([
        'gateway.ai.cloudflare.com/*' => Http::response(
            $this->fixture('text-response.json'),
        ),
    ]);

    Prism::text()
        ->using('workers-ai', 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast')
        ->withPrompt('Hello!')
        ->asText();

    Http::assertSent(function ($request) {
        return ! $request->hasHeader('x-session-affinity');
    });
});

it('sends x-session-affinity header in streaming mode', function () {
    $streamBody = file_get_contents(__DIR__.'/Fixtures/stream-response.txt');

    Http::fake([
        'gateway.ai.cloudflare.com/*' => Http::response($streamBody, 200, [
            'Content-Type' => 'text/event-stream',
        ]),
    ]);

    $stream = Prism::text()
        ->using('workers-ai', 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast')
        ->withProviderOptions(['session_affinity' => 'ses_stream-test'])
        ->withPrompt('Hello!')
        ->asStream();

    // Consume the stream
    foreach ($stream as $_event) {}

    Http::assertSent(function ($request) {
        return $request->hasHeader('x-session-affinity', 'ses_stream-test');
    });
});

it('sends x-session-affinity header in structured mode', function () {
    Http::fake([
        'gateway.ai.cloudflare.com/*' => Http::response(
            $this->fixture('structured-response.json'),
        ),
    ]);

    Prism::structured()
        ->using('workers-ai', 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast')
        ->withProviderOptions(['session_affinity' => 'ses_structured-test'])
        ->withSchema(new \Prism\Prism\Schema\ObjectSchema(
            name: 'test',
            description: 'test',
            properties: [new \Prism\Prism\Schema\StringSchema('name', 'name')],
            requiredFields: ['name'],
        ))
        ->withPrompt('Return a name.')
        ->generate();

    Http::assertSent(function ($request) {
        return $request->hasHeader('x-session-affinity', 'ses_structured-test');
    });
});
