<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_every_response_carries_the_headers_that_cannot_break_a_page(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }

    public function test_the_panel_and_the_portal_carry_them_too(): void
    {
        foreach ([route('admin.login'), route('portal.login')] as $url) {
            $this->get($url)->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    /**
     * The gallery lightbox's F key is the Fullscreen API. A tidy-looking
     * `fullscreen=()` in that header would switch it off, and nothing but this
     * assertion would say so.
     */
    public function test_the_gallery_keeps_its_fullscreen(): void
    {
        $this->assertStringContainsString(
            'fullscreen=(self)',
            $this->get(route('home'))->headers->get('Permissions-Policy'),
        );
    }

    /**
     * The policy itself, off whichever header is carrying it.
     *
     * Three assertions below are about what the policy *says* — the nonce, the
     * map origin, no wholesale inline — and not about which way CSP_ENFORCE is
     * set. Reading the report-only header by name made them a trap: the moment
     * somebody followed the instructions in config/security.php and switched
     * enforcement on, they failed, on a policy that had not changed a word.
     */
    private function policyOf(TestResponse $response): string
    {
        return $response->headers->get('Content-Security-Policy')
            ?? $response->headers->get('Content-Security-Policy-Report-Only')
            ?? '';
    }

    private function policy(): string
    {
        return $this->policyOf($this->get(route('home')));
    }

    public function test_the_policy_is_report_only_until_it_is_switched_on(): void
    {
        // Stated rather than inherited from whatever .env says today.
        config(['security.csp.enforce' => false]);

        $response = $this->get(route('home'));

        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function test_enforcing_moves_it_to_the_real_header(): void
    {
        config(['security.csp.enforce' => true]);

        $response = $this->get(route('home'));

        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_it_can_be_switched_off_entirely(): void
    {
        config(['security.csp.enabled' => false]);

        $response = $this->get(route('home'));

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));
        // The headers that cannot break anything are not part of that switch.
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * The point of the nonce: inline script is not allowed wholesale, so an
     * injected <script> tag is refused even though Alpine forces 'unsafe-eval'.
     */
    public function test_scripts_are_not_allowed_inline_wholesale(): void
    {
        preg_match('/script-src ([^;]+)/', $this->policy(), $matches);

        $this->assertStringNotContainsString("'unsafe-inline'", $matches[1]);
        $this->assertStringContainsString("'nonce-", $matches[1]);
    }

    public function test_the_head_script_carries_the_nonce_the_header_names(): void
    {
        $response = $this->get(route('home'));

        preg_match("/'nonce-([^']+)'/", $this->policyOf($response), $matches);
        $this->assertNotEmpty($matches[1] ?? null, 'The policy named no nonce.');

        $this->assertStringContainsString('<script nonce="'.$matches[1].'"', $response->getContent());

        // @vite's own tags too — the bundle is a module script, and one
        // without the nonce is a site with no JavaScript at all under an
        // enforced policy: no Alpine, no booking form, no lightbox.
        preg_match_all('/<script[^>]*type="module"[^>]*>/', $response->getContent(), $modules);
        $this->assertNotEmpty($modules[0], 'The bundle was not on the page at all.');

        foreach ($modules[0] as $tag) {
            $this->assertStringContainsString('nonce="'.$matches[1].'"', $tag);
        }

        // And nothing anywhere carries a different one.
        preg_match_all('/nonce="([^"]*)"/', $response->getContent(), $all);
        $this->assertSame([$matches[1]], array_values(array_unique($all[1])));
    }

    /**
     * A block that forgets the nonce does not throw — it silently stops
     * running. For the head script that means no theme before first paint, on
     * a page that otherwise looks fine.
     */
    public function test_no_view_has_an_inline_script_without_a_nonce(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            preg_match_all('/<script(?![^>]*\bnonce=)[^>]*>/', $file->getContents(), $matches);

            foreach ($matches[0] as $tag) {
                $offenders[] = $file->getRelativePathname().'  '.$tag;
            }
        }

        $this->assertSame([], $offenders);
    }

    /**
     * The sibling of the rule above, and the quieter half of it. Scripts get a
     * nonce, but a nonce cannot cover an attribute, so `onclick=` and
     * `onsubmit=` are refused outright under this policy — and refused without
     * a sound. A confirm() on a delete form that stops running does not block
     * the delete; it removes the question and submits, which looks exactly
     * like a delete that worked. Ask from app.js instead: `data-confirm`.
     */
    public function test_no_view_has_an_inline_event_handler(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            // Whitespace first, so Alpine's `x-on:click` and `@click` — which
            // the policy does allow, by way of 'unsafe-eval' — are not swept
            // up with it, nor is `<option`.
            preg_match_all('/\son[a-z]+\s*=\s*["\']/i', $file->getContents(), $matches);

            foreach ($matches[0] as $attribute) {
                $offenders[] = $file->getRelativePathname().' '.trim($attribute);
            }
        }

        $this->assertSame([], $offenders);
    }

    /** The contact page frames OpenStreetMap; a policy without it renders an empty box. */
    public function test_the_contact_map_origin_is_allowed(): void
    {
        $this->assertStringContainsString(
            'frame-src https://www.openstreetmap.org',
            $this->policy(),
        );
    }
}
