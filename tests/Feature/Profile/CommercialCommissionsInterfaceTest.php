<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommercialCommissionsInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config()->set([
            'services.commercial_commissions.url' => 'https://informes.test/api/comisiones_comercial',
            'services.commercial_commissions.username' => 'api-user',
            'services.commercial_commissions.password' => 'api-password',
        ]);
    }

    public function test_commercial_sees_own_commission_card_and_requested_month(): void
    {
        $commercial = $this->commercial();

        Http::fake(['*' => Http::response([
            'commercial_id' => $commercial->salesforce_user_id,
            'month' => '2026-08',
            'month_label' => 'August 2026',
            'economic_status' => 'Pending approval',
            'has_data' => true,
            'row' => ['final_commission' => '1250.50'],
        ])]);

        $response = $this->actingAs($commercial)->get(route('profile.show', ['month' => '2026-08']));

        $response->assertOk()
            ->assertSee('Comisiones personales')
            ->assertSee('1.250,50 €')
            ->assertSee('Comisión de agosto de 2026')
            ->assertDontSee('Estado económico')
            ->assertDontSee('Pending approval')
            ->assertDontSee('Provisional')
            ->assertDontSee('Definitive')
            ->assertDontSee('Reopened')
            ->assertSee('value="2026-08"', false)
            ->assertSee('max="' . now()->format('Y-m') . '"', false);

        Http::assertSent(function ($request) use ($commercial): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query === [
                'salesforce_id' => $commercial->salesforce_user_id,
                'month' => '2026-08',
            ];
        });
    }

    public function test_store_manager_and_area_manager_see_their_own_commission_cards(): void
    {
        $users = collect([
            User::ROLE_STORE_MANAGER,
            User::ROLE_AREA_MANAGER,
        ])->map(function (string $extraRole, int $index): User {
            return $this->commercial([
                'email' => "interface-profile-{$index}@example.com",
                'extra_role' => $extraRole,
                'salesforce_user_id' => "SF-INTERFACE-{$index}",
            ]);
        });

        Http::fake(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return Http::response([
                'commercial_id' => $query['salesforce_id'],
                'month' => $query['month'],
                'month_label' => 'Julio 2026',
                'economic_status' => 'paid',
                'has_data' => true,
                'row' => ['final_commission' => 25],
            ]);
        });

        foreach ($users as $user) {
            $this->actingAs($user)
                ->get(route('profile.show', ['month' => '2026-07']))
                ->assertOk()
                ->assertSee('Comisiones personales')
                ->assertSee('25,00 €');
        }

        Http::assertSentCount(2);
        foreach ($users as $user) {
            Http::assertSent(function ($request) use ($user): bool {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                return $query['salesforce_id'] === $user->salesforce_user_id
                    && $query['month'] === '2026-07';
            });
        }
    }

    public function test_hr_newcars_sees_their_own_commission_card(): void
    {
        $user = $this->commercial([
            'extra_role' => User::ROLE_HR_NEWCARS,
            'salesforce_user_id' => 'SF-HR-NEWCARS-001',
        ]);

        Http::fake(['*' => Http::response([
            'month' => '2026-08',
            'has_data' => true,
            'row' => ['final_commission' => 10],
        ])]);

        $this->actingAs($user)
            ->get(route('profile.show', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('Comisiones personales')
            ->assertSee('10,00 €');
    }

    public function test_complete_breakdown_is_rendered_without_technical_fields_or_economic_status(): void
    {
        $commercial = $this->commercial();

        Http::fake(['*' => Http::response([
            'commercial_id' => $commercial->salesforce_user_id,
            'month' => '2026-08',
            'economic_status' => 'Pending approval',
            'has_data' => true,
            'row' => [
                'final_commission' => 788.96,
                'operations_commission_amount' => 620,
                'prima_total' => 900,
                'prima_adjusted' => 850,
                'prima_after_penalties' => 800,
                'total_penalties' => 11.04,
                    'deliveries_count' => 0,
                    'sales_count' => 4,
                    'sales_amount' => 50000,
                    'stock_150_amount' => 0,
                    'bonus_15_amount' => 0,
                    'shared_amount' => null,
                    'discount_penalty_amount' => 12.5,
                    'guarantee_penalty' => 8.25,
                    'reviews_penalty' => 3.5,
                    'financing_penalty' => 4.75,
                    'financing_cancellation_penalty_amount' => -6,
                    'delivery_bracket_label' => 'Tramo A',
                    'delivery_bracket_percent' => 85.71,
                    'reviews_count' => 0,
                    'financing_percentage' => 0,
                    'details' => [
                        'operations' => [[
                            'commission_amount' => 44,
                            'reason' => 'Venta de prueba',
                            'type' => 'venta',
                            'cv_signed_date' => '2026-08-10',
                            'sales_count' => 4,
                            'internal_operation_id' => 'OP-SECRET-001',
                        ], [
                            'commission_amount' => 44,
                            'reason' => 'Venta de prueba',
                            'type' => 'venta',
                            'cv_signed_date' => '2026-08-11',
                        ], [
                            'commission_amount' => 30,
                            'reason' => 'Venta compartida',
                            'type' => 'venta_compartida',
                        ], [
                            'commission_amount' => 0,
                            'reason' => 'No mostrar',
                        ]],
                    'shared' => [[
                        'operation' => 'Operación compartida adicional',
                        'commercial_name' => 'Comercial anonimizado',
                        'amount' => 30,
                    ]],
                    'purchases' => [['amount' => 44]],
                    ],
            ],
        ])]);

        $this->actingAs($commercial)
            ->get(route('profile.show', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('788,96 €')
            ->assertSee('+')
            ->assertSee('− Penalización por descuento')
            ->assertSee('− Penalización de garantías')
            ->assertSee('− Penalización de reseñas')
            ->assertSee('− Penalización de financiación')
            ->assertSee('− Cancelación de financiación')
            ->assertSee('Subtotal de prima')
            ->assertSee('Prima después de penalizaciones')
            ->assertSee('44,00 €')
            ->assertSee('Venta de prueba × 2')
            ->assertSee('88,00 €')
            ->assertSee('Venta compartida propia')
            ->assertSee('30,00 €')
            ->assertSee('Operaciones')
            ->assertSee('Comisión por venta de otro comercial')
            ->assertSee('El detalle explica el origen de los importes y no se suma de nuevo a la comisión final.')
            ->assertSee('Ver detalle')
            ->assertDontSee('Entregas')
            ->assertDontSee('Incentivo Stock 150')
            ->assertDontSee('Bonificación 15')
            ->assertDontSee('85,71 %')
            ->assertDontSee('No mostrar')
            ->assertDontSee('Detalle de compras')
            ->assertDontSee('> = <', false)
            ->assertDontSee('Pending approval')
            ->assertDontSee('economic_status')
            ->assertDontSee('SF-COMMERCIAL-001')
            ->assertDontSee('OP-SECRET-001');

        $this->assertSame(1, substr_count($response->getContent(), '788,96 €'));
    }

    public function test_appraiser_uses_only_appraiser_settlement_lines(): void
    {
        $appraiser = $this->commercial();

        Http::fake(['*' => Http::response([
            'month' => '2026-08',
            'has_data' => true,
            'row' => [
                'commission_mode' => 'Tasador',
                'purchases_amount' => 120,
                'sales_amount' => 80,
                'appraiser_financing_commission' => 25,
                'appraiser_speed_amount' => 10,
                'financing_cancellation_penalty_amount' => -5,
                'prima_total' => 999,
                'prima_after_penalties' => 888,
                'final_commission' => 230,
            ],
        ])]);

        $this->actingAs($appraiser)
            ->get(route('profile.show', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('+ Compras gestionadas')
            ->assertSee('+ Ventas gestionadas')
            ->assertSee('+ Comisión de financiación')
            ->assertSee('+ Incentivo por velocidad')
            ->assertSee('− Cancelación de financiación')
            ->assertSee('230,00 €')
            ->assertDontSee('999,00 €')
            ->assertDontSee('Prima después de penalizaciones');
    }

    public function test_real_commercial_payload_explains_official_final_commission_without_duplicate_amounts(): void
    {
        $commercial = $this->commercial();

        Http::fake(['*' => Http::response([
            'month' => '2026-08',
            'has_data' => true,
            'row' => [
                'commission_mode' => 'Comercial',
                'sales_amount' => 450.0,
                'appraisals_amount' => 60.0,
                'changes_amount' => 0.0,
                'operations_commission_amount' => 510.0,
                'purchases_amount' => 60.0,
                'shared_amount' => 0.0,
                'discount_penalty_amount' => 19.5,
                'stock_150_amount' => 0.0,
                'bonus_15_amount' => 0.0,
                'prima_total' => 490.5,
                'delivery_bracket_percent' => 80.0,
                'prima_adjusted' => 404.4,
                'guarantee_penalty' => 40.44,
                'reviews_penalty' => 0.0,
                'financing_penalty' => 0.0,
                'financing_cancellation_penalty_amount' => 0.0,
                'total_penalties' => 40.44,
                'prima_after_penalties' => 363.96,
                'financing_product_amount' => 354.2,
                'guarantee_product_amount' => 70.8,
                'final_commission' => 788.96,
            ],
        ])]);

        $response = $this->actingAs($commercial)
            ->get(route('profile.show', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('+ Ventas gestionadas')
            ->assertSee('450,00 €')
            ->assertSee('− Penalización por descuento')
            ->assertSee('19,50 €')
            ->assertSee('+ Tasaciones y cambios')
            ->assertSee('60,00 €')
            ->assertSee('Subtotal de prima')
            ->assertSee('490,50 €')
            ->assertSee('− Ajuste por tramo de entregas')
            ->assertSee('86,10 €')
            ->assertSee('Prima ajustada')
            ->assertSee('404,40 €')
            ->assertSee('− Penalización de garantías')
            ->assertSee('40,44 €')
            ->assertSee('Prima después de penalizaciones')
            ->assertSee('363,96 €')
            ->assertSee('+ Comisión de producto financiero')
            ->assertSee('354,20 €')
            ->assertSee('+ Comisión de producto de garantía')
            ->assertSee('70,80 €')
            ->assertSee('Comisión final')
            ->assertSee('788,96 €')
            ->assertDontSee('510,00 €')
            ->assertDontSee('80,00 %')
            ->assertDontSee('Operaciones compartidas')
            ->assertDontSee('Bonificación 15');

        $this->assertSame(1, substr_count($response->getContent(), '788,96 €'));
    }

    public function test_partial_and_legacy_breakdowns_hide_only_missing_values(): void
    {
        $commercial = $this->commercial();

        Http::fakeSequence()
            ->push([
                'month' => '2026-07',
                'has_data' => true,
                'row' => [
                    'final_commission' => 0,
                    'operations_commission_amount' => null,
                    'deliveries_count' => 0,
                    'details' => [],
                ],
            ])
            ->push([
                'month' => '2026-05',
                'has_data' => true,
                'row' => ['final_commission' => 99.90],
            ]);

        $this->actingAs($commercial)
            ->get(route('profile.show', ['month' => '2026-07']))
            ->assertOk()
            ->assertSee('0,00 €')
            ->assertSee('Entregas')
            ->assertDontSee('Comisión de operaciones')
            ->assertDontSee('Detalle de operaciones');

        $this->actingAs($commercial)
            ->get(route('profile.show', ['month' => '2026-05']))
            ->assertOk()
            ->assertSee('99,90 €')
            ->assertDontSee('Operaciones')
            ->assertDontSee('Prima y entregas')
            ->assertDontSee('Financiación');
    }

    public function test_non_commercial_roles_do_not_see_the_card_or_call_the_api(): void
    {
        Http::fake();

        foreach ([
            ['role' => User::ROLE_ADMIN],
            ['role' => User::ROLE_MANAGER],
            ['role' => User::ROLE_USER, 'extra_role' => User::ROLE_MANAGEMENT],
            ['role' => User::ROLE_USER, 'extra_role' => User::ROLE_MARKETING],
        ] as $attributes) {
            $this->actingAs(User::factory()->create($attributes))
                ->get(route('profile.show'))
                ->assertOk()
                ->assertDontSee('data-personal-commissions-card', false);
        }

        Http::assertNothingSent();
    }

    public function test_card_is_not_visible_on_another_users_profile(): void
    {
        $viewer = $this->commercial();
        $otherCommercial = $this->commercial([
            'email' => 'other-commercial@example.com',
            'salesforce_user_id' => 'SF-OTHER',
        ]);

        Http::fake();

        $this->actingAs($viewer)
            ->get(route('users.show', $otherCommercial))
            ->assertOk()
            ->assertDontSee('data-personal-commissions-card', false);

        Http::assertNothingSent();
    }

    public function test_zero_commission_and_missing_data_are_rendered_differently(): void
    {
        $commercial = $this->commercial();

        Http::fakeSequence()
            ->push($this->payload($commercial, true, 0))
            ->push($this->payload($commercial, false, null));

        $this->actingAs($commercial)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('0,00 €')
            ->assertDontSee('No hay datos económicos');

        $this->actingAs($commercial)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('No hay datos económicos para julio de 2026.')
            ->assertDontSee('0,00 €');
    }

    public function test_api_errors_are_rendered_as_user_facing_messages(): void
    {
        $commercial = $this->commercial();

        Http::fakeSequence()
            ->push([], 404)
            ->push([], 422)
            ->push([], 429, ['Retry-After' => '12'])
            ->push([], 503);

        $this->actingAs($commercial)->get(route('profile.show'))->assertSee('No se ha encontrado información', false);
        $this->actingAs($commercial)->get(route('profile.show'))->assertSee('El mes seleccionado no es válido', false);
        $this->actingAs($commercial)->get(route('profile.show'))->assertSee('Se ha alcanzado temporalmente el límite de consultas', false)->assertSee('12 segundos', false);
        $this->actingAs($commercial)->get(route('profile.show'))->assertSee('El servicio de comisiones no está disponible', false);
    }

    public function test_missing_salesforce_id_does_not_show_card_or_call_api(): void
    {
        $commercial = $this->commercial(['salesforce_user_id' => null]);
        Http::fake();

        $this->actingAs($commercial)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertDontSee('data-personal-commissions-card', false);

        Http::assertNothingSent();
    }

    private function commercial(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-COMMERCIAL-001',
        ], $overrides));
    }

    private function payload(User $commercial, bool $hasData, mixed $commission): array
    {
        return [
            'commercial_id' => $commercial->salesforce_user_id,
            'month' => '2026-07',
            'month_label' => 'Julio 2026',
            'economic_status' => 'paid',
            'has_data' => $hasData,
            'row' => $hasData ? ['final_commission' => $commission] : null,
        ];
    }
}
