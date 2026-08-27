<?php

namespace App\Services;

use App\Ai\Agents\MessageTranslationAgent;
use Throwable;

class AiChatTranslationService
{
    /**
     * Translate a message when its role and language match one of the
     * configured support directions.
     *
     * @return array<string, string>
     */
    public function attributesFor(string $role, string $content): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $content = trim($content);
        $target = $this->targetLanguage($role, $content);

        if ($content === '' || $target === null) {
            return [];
        }

        try {
            $response = MessageTranslationAgent::make()->prompt(
                $this->prompt($content, $target),
                [],
                config('aichat.provider'),
                config('aichat.model'),
            );
            $translated = trim($response->text);

            if ($translated === '' || $translated === $content) {
                return [];
            }

            return [
                'translated_content' => $translated,
                'translation_target' => $target,
            ];
        } catch (Throwable $exception) {
            // Translation is an enhancement. A provider failure must not stop
            // a customer message or an otherwise valid support reply.
            report($exception);

            return [];
        }
    }

    private function enabled(): bool
    {
        return filter_var(
            config('aichat.translation.enabled', false),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    private function targetLanguage(string $role, string $content): ?string
    {
        if ($role === 'user' && $this->containsLatin($content) && ! $this->containsHan($content)) {
            return 'zh-CN';
        }

        if ($role === 'admin' && $this->containsHan($content)) {
            return 'en';
        }

        return null;
    }

    private function containsLatin(string $content): bool
    {
        return preg_match('/[A-Za-z]/', $content) === 1;
    }

    private function containsHan(string $content): bool
    {
        return preg_match('/\p{Han}/u', $content) === 1;
    }

    private function prompt(string $content, string $target): string
    {
        $language = $target === 'zh-CN' ? 'Simplified Chinese' : 'English';

        return <<<PROMPT
        Translate this customer-support message into {$language}.
        Return only the translated message.

        MESSAGE:
        {$content}
        PROMPT;
    }
}
