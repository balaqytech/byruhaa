<?php

namespace App\Modules\Identity\Actions;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class DownloadMinorAvatarImage
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    public function __construct(private PublicAvatarAddressResolver $addresses) {}

    public function download(string $url): string
    {
        for ($redirects = 0; $redirects <= 3; $redirects++) {
            $response = $this->request($url);

            if (in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                $location = $response->header('Location');

                if (! is_string($location) || trim($location) === '') {
                    throw $this->invalidUrl();
                }

                try {
                    $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
                } catch (InvalidArgumentException) {
                    throw $this->invalidUrl();
                }

                continue;
            }

            if ($response->status() !== 200) {
                throw ValidationException::withMessages(['avatar_url' => 'رابط الصورة لم يُرجع صورة قابلة للتنزيل.']);
            }

            return $this->readImage($response);
        }

        throw ValidationException::withMessages(['avatar_url' => 'رابط الصورة أعاد توجيهات كثيرة. أرسل الرابط المباشر للصورة.']);
    }

    private function request(string $url): Response
    {
        $parts = parse_url($url);

        if (strlen($url) > 4096
            || filter_var($url, FILTER_VALIDATE_URL) === false
            || ! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || ! isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['fragment'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw $this->invalidUrl();
        }

        $host = strtolower($parts['host']);
        $address = $this->addresses->resolve($host);
        $pinnedAddress = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ? '['.$address.']'
            : $address;

        try {
            $response = Http::accept('image/jpeg, image/png, image/webp')
                ->withUserAgent('Byruhaa-Avatar-Importer/1.0')
                ->connectTimeout(3)
                ->timeout(8)
                ->withoutRedirecting()
                ->withOptions([
                    'stream' => true,
                    'curl' => [
                        CURLOPT_RESOLVE => [$host.':443:'.$pinnedAddress],
                        CURLOPT_NOPROXY => '*',
                        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                    ],
                ])
                ->get($url);
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['avatar_url' => 'تعذّر تنزيل الصورة من الرابط. تأكد من إتاحته ثم حاول مجددًا.']);
        }

        return $response;
    }

    private function readImage(Response $response): string
    {
        $declaredSize = $response->header('Content-Length');

        if ($declaredSize !== '' && ctype_digit($declaredSize) && (int) $declaredSize > self::MAX_BYTES) {
            throw $this->tooLarge();
        }

        $stream = $response->toPsrResponse()->getBody();
        $contents = '';

        try {
            while (! $stream->eof()) {
                $chunk = $stream->read(min(8192, self::MAX_BYTES + 1 - strlen($contents)));

                if ($chunk === '') {
                    throw ValidationException::withMessages(['avatar_url' => 'انقطع تنزيل الصورة. حاول مرة أخرى.']);
                }

                $contents .= $chunk;

                if (strlen($contents) > self::MAX_BYTES) {
                    throw $this->tooLarge();
                }
            }
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['avatar_url' => 'انقطع تنزيل الصورة. حاول مرة أخرى.']);
        }

        if ($contents === '') {
            throw ValidationException::withMessages(['avatar_url' => 'رابط الصورة أعاد ملفًا فارغًا.']);
        }

        return $contents;
    }

    private function invalidUrl(): ValidationException
    {
        return ValidationException::withMessages(['avatar_url' => 'أرسل رابط HTTPS عامًا للصورة.']);
    }

    private function tooLarge(): ValidationException
    {
        return ValidationException::withMessages(['avatar_url' => 'حجم الصورة من الرابط يجب ألا يتجاوز ٢ ميغابايت.']);
    }
}
