<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Small, dedicated agent used for the opt-in support-message translation
 * layer. Keeping this separate from the customer-support agent prevents
 * translation prompts from receiving support tools or conversation history.
 */
class MessageTranslationAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You are a precise customer-support translator for InkPavo.
        Translate only the message supplied by the user into the requested
        target language. Return only the translation, with no explanation,
        quotation marks, or translator notes. Preserve URLs, numbers, names,
        line breaks, and the message's tone.
        PROMPT;
    }

    public function messages(): iterable
    {
        return [];
    }

    public function tools(): iterable
    {
        return [];
    }
}
