<?php

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Filament\Tests\Fixtures\Models\User;
use Filament\Tests\TestCase;
use PragmaRX\Google2FAQRCode\Google2FA;
use PragmaRX\Google2FAQRCode\QRCode\Bacon;
use PragmaRX\Google2FAQRCode\QRCode\Chillerlan;

use function Pest\Laravel\actingAs;

uses(TestCase::class);

it('returns valid SVG after decoding `generateQrCodeDataUri()` once', function (string $qrCodeServiceClass): void {
    if (($qrCodeServiceClass === Bacon::class) && (! class_exists(SvgImageBackEnd::class))) {
        $this->markTestSkipped('The optional bacon/bacon-qr-code package is not installed.');
    }

    Filament::setCurrentPanel('app-authentication');
    actingAs(User::factory()->create());

    $qrCodeService = match ($qrCodeServiceClass) {
        Chillerlan::class => app(Chillerlan::class),
        Bacon::class => app(Bacon::class, ['imageBackEnd' => app(SvgImageBackEnd::class)]),
    };

    $appAuthentication = app(AppAuthentication::class, [
        'google2FA' => app(Google2FA::class, ['qrCodeService' => $qrCodeService]),
    ]);

    $dataUri = $appAuthentication->generateQrCodeDataUri('JBSWY3DPEHPK3PXP');

    expect($dataUri)->toStartWith('data:image/svg+xml;base64,');

    $decoded = base64_decode(explode(',', $dataUri, 2)[1], strict: true);

    expect($decoded)
        ->toBeString()
        ->not->toStartWith('data:');

    $document = simplexml_load_string($decoded);

    expect($document)->not->toBeFalse();
    expect($document->getName())->toBe('svg');
})->with([
    'Chillerlan' => Chillerlan::class,
    'Bacon SVG' => Bacon::class,
]);
