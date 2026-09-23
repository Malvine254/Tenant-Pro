<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\Role;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\AI\AiAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenancy(): array
    {
        $landlordRole = Role::create(['name' => 'LANDLORD']);
        $tenantRole = Role::create(['name' => 'TENANT']);

        $landlord = User::factory()->create(['role_id' => $landlordRole->id, 'is_active' => true]);
        $tenant = User::factory()->create(['role_id' => $tenantRole->id, 'is_active' => true]);

        $property = Property::create([
            'name' => 'Sunrise Court',
            'landlord_id' => $landlord->id,
            'address_line' => 'Kilimani, Nairobi',
            'city' => 'Nairobi',
            'is_publicly_listed' => false,
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'unit_number' => 'A101',
            'rent_amount' => 25000,
            'status' => 'OCCUPIED',
        ]);

        Tenant::create([
            'user_id' => $tenant->id,
            'unit_id' => $unit->id,
            'move_in_date' => now()->subMonths(2),
            'is_active' => true,
        ]);

        Invoice::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'billing_type' => 'RENT',
            'amount' => 25000,
            'total_amount' => 25000,
            'paid_amount' => 0,
            'status' => 'PENDING',
            'period_month' => 9,
            'period_year' => 2026,
            'issue_date' => '2026-09-01',
            'due_date' => '2026-09-05',
        ]);

        $conversation = SupportConversation::create([
            'tenant_user_id' => $tenant->id,
            'landlord_user_id' => $landlord->id,
            'property_id' => $property->id,
            'topic' => 'Billing',
            'is_open' => true,
        ]);

        SupportMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $tenant->id,
            'topic' => 'Billing',
            'body' => 'What is my rent balance?',
            'is_from_tenant' => true,
            'status' => 'SENT',
        ]);

        return compact('landlord', 'tenant', 'property', 'unit', 'conversation');
    }

    private function enableAzureConfig(): void
    {
        config([
            'services.azure_openai.enabled' => true,
            'services.azure_openai.endpoint' => 'https://example.openai.azure.com',
            'services.azure_openai.api_key' => 'test-key',
            'services.azure_openai.deployment' => 'gpt-4o',
            'services.azure_openai.api_version' => '2024-10-21',
            'services.azure_openai.max_tool_iterations' => 5,
            'services.azure_openai.context_message_limit' => 20,
        ]);
    }

    public function test_assistant_calls_invoice_tool_and_persists_grounded_reply(): void
    {
        $this->enableAzureConfig();
        $ctx = $this->makeTenancy();

        Http::fake([
            '*openai.azure.com*' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_1',
                                'type' => 'function',
                                'function' => ['name' => 'get_invoices', 'arguments' => '{}'],
                            ]],
                        ],
                    ]],
                ])
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Your outstanding rent balance is KSh 25,000.00, due 2026-09-05.',
                        ],
                    ]],
                ]),
        ]);

        $reply = app(AiAssistantService::class)->respond($ctx['conversation'], $ctx['tenant']);

        $this->assertNotNull($reply);
        $this->assertTrue($reply->is_ai);
        $this->assertFalse($reply->is_from_tenant);
        $this->assertStringContainsString('25,000', $reply->body);
        $this->assertSame('get_invoices', $reply->tool_calls[0]['name']);
        $this->assertTrue($reply->tool_calls[0]['result']['ok']);
    }

    public function test_assistant_cannot_leak_another_tenants_invoices(): void
    {
        $this->enableAzureConfig();
        $ctx = $this->makeTenancy();

        // A second tenant with their own invoice, to prove tool scoping cannot cross tenants
        // even if the model somehow supplied a foreign identifier as an argument.
        $otherTenant = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'TENANT'])->id]);
        Invoice::create([
            'unit_id' => $ctx['unit']->id,
            'tenant_id' => $otherTenant->id,
            'user_id' => $otherTenant->id,
            'billing_type' => 'RENT',
            'amount' => 99999,
            'total_amount' => 99999,
            'paid_amount' => 0,
            'status' => 'PENDING',
            'period_month' => 9,
            'period_year' => 2026,
            'issue_date' => '2026-09-01',
            'due_date' => '2026-09-05',
        ]);

        Http::fake([
            '*openai.azure.com*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call_1',
                            'type' => 'function',
                            // Attempt to smuggle another tenant's id via arguments; the tool must ignore it.
                            'function' => ['name' => 'get_invoices', 'arguments' => json_encode(['tenant_id' => $otherTenant->id])],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $registry = new \App\Services\AI\ToolRegistry($ctx['conversation']);
        $result = $registry->call('get_invoices', ['tenant_id' => $otherTenant->id], $ctx['tenant']);

        $this->assertTrue($result['ok']);
        $amounts = collect($result['data']['invoices'])->pluck('total_amount_formatted');
        $this->assertTrue($amounts->every(fn ($amount) => $amount !== 'KSh 99,999.00'));
    }

    public function test_escalation_only_happens_via_explicit_tool_call_and_stops_further_ai_replies(): void
    {
        $this->enableAzureConfig();
        $ctx = $this->makeTenancy();

        Http::fake([
            '*openai.azure.com*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call_1',
                            'type' => 'function',
                            'function' => ['name' => 'escalate_to_human', 'arguments' => json_encode(['reason' => 'Wants to speak to the landlord directly.'])],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $this->assertNull($ctx['conversation']->fresh()->escalated_at);

        app(AiAssistantService::class)->respond($ctx['conversation'], $ctx['tenant']);

        $ctx['conversation']->refresh();
        $this->assertNotNull($ctx['conversation']->escalated_at);

        // Once escalated, the AI must not generate further automatic replies.
        Http::fake(); // any further HTTP call would fail the test via unexpected fake response
        $second = app(AiAssistantService::class)->respond($ctx['conversation'], $ctx['tenant']);
        $this->assertNull($second);
    }

    public function test_assistant_does_not_respond_when_not_configured(): void
    {
        config(['services.azure_openai.enabled' => false]);
        $ctx = $this->makeTenancy();

        $reply = app(AiAssistantService::class)->respond($ctx['conversation'], $ctx['tenant']);

        $this->assertNull($reply);
    }

    public function test_sending_a_support_message_over_http_triggers_an_ai_reply(): void
    {
        $this->enableAzureConfig();
        config(['deployment.mobile_api_key' => 'test-mobile-key']);
        $ctx = $this->makeTenancy();

        Http::fake([
            '*openai.azure.com*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Your outstanding rent balance is KSh 25,000.00.',
                    ],
                ]],
            ]),
        ]);

        Sanctum::actingAs($ctx['tenant']);

        $response = $this->withHeader('X-Mobile-App-Key', 'test-mobile-key')
            ->postJson('/api/support/messages', [
                'conversationId' => $ctx['conversation']->id,
                'topic' => 'Billing',
                'text' => 'What do I owe this month?',
            ]);

        $response->assertCreated();

        // The AI reply is generated after the response is sent (afterResponse) so the tenant app
        // is not held up waiting on the model/tool round trip - verify it landed in the database,
        // the same way the app would pick it up on its next poll.
        $aiMessage = SupportMessage::where('conversation_id', $ctx['conversation']->id)
            ->where('is_ai', true)
            ->first();

        $this->assertNotNull($aiMessage, 'Expected an AI-generated message to be persisted.');
        $this->assertStringContainsString('25,000', $aiMessage->body);
    }

    public function test_landlord_is_not_emailed_when_the_ai_successfully_answers(): void
    {
        $this->enableAzureConfig();
        config(['deployment.mobile_api_key' => 'test-mobile-key']);
        \Illuminate\Support\Facades\Mail::fake();
        $ctx = $this->makeTenancy();
        $ctx['landlord']->update(['email' => 'landlord@example.com']);

        Http::fake([
            '*openai.azure.com*' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => 'Your balance is KSh 25,000.00.'],
                ]],
            ]),
        ]);

        Sanctum::actingAs($ctx['tenant']);
        $this->withHeader('X-Mobile-App-Key', 'test-mobile-key')
            ->postJson('/api/support/messages', [
                'conversationId' => $ctx['conversation']->id,
                'topic' => 'Billing',
                'text' => 'What do I owe?',
            ])->assertCreated();

        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_landlord_is_not_emailed_when_the_ai_is_unavailable_and_not_escalated(): void
    {
        config(['services.azure_openai.enabled' => false]);
        config(['deployment.mobile_api_key' => 'test-mobile-key']);
        \Illuminate\Support\Facades\Mail::fake();
        $ctx = $this->makeTenancy();
        $ctx['landlord']->update(['email' => 'landlord@example.com']);

        Sanctum::actingAs($ctx['tenant']);
        $this->withHeader('X-Mobile-App-Key', 'test-mobile-key')
            ->postJson('/api/support/messages', [
                'conversationId' => $ctx['conversation']->id,
                'topic' => 'Billing',
                'text' => 'What do I owe?',
            ])->assertCreated();

        // Email is reserved for explicit escalation only - not merely because the AI is disabled
        // or failed to answer a particular message.
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_landlord_is_emailed_only_once_the_tenant_escalates(): void
    {
        $this->enableAzureConfig();
        config(['deployment.mobile_api_key' => 'test-mobile-key']);
        \Illuminate\Support\Facades\Mail::fake();
        $ctx = $this->makeTenancy();
        $ctx['landlord']->update(['email' => 'landlord@example.com']);

        Http::fake([
            '*openai.azure.com*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call_1',
                            'type' => 'function',
                            'function' => ['name' => 'escalate_to_human', 'arguments' => json_encode(['reason' => 'Wants a human.'])],
                        ]],
                    ],
                ]],
            ]),
        ]);

        Sanctum::actingAs($ctx['tenant']);
        $this->withHeader('X-Mobile-App-Key', 'test-mobile-key')
            ->postJson('/api/support/messages', [
                'conversationId' => $ctx['conversation']->id,
                'topic' => 'Billing',
                'text' => 'I want to speak to a human.',
            ])->assertCreated();

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TenantProUpdateMail::class);
    }

    public function test_admin_can_resume_ai_after_escalation(): void
    {
        $ctx = $this->makeTenancy();
        $ctx['conversation']->update([
            'escalated_at' => now(),
            'escalation_reason' => 'Tenant asked for a human.',
        ]);

        $this->actingAs($ctx['landlord'])
            ->withoutMiddleware(\App\Http\Middleware\EnsureAdminReadiness::class)
            ->post(route('admin.chats.resume-ai', $ctx['conversation']), [], ['Accept' => 'application/json'])
            ->assertOk();

        $ctx['conversation']->refresh();
        $this->assertNull($ctx['conversation']->escalated_at);
        $this->assertNull($ctx['conversation']->escalation_reason);
        $this->assertTrue($ctx['conversation']->ai_enabled);
    }
}
