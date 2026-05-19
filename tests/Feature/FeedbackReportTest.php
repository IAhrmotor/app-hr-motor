<?php

namespace Tests\Feature;

use App\Mail\FeedbackReportMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FeedbackReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_authenticated_user_can_send_a_bug_report_with_screenshots(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Usuario Prueba',
            'email' => 'usuario.prueba@example.com',
        ]);

        $screenshotOne = UploadedFile::fake()->image('captura-1.png');
        $screenshotTwo = UploadedFile::fake()->image('captura-2.jpg');

        $response = $this->actingAs($user)
            ->from(route('home'))
            ->post(route('feedback.store'), [
                'type' => 'bug',
                'title' => 'No carga la agenda',
                'description' => 'Al abrir la agenda se queda en blanco.',
                'page_url' => route('home'),
                'page_title' => 'Inicio',
                'screenshots' => [$screenshotOne, $screenshotTwo],
            ]);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('feedback_report_success');

        Mail::assertSent(FeedbackReportMail::class, function (FeedbackReportMail $mail) use ($user): bool {
            $this->assertSame('bug', $mail->reportType);
            $this->assertSame('No carga la agenda', $mail->title);
            $this->assertSame('Al abrir la agenda se queda en blanco.', $mail->description);
            $this->assertSame($user->name, $mail->reporterName);
            $this->assertSame($user->email, $mail->reporterEmail);
            $this->assertSame(route('home'), $mail->pageUrl);
            $this->assertSame('Inicio', $mail->pageTitle);
            $this->assertCount(2, $mail->screenshots);

            $this->assertTrue($mail->envelope()->hasSubject('🐛 Bug - No carga la agenda'));
            $this->assertTrue($mail->envelope()->hasReplyTo($user->email, $user->name));
            $this->assertCount(2, $mail->attachments());

            return true;
        });
    }

    public function test_feedback_widget_is_rendered_in_the_main_layout(): void
    {
        $user = User::factory()->create([
            'email' => 'layout@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('Reportar un bug o enviar una sugerencia', false)
            ->assertSee(route('feedback.store'), false);
    }
}
