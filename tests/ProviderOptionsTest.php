<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Prism\Prism\Facades\Prism;

it('forwards reasoning_effort to text request payload', function () {
    Http::fake([
        'gateway.ai.cloudflare.com/*' => Http::response(
            $this->fixture('text-response.json'),
        ),
    ]);

    Prism::text()
        ->using('workers-ai', 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast')
        ->withPrompt('Hello!')
        ->withProviderOptions(['reasoning_effort' => 'high'])
        ->asText();

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return data_get($body, 'reasoning_effort') === 'high';
    });
});

it('forwards reasoning_effort to streaming request payload', function () {
    $streamBody = file_get_contents(__DIR__.'/Fixtures/stream-response.txt');

    Http::fake([
        'gateway.ai.cloudflare.com/*' => Http::response($streamBody, 200, [
            'Content-Type' => 'text/event-stream',
        ]),
    ]);

    $events = Prism::text()
        ->using('workers-ai', 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast')
        ->withPrompt('Hello!')
        ->withProviderOptions(['reasoning_effort' => 'medium'])
        ->asStream();

    // Consume the generator
    foreach ($events as $event) {
        // noop
    }

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return data_get($body, 'reasoning_effort') === 'medium';
    });
});

it('forwards reasoning_effort to structured request payload', function () {
    Http::fake([
        'gateway.ai.cloudflare.com/*' => Http::response(
            $this->fixture('structured-response.json'),
        ),
    ]);

    Prism::structured()
        ->using('workers-ai', 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast')
        ->withPrompt('Classify this')
        ->withSchema(new \Prism\Prism\Schema\ObjectSchema(
            'intent',
            'The intent',
            [
                new \Prism\Prism\Schema\StringSchema('intent', 'The intent'),
            ],
        ))
        ->withProviderOptions(['reasoning_effort' => 'low'])
        ->asStructured();

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return data_get($body, 'reasoning_effort') === 'low';
    });
});

it('does not forward internal provider options to the payload', function () {
    Http::fake([
        'gateway.ai.cloudflare.com/*' => Http::response(
            $this->fixture('text-response.json'),
        ),
    ]);

    Prism::text()
        ->using('workers-ai', 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast')
        ->withPrompt('Hello!')
        ->withProviderOptions([
            'session_affinity' => 'ses_abc123',
            'reasoning_effort' => 'high',
        ])
        ->asText();

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        // reasoning_effort should be forwarded
        // session_affinity should NOT appear in the payload (it's a header, not a body param)
        return data_get($body, 'reasoning_effort') === 'high'
            && ! array_key_exists('session_affinity', $body);
    });
});

it('sends empty provider options without affecting payload', function () {
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
        $body = json_decode($request->body(), true);

        // Should have standard keys but no extra provider options
        return array_key_exists('model', $body)
            && array_key_exists('messages', $body)
            && ! array_key_exists('reasoning_effort', $body);
    });
});
