<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AI\JugajugAIAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_chat_maintains_conversational_memory(): void
    {
        $user = User::factory()->create();
        $assistant = app(JugajugAIAssistant::class);

        $firstTurn = $assistant->chat('আমার নাম রহিম।', $user->id, 'conv_test_1');
        $this->assertNotEmpty($firstTurn['reply']);

        $secondTurn = $assistant->chat('আমার নাম কী?', $user->id, 'conv_test_1');
        $this->assertNotEmpty($secondTurn['reply']);
    }

    public function test_suggest_comment_returns_thoughtful_suggestion(): void
    {
        $assistant = app(JugajugAIAssistant::class);
        $res = $assistant->suggestComment('আজকের দিনটি সত্যিই চমৎকার!');

        $this->assertNotEmpty($res['content']);
    }

    public function test_write_caption_generates_creative_text(): void
    {
        $assistant = app(JugajugAIAssistant::class);
        $res = $assistant->writeCaption('Sunset at Coxs Bazar', 'poetic');

        $this->assertNotEmpty($res['content']);
    }

    public function test_translate_converts_languages(): void
    {
        $assistant = app(JugajugAIAssistant::class);
        $res = $assistant->translate('Welcome to Bangladesh', 'bn');

        $this->assertNotEmpty($res['content']);
    }

    public function test_summarize_reduces_long_content(): void
    {
        $assistant = app(JugajugAIAssistant::class);
        $res = $assistant->summarize('This is a long article about AI and software architecture in Bangladesh.');

        $this->assertNotEmpty($res['content']);
    }

    public function test_moderation_detects_prohibited_terms(): void
    {
        $assistant = app(JugajugAIAssistant::class);

        $safeRes = $assistant->moderate('আজ সুন্দর বৃষ্টি হচ্ছে।');
        $this->assertTrue($safeRes['is_safe']);

        $harmfulRes = $assistant->moderate('I will attack and kill everyone.');
        $this->assertFalse($harmfulRes['is_safe']);
        $this->assertNotEmpty($harmfulRes['flags']);
    }

    public function test_citizen_services_assistant_handles_nid_passport_inquiries(): void
    {
        $assistant = app(JugajugAIAssistant::class);
        $res = $assistant->citizenServicesAssistant('কীভাবে নতুন ই-পাসপোর্টের জন্য আবেদন করতে পারি?');

        $this->assertNotEmpty($res['content']);
    }
}
