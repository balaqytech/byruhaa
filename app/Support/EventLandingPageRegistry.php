<?php

namespace App\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\Factory as ViewFactory;

final readonly class EventLandingPageRegistry
{
    private const DefaultView = 'pages.public.site.events.show';

    public function __construct(
        private ConfigRepository $config,
        private Translator $translator,
        private ViewFactory $views,
    ) {}

    public function resolve(?string $key): string
    {
        $page = filled($key) ? ($this->pages()[$key] ?? null) : null;
        $view = is_array($page) ? ($page['view'] ?? null) : null;

        if (is_string($view) && $this->views->exists($view)) {
            return $view;
        }

        return $this->fallbackView();
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->pages() as $key => $page) {
            if (! is_string($key) || ! is_array($page)) {
                continue;
            }

            $view = $page['view'] ?? null;
            $label = $page['label'] ?? null;

            if (! is_string($view) || ! $this->views->exists($view) || ! is_string($label)) {
                continue;
            }

            $translatedLabel = $this->translator->get($label);
            $options[$key] = is_string($translatedLabel) ? $translatedLabel : $key;
        }

        return $options;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function pages(): array
    {
        $pages = $this->config->get('event-landings.pages', []);

        return is_array($pages) ? $pages : [];
    }

    private function fallbackView(): string
    {
        $view = $this->config->get('event-landings.fallback_view', self::DefaultView);

        return is_string($view) && $this->views->exists($view)
            ? $view
            : self::DefaultView;
    }
}
